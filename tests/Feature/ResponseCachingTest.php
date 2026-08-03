<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Feature;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\Services\ServerService;
use AlexHackney\LaraNimble\Services\StreamService;
use AlexHackney\LaraNimble\Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\RequestInterface;

class ResponseCachingTest extends TestCase
{
    /** @var array<int, array{request: RequestInterface}> */
    private array $history = [];

    private function makeClient(int $responses): NimbleClient
    {
        $this->history = [];

        $queue = [];
        for ($i = 0; $i < $responses; $i++) {
            $queue[] = new Response(200, [], json_encode([
                ['app' => 'live', 'streams' => [['strm' => 'stream1', 'bandwidth' => 100]]],
            ]));
        }

        $handlerStack = HandlerStack::create(new MockHandler($queue));
        $handlerStack->push(Middleware::history($this->history));

        return new NimbleClient(
            ['host' => 'localhost', 'port' => 8082, 'protocol' => 'http'],
            new Client(['handler' => $handlerStack])
        );
    }

    #[Test]
    public function it_caches_live_stream_status_when_enabled(): void
    {
        config()->set('nimble.cache.enabled', true);
        config()->set('nimble.cache.ttl', 60);

        $service = new StreamService($this->makeClient(2));

        $first = $service->list();
        $second = $service->list();

        $this->assertCount(1, $this->history);
        $this->assertSame('stream1', $first->first()->stream);
        $this->assertSame('stream1', $second->first()->stream);
    }

    #[Test]
    public function it_does_not_cache_when_disabled(): void
    {
        config()->set('nimble.cache.enabled', false);

        $service = new StreamService($this->makeClient(2));

        $service->list();
        $service->list();

        $this->assertCount(2, $this->history);
    }

    #[Test]
    public function it_caches_server_status_when_enabled(): void
    {
        config()->set('nimble.cache.enabled', true);
        config()->set('nimble.cache.ttl', 60);

        $this->history = [];
        $handlerStack = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['Connections' => 5])),
            new Response(200, [], json_encode(['Connections' => 6])),
        ]));
        $handlerStack->push(Middleware::history($this->history));

        $client = new NimbleClient(
            ['host' => 'localhost', 'port' => 8082, 'protocol' => 'http'],
            new Client(['handler' => $handlerStack])
        );
        $service = new ServerService($client);

        $this->assertSame(5, $service->status()->connections);
        $this->assertSame(5, $service->status()->connections);
        $this->assertCount(1, $this->history);
    }

    #[Test]
    public function cache_keys_are_scoped_per_server(): void
    {
        config()->set('nimble.cache.enabled', true);
        config()->set('nimble.cache.ttl', 60);

        $serviceA = new StreamService($this->makeClient(1));
        $serviceA->list();

        // A second server with a different base URL must not hit A's cache
        $historyB = [];
        $handlerStack = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode([])),
        ]));
        $handlerStack->push(Middleware::history($historyB));

        $clientB = new NimbleClient(
            ['host' => 'other-server', 'port' => 8082, 'protocol' => 'http'],
            new Client(['handler' => $handlerStack])
        );

        (new StreamService($clientB))->list();

        $this->assertCount(1, $historyB);
    }
}
