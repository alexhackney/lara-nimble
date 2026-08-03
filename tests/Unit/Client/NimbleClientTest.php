<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Client;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\Exceptions\InvalidConfigurationException;
use AlexHackney\LaraNimble\Exceptions\NimbleApiException;
use AlexHackney\LaraNimble\Exceptions\NimbleAuthenticationException;
use AlexHackney\LaraNimble\Exceptions\NimbleConnectionException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class NimbleClientTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated_with_valid_config(): void
    {
        $config = [
            'host' => 'localhost',
            'port' => 8082,
            'protocol' => 'http',
        ];

        $client = new NimbleClient($config);

        $this->assertInstanceOf(NimbleClient::class, $client);
    }

    #[Test]
    public function it_throws_exception_when_host_is_missing(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Nimble host is required');

        new NimbleClient([]);
    }

    #[Test]
    public function it_builds_correct_base_url(): void
    {
        $config = [
            'host' => 'nimble.example.com',
            'port' => 8082,
            'protocol' => 'https',
        ];

        $client = new NimbleClient($config);
        $baseUrl = $client->getBaseUrl();

        $this->assertEquals('https://nimble.example.com:8082', $baseUrl);
    }

    #[Test]
    public function it_can_make_get_request(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'online'])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'host' => 'localhost',
            'port' => 8082,
            'protocol' => 'http',
        ];

        $client = new NimbleClient($config, $httpClient);
        $response = $client->get('/manage/status');

        $this->assertEquals(['status' => 'online'], $response->data());
    }

    #[Test]
    public function it_can_make_post_request(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['success' => true])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'host' => 'localhost',
            'port' => 8082,
            'protocol' => 'http',
        ];

        $client = new NimbleClient($config, $httpClient);
        $response = $client->post('/manage/publish', ['app' => 'live', 'stream' => 'test']);

        $this->assertEquals(['success' => true], $response->data());
    }

    #[Test]
    public function it_can_make_delete_request(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['success' => true])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'host' => 'localhost',
            'port' => 8082,
            'protocol' => 'http',
        ];

        $client = new NimbleClient($config, $httpClient);
        $response = $client->delete('/manage/session/123');

        $this->assertEquals(['success' => true], $response->data());
    }

    #[Test]
    public function it_adds_authentication_when_token_is_provided(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'online'])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'host' => 'localhost',
            'port' => 8082,
            'protocol' => 'http',
            'token' => 'secret-token',
        ];

        $client = new NimbleClient($config, $httpClient);
        $response = $client->get('/manage/status');

        $this->assertEquals(['status' => 'online'], $response->data());

        // Verify authentication was added to the request
        $lastRequest = $mock->getLastRequest();
        $this->assertNotNull($lastRequest);
    }

    #[Test]
    public function it_throws_connection_exception_on_network_error(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection timeout', new Request('GET', '/manage/status')),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'host' => 'localhost',
            'port' => 8082,
            'protocol' => 'http',
            'retry_times' => 1, // Only try once for testing
        ];

        $client = new NimbleClient($config, $httpClient);

        $this->expectException(NimbleConnectionException::class);
        $client->get('/manage/status');
    }

    #[Test]
    public function it_throws_authentication_exception_on_401(): void
    {
        $mock = new MockHandler([
            new Response(401, [], json_encode(['error' => 'Unauthorized'])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'host' => 'localhost',
            'port' => 8082,
            'protocol' => 'http',
        ];

        $client = new NimbleClient($config, $httpClient);

        $this->expectException(NimbleAuthenticationException::class);
        $client->get('/manage/status');
    }

    #[Test]
    public function it_throws_api_exception_on_4xx_errors(): void
    {
        $mock = new MockHandler([
            new Response(404, [], json_encode(['error' => 'Not found'])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'host' => 'localhost',
            'port' => 8082,
            'protocol' => 'http',
        ];

        $client = new NimbleClient($config, $httpClient);

        $this->expectException(NimbleApiException::class);
        $client->get('/manage/status');
    }

    #[Test]
    public function it_throws_api_exception_on_5xx_errors(): void
    {
        $mock = new MockHandler([
            new Response(500, [], json_encode(['error' => 'Internal server error'])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'host' => 'localhost',
            'port' => 8082,
            'protocol' => 'http',
            'retry_times' => 1, // Only try once for testing
        ];

        $client = new NimbleClient($config, $httpClient);

        $this->expectException(NimbleApiException::class);
        $client->get('/manage/status');
    }
}
