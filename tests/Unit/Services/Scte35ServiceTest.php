<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\Services\Scte35Service;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class Scte35ServiceTest extends TestCase
{
    /** @var array<int, array{request: RequestInterface}> */
    private array $history = [];

    private function createService(MockHandler $mockHandler): Scte35Service
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

        return new Scte35Service($nimbleClient);
    }

    private function lastRequest(): RequestInterface
    {
        $this->assertNotEmpty($this->history);

        return $this->history[count($this->history) - 1]['request'];
    }

    #[Test]
    public function it_inserts_a_cue_out_marker_with_duration(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Ok'])),
        ]);

        $service = $this->createService($mock);

        $this->assertTrue($service->cueOut('live', 'stream1', 30));

        $request = $this->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/manage/advertizer/scte35_out/live/stream1', $request->getUri()->getPath());
        $this->assertSame('duration=30', $request->getUri()->getQuery());
    }

    #[Test]
    public function it_inserts_a_cue_out_marker_without_duration(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Ok'])),
        ]);

        $service = $this->createService($mock);

        $this->assertTrue($service->cueOut('live', 'stream1'));

        $this->assertSame('', $this->lastRequest()->getUri()->getQuery());
    }

    #[Test]
    public function it_inserts_a_cue_in_marker(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Ok'])),
        ]);

        $service = $this->createService($mock);

        $this->assertTrue($service->cueIn('live', 'stream1'));

        $request = $this->lastRequest();
        $this->assertSame('/manage/advertizer/scte35_in/live/stream1', $request->getUri()->getPath());
    }

    #[Test]
    public function it_inserts_a_time_signal_marker(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Ok'])),
        ]);

        $service = $this->createService($mock);

        $this->assertTrue($service->timeSignal('live', 'stream1', 52, 14, 'abc123'));

        $request = $this->lastRequest();
        $this->assertSame('/manage/advertizer/scte35_time_signal/live/stream1', $request->getUri()->getPath());
        $this->assertSame('seg_type=52&upid_type=14&upid=abc123', $request->getUri()->getQuery());
    }

    #[Test]
    public function it_returns_false_when_the_marker_is_rejected(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Error'])),
        ]);

        $service = $this->createService($mock);

        $this->assertFalse($service->cueIn('live', 'stream1'));
    }
}
