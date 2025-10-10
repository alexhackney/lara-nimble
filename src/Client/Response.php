<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Client;

use Psr\Http\Message\ResponseInterface;

/**
 * Wrapper for HTTP response from Nimble API
 */
class Response
{
    private array $data;

    public function __construct(
        private readonly ResponseInterface $response
    ) {
        $this->data = $this->parseBody();
    }

    /**
     * Get the HTTP status code
     */
    public function statusCode(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * Check if the response was successful (2xx status code)
     */
    public function successful(): bool
    {
        return $this->statusCode() >= 200 && $this->statusCode() < 300;
    }

    /**
     * Check if the response failed (4xx or 5xx status code)
     */
    public function failed(): bool
    {
        return !$this->successful();
    }

    /**
     * Check if the response is a client error (4xx status code)
     */
    public function clientError(): bool
    {
        return $this->statusCode() >= 400 && $this->statusCode() < 500;
    }

    /**
     * Check if the response is a server error (5xx status code)
     */
    public function serverError(): bool
    {
        return $this->statusCode() >= 500;
    }

    /**
     * Get the response body as array
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * Get a specific key from the response data
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    /**
     * Get the raw response body as string
     */
    public function body(): string
    {
        return (string) $this->response->getBody();
    }

    /**
     * Get the underlying PSR response
     */
    public function toPsrResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Get response headers
     */
    public function headers(): array
    {
        return $this->response->getHeaders();
    }

    /**
     * Get a specific header value
     */
    public function header(string $name): ?string
    {
        $headers = $this->response->getHeader($name);
        return $headers[0] ?? null;
    }

    /**
     * Parse the JSON response body
     */
    private function parseBody(): array
    {
        $body = $this->body();

        if (empty($body)) {
            return [];
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (\JsonException $e) {
            return [];
        }
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return $this->data();
    }

    /**
     * Convert to JSON string
     */
    public function toJson(): string
    {
        return json_encode($this->data(), JSON_THROW_ON_ERROR);
    }
}
