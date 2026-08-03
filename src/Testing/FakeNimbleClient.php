<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Testing;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\Client\Response;
use Closure;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Support\Str;

/**
 * Fake HTTP client for tests: resolves canned responses from endpoint
 * stubs and records every request instead of talking to a server.
 *
 * Stub keys are endpoint patterns, optionally prefixed with a method:
 *
 *     '/manage/rtmp/republish'          => matches any method
 *     'GET /manage/rtmp/republish'      => matches GET only
 *     '/manage/rtmp/republish/*'        => wildcard patterns supported
 *
 * Stub values are response arrays, or closures receiving
 * (string $method, string $endpoint, array $data, array $query) and
 * returning a response array. Unmatched requests get {"status": "Ok"}.
 */
class FakeNimbleClient extends NimbleClient
{
    /** @var list<array{method: string, endpoint: string, data: array, query: array}> */
    private array $recorded = [];

    /**
     * @param  array<string, array|Closure>  $stubs
     */
    public function __construct(
        private readonly array $stubs = [],
    ) {
        parent::__construct(['host' => 'nimble.fake']);
    }

    public function get(string $endpoint, array $params = []): Response
    {
        return $this->fakeRequest('GET', $endpoint, [], $params);
    }

    public function post(string $endpoint, array $data = [], array $query = []): Response
    {
        return $this->fakeRequest('POST', $endpoint, $data, $query);
    }

    public function put(string $endpoint, array $data = []): Response
    {
        return $this->fakeRequest('PUT', $endpoint, $data, []);
    }

    public function delete(string $endpoint, array $params = []): Response
    {
        return $this->fakeRequest('DELETE', $endpoint, [], $params);
    }

    public function download(string $endpoint, array $params = [], ?string $sink = null, ?int $timeout = null): Response
    {
        $response = $this->fakeRequest('GET', $endpoint, [], $params);

        if ($sink !== null) {
            file_put_contents($sink, $response->body());
        }

        return $response;
    }

    /**
     * All recorded requests, in order.
     *
     * @return list<array{method: string, endpoint: string, data: array, query: array}>
     */
    public function recorded(): array
    {
        return $this->recorded;
    }

    private function fakeRequest(string $method, string $endpoint, array $data, array $query): Response
    {
        $endpoint = '/'.ltrim($endpoint, '/');

        $this->recorded[] = [
            'method' => $method,
            'endpoint' => $endpoint,
            'data' => $data,
            'query' => $query,
        ];

        $body = $this->resolveStub($method, $endpoint, $data, $query);

        return new Response(new Psr7Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode($body, JSON_THROW_ON_ERROR)
        ));
    }

    private function resolveStub(string $method, string $endpoint, array $data, array $query): array
    {
        foreach ($this->stubs as $pattern => $stub) {
            $expectedMethod = null;

            if (str_contains($pattern, ' ')) {
                [$expectedMethod, $pattern] = explode(' ', $pattern, 2);
            }

            if ($expectedMethod !== null && strcasecmp($expectedMethod, $method) !== 0) {
                continue;
            }

            $pattern = '/'.ltrim(trim($pattern), '/');

            if ($pattern === $endpoint || Str::is($pattern, $endpoint)) {
                return $stub instanceof Closure
                    ? $stub($method, $endpoint, $data, $query)
                    : $stub;
            }
        }

        return ['status' => 'Ok'];
    }
}
