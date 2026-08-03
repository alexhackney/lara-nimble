<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\StreamDto;
use AlexHackney\LaraNimble\Services\StreamService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class StreamServiceTest extends TestCase
{
    /** @var array<int, array{request: RequestInterface}> */
    private array $history = [];

    private function createService(MockHandler $mockHandler): StreamService
    {
        $this->history = [];

        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push(Middleware::history($this->history));
        $httpClient = new Client(['handler' => $handlerStack]);

        $nimbleClient = new NimbleClient([
            'host' => 'localhost',
            'port' => 8082,
            'protocol' => 'http',
        ], $httpClient);

        return new StreamService($nimbleClient);
    }

    private function liveStreamsResponse(): Response
    {
        return new Response(200, [], json_encode([
            [
                'app' => 'live',
                'streams' => [
                    [
                        'strm' => 'stream1',
                        'bandwidth' => 1697348,
                        'resolution' => '1280x720',
                        'vcodec' => 'avc1.42c01f',
                        'acodec' => 'mp4a.40.2',
                        'protocol' => 'RTMP',
                        'publisher_ip' => '192.168.0.95',
                        'publisher_port' => 60349,
                        'publish_time' => '1524060893',
                    ],
                    ['strm' => 'stream2', 'bandwidth' => 900000],
                ],
            ],
            [
                'app' => 'events',
                'streams' => [
                    ['strm' => 'concert', 'bandwidth' => 2500000],
                ],
            ],
        ]));
    }

    #[Test]
    public function it_lists_live_streams_across_all_applications(): void
    {
        $service = $this->createService(new MockHandler([$this->liveStreamsResponse()]));
        $streams = $service->list();

        $this->assertInstanceOf(Collection::class, $streams);
        $this->assertCount(3, $streams);
        $this->assertContainsOnlyInstancesOf(StreamDto::class, $streams);

        $first = $streams->first();
        $this->assertSame('live', $first->app);
        $this->assertSame('stream1', $first->stream);
        $this->assertSame(1697348, $first->bandwidth);
        $this->assertSame('1280x720', $first->resolution);
        $this->assertSame(1524060893, $first->publishTime);

        $this->assertSame('events', $streams->last()->app);

        $request = $this->history[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/manage/live_streams_status', $request->getUri()->getPath());
    }

    #[Test]
    public function it_returns_an_empty_collection_when_nothing_is_live(): void
    {
        $service = $this->createService(new MockHandler([
            new Response(200, [], json_encode([])),
        ]));

        $this->assertTrue($service->list()->isEmpty());
    }

    #[Test]
    public function it_filters_streams_by_application(): void
    {
        $service = $this->createService(new MockHandler([$this->liveStreamsResponse()]));
        $streams = $service->byApp('live');

        $this->assertCount(2, $streams);
        $this->assertSame(['stream1', 'stream2'], $streams->map(fn (StreamDto $s) => $s->stream)->all());
    }

    #[Test]
    public function it_finds_a_live_stream(): void
    {
        $service = $this->createService(new MockHandler([$this->liveStreamsResponse()]));
        $stream = $service->find('events', 'concert');

        $this->assertInstanceOf(StreamDto::class, $stream);
        $this->assertSame(2500000, $stream->bandwidth);
    }

    #[Test]
    public function it_returns_null_for_a_stream_that_is_not_live(): void
    {
        $service = $this->createService(new MockHandler([$this->liveStreamsResponse()]));

        $this->assertNull($service->find('live', 'offline-stream'));
    }

    #[Test]
    public function it_checks_stream_existence(): void
    {
        $service = $this->createService(new MockHandler([
            $this->liveStreamsResponse(),
            $this->liveStreamsResponse(),
        ]));

        $this->assertTrue($service->exists('live', 'stream1'));
        $this->assertFalse($service->exists('live', 'nope'));
    }
}
