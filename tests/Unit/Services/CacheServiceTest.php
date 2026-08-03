<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\Services\CacheService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CacheServiceTest extends TestCase
{
    private function createService(MockHandler $mockHandler): CacheService
    {
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'host' => 'localhost',
            'port' => 8082,
            'protocol' => 'http',
        ];

        $nimbleClient = new NimbleClient($config, $httpClient);

        return new CacheService($nimbleClient);
    }

    #[Test]
    public function it_can_clear_cache(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
                'message' => 'Cache cleared',
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->clear();

        $this->assertTrue($result);
    }

    #[Test]
    public function it_can_clear_cache_with_type(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
                'message' => 'HLS cache cleared',
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->clear('hls');

        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_when_clear_fails(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => false,
                'error' => 'Failed to clear cache',
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->clear();

        $this->assertFalse($result);
    }

    #[Test]
    public function it_can_get_cache_statistics(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'total_size' => 1073741824,
                'items' => 1234,
                'hit_rate' => 0.85,
                'miss_rate' => 0.15,
            ])),
        ]);

        $service = $this->createService($mock);
        $stats = $service->statistics();

        $this->assertIsArray($stats);
        $this->assertEquals(1073741824, $stats['total_size']);
        $this->assertEquals(1234, $stats['items']);
        $this->assertEquals(0.85, $stats['hit_rate']);
    }

    #[Test]
    public function it_can_configure_cache_settings(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->configure([
            'enabled' => true,
            'max_size' => 2147483648,
            'ttl' => 3600,
        ]);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_when_configure_fails(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => false,
                'error' => 'Invalid configuration',
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->configure(['enabled' => true]);

        $this->assertFalse($result);
    }
}
