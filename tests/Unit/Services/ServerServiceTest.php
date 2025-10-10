<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\ServerStatusDto;
use AlexHackney\LaraNimble\Services\ServerService;
use PHPUnit\Framework\TestCase;

class ServerServiceTest extends TestCase
{
    private function createService(MockHandler $mockHandler): ServerService
    {
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'host' => 'localhost',
            'port' => 8082,
            'protocol' => 'http',
        ];

        $nimbleClient = new NimbleClient($config, $httpClient);

        return new ServerService($nimbleClient);
    }

    /** @test */
    public function it_can_get_server_status(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'online',
                'version' => '4.5.1',
                'uptime' => 123456,
                'connections' => 42,
                'bandwidth' => [
                    'in' => 1234567,
                    'out' => 7654321,
                ],
            ])),
        ]);

        $service = $this->createService($mock);
        $status = $service->status();

        $this->assertInstanceOf(ServerStatusDto::class, $status);
        $this->assertEquals('online', $status->status);
        $this->assertEquals('4.5.1', $status->version);
        $this->assertEquals(42, $status->connections);
    }

    /** @test */
    public function it_can_reload_configuration(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
                'message' => 'Configuration reloaded',
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->reload();

        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_reload_fails(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => false,
                'error' => 'Failed to reload configuration',
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->reload();

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_sync_with_wms_panel(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
                'message' => 'Sync completed',
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->sync();

        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_sync_fails(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => false,
                'error' => 'Sync failed',
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->sync();

        $this->assertFalse($result);
    }
}
