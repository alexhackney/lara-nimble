<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Client;

use AlexHackney\LaraNimble\Exceptions\InvalidConfigurationException;
use AlexHackney\LaraNimble\Exceptions\NimbleApiException;
use AlexHackney\LaraNimble\Exceptions\NimbleAuthenticationException;
use AlexHackney\LaraNimble\Exceptions\NimbleConnectionException;
use AlexHackney\LaraNimble\Exceptions\NimbleTimeoutException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP client for Nimble Streamer API
 */
class NimbleClient
{
    private GuzzleClient $client;

    private string $baseUrl;

    private ?string $token;

    private int $retryTimes;

    private int $retrySleep;

    private bool $logRequests;

    private string $logChannel;

    /**
     * Create a new Nimble client instance
     *
     * @throws InvalidConfigurationException
     */
    public function __construct(array $config = [], ?GuzzleClient $client = null)
    {
        $this->validateConfig($config);

        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 8082;
        $protocol = $config['protocol'] ?? 'http';

        $this->baseUrl = sprintf('%s://%s:%d', $protocol, $host, $port);
        $this->token = $config['token'] ?? null;
        $this->retryTimes = $config['retry_times'] ?? 3;
        $this->retrySleep = $config['retry_sleep'] ?? 100;
        $this->logRequests = $config['log_requests'] ?? false;
        $this->logChannel = $config['log_channel'] ?? 'stack';

        $this->client = $client ?? $this->createGuzzleClient($config);
    }

    /**
     * Send a GET request
     *
     * @throws NimbleConnectionException
     * @throws NimbleAuthenticationException
     * @throws NimbleApiException
     * @throws NimbleTimeoutException
     */
    public function get(string $endpoint, array $params = []): Response
    {
        return $this->request('GET', $endpoint, ['query' => $params]);
    }

    /**
     * Send a POST request
     *
     * @throws NimbleConnectionException
     * @throws NimbleAuthenticationException
     * @throws NimbleApiException
     * @throws NimbleTimeoutException
     */
    public function post(string $endpoint, array $data = [], array $query = []): Response
    {
        $options = ['json' => $data];

        if ($query !== []) {
            $options['query'] = $query;
        }

        return $this->request('POST', $endpoint, $options);
    }

    /**
     * Send a PUT request
     *
     * @throws NimbleConnectionException
     * @throws NimbleAuthenticationException
     * @throws NimbleApiException
     * @throws NimbleTimeoutException
     */
    public function put(string $endpoint, array $data = []): Response
    {
        return $this->request('PUT', $endpoint, ['json' => $data]);
    }

    /**
     * Send a DELETE request
     *
     * @throws NimbleConnectionException
     * @throws NimbleAuthenticationException
     * @throws NimbleApiException
     * @throws NimbleTimeoutException
     */
    public function delete(string $endpoint, array $params = []): Response
    {
        return $this->request('DELETE', $endpoint, ['query' => $params]);
    }

    /**
     * Send a GET request for a large payload (e.g. DVR MP4 export).
     *
     * With a sink path, the body streams straight to that file instead of
     * into memory. Downloads default to no timeout since archive exports
     * can far exceed the configured request timeout; pass one to override.
     *
     * @throws NimbleConnectionException
     * @throws NimbleAuthenticationException
     * @throws NimbleApiException
     * @throws NimbleTimeoutException
     */
    public function download(string $endpoint, array $params = [], ?string $sink = null, ?int $timeout = null): Response
    {
        $options = ['query' => $params, 'timeout' => $timeout ?? 0];

        if ($sink !== null) {
            $options['sink'] = $sink;
        }

        return $this->request('GET', $endpoint, $options);
    }

    /**
     * Send an HTTP request with retry logic
     *
     * @throws NimbleConnectionException
     * @throws NimbleAuthenticationException
     * @throws NimbleApiException
     * @throws NimbleTimeoutException
     */
    private function request(string $method, string $endpoint, array $options = []): Response
    {
        $url = $this->buildUrl($endpoint);
        $options = $this->addAuthentication($options);

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->retryTimes) {
            try {
                $this->logRequest($method, $url, $options);

                $response = $this->client->request($method, $url, $options);
                $wrappedResponse = new Response($response);

                $this->logResponse($wrappedResponse);

                return $wrappedResponse;
            } catch (ConnectException $e) {
                $lastException = new NimbleConnectionException(
                    "Failed to connect to Nimble server at {$this->baseUrl}: {$e->getMessage()}",
                    previous: $e
                );

                // Retry on connection errors
                if ($this->shouldRetry($attempt)) {
                    $this->sleep($attempt);
                    $attempt++;

                    continue;
                }
            } catch (RequestException $e) {
                $response = $e->getResponse();

                if ($response === null) {
                    $lastException = new NimbleTimeoutException(
                        "Request to {$url} timed out",
                        previous: $e
                    );

                    // Retry on timeouts
                    if ($this->shouldRetry($attempt)) {
                        $this->sleep($attempt);
                        $attempt++;

                        continue;
                    }
                } else {
                    // Handle HTTP errors
                    $lastException = $this->handleHttpError($response, $e);

                    // Don't retry on authentication errors or client errors
                    if ($response->getStatusCode() === 401 || $response->getStatusCode() === 403) {
                        throw $lastException;
                    }

                    // Retry on server errors (5xx)
                    if ($response->getStatusCode() >= 500 && $this->shouldRetry($attempt)) {
                        $this->sleep($attempt);
                        $attempt++;

                        continue;
                    }
                }
            }

            break;
        }

        // This should never happen as exceptions are always set in catch blocks
        // but we need this for type safety
        throw $lastException ?? new NimbleConnectionException(
            'Request failed with unknown error'
        );
    }

    /**
     * Build full URL from endpoint
     */
    private function buildUrl(string $endpoint): string
    {
        $endpoint = ltrim($endpoint, '/');

        return "{$this->baseUrl}/{$endpoint}";
    }

    /**
     * Add authentication parameters to request options
     */
    private function addAuthentication(array $options): array
    {
        if ($this->token === null) {
            return $options;
        }

        $salt = random_int(0, 1000000);
        $hash = $this->generateAuthHash($salt, $this->token);

        // Add auth params to query string for all methods
        $authParams = [
            'salt' => $salt,
            'hash' => $hash,
        ];

        if (isset($options['query'])) {
            $options['query'] = array_merge($options['query'], $authParams);
        } else {
            $options['query'] = $authParams;
        }

        return $options;
    }

    /**
     * Generate authentication hash
     */
    private function generateAuthHash(int $salt, string $token): string
    {
        $str2hash = $salt.'/'.$token;
        $md5raw = md5($str2hash, true);

        return base64_encode($md5raw);
    }

    /**
     * Handle HTTP error responses
     */
    private function handleHttpError(ResponseInterface $response, RequestException $e): NimbleApiException|NimbleAuthenticationException
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $data = null;
        }

        $message = $data['error']['message'] ?? $data['message'] ?? $e->getMessage();

        if ($statusCode === 401 || $statusCode === 403) {
            return new NimbleAuthenticationException(
                "Authentication failed: {$message}",
                previous: $e
            );
        }

        return new NimbleApiException(
            "API error: {$message}",
            $statusCode,
            $data,
            $e
        );
    }

    /**
     * Check if we should retry the request
     */
    private function shouldRetry(int $attempt): bool
    {
        return $attempt < ($this->retryTimes - 1);
    }

    /**
     * Sleep with exponential backoff
     */
    private function sleep(int $attempt): void
    {
        $milliseconds = $this->retrySleep * (2 ** $attempt);
        usleep($milliseconds * 1000);
    }

    /**
     * Log the outgoing request
     */
    private function logRequest(string $method, string $url, array $options): void
    {
        if (! $this->logRequests) {
            return;
        }

        // Remove sensitive data from logs
        $logOptions = $options;
        if (isset($logOptions['query']['hash'])) {
            $logOptions['query']['hash'] = '***';
        }

        Log::channel($this->logChannel)->debug('Nimble API Request', [
            'method' => $method,
            'url' => $url,
            'options' => $logOptions,
        ]);
    }

    /**
     * Log the API response
     */
    private function logResponse(Response $response): void
    {
        if (! $this->logRequests) {
            return;
        }

        Log::channel($this->logChannel)->debug('Nimble API Response', [
            'status' => $response->statusCode(),
            'data' => $response->data(),
        ]);
    }

    /**
     * Create the Guzzle HTTP client
     *
     * Retries are handled exclusively by the loop in request(); adding
     * Guzzle retry middleware here would multiply the attempt count.
     */
    private function createGuzzleClient(array $config): GuzzleClient
    {
        return new GuzzleClient([
            'timeout' => $config['timeout'] ?? 30,
            'connect_timeout' => $config['connect_timeout'] ?? 10,
            'verify' => $config['verify_ssl'] ?? true,
            'http_errors' => true,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Validate configuration
     *
     * @throws InvalidConfigurationException
     */
    private function validateConfig(array $config): void
    {
        if (empty($config['host'])) {
            throw new InvalidConfigurationException('Nimble host is required');
        }

        if (! is_string($config['host'])) {
            throw new InvalidConfigurationException('Host must be a string');
        }

        if (isset($config['port']) && ! is_int($config['port'])) {
            throw new InvalidConfigurationException('Port must be an integer');
        }

        if (isset($config['protocol']) && ! in_array($config['protocol'], ['http', 'https'])) {
            throw new InvalidConfigurationException('Protocol must be either http or https');
        }

        if (isset($config['token']) && ! is_string($config['token'])) {
            throw new InvalidConfigurationException('Token must be a string or null');
        }
    }

    /**
     * Get the base URL
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Build a full, ready-to-request URL for an endpoint, including
     * authentication parameters when a management token is configured.
     *
     * Useful for endpoints whose response should be consumed directly by
     * the client application, such as DVR MP4 export.
     */
    public function url(string $endpoint, array $params = []): string
    {
        $options = $this->addAuthentication($params === [] ? [] : ['query' => $params]);
        $query = $options['query'] ?? [];

        $url = $this->buildUrl($endpoint);

        return $query === [] ? $url : $url.'?'.http_build_query($query);
    }
}
