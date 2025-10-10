<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\StreamDto;
use AlexHackney\LaraNimble\DTOs\StreamStatsDto;
use AlexHackney\LaraNimble\Services\StreamService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class StreamServiceTest extends TestCase
{
    private function createService(MockHandler $mockHandler): StreamService
    {
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'host' => 'localhost',
            'port' => 8082,
            'protocol' => 'http',
        ];

        $nimbleClient = new NimbleClient($config, $httpClient);

        return new StreamService($nimbleClient);
    }

    /** @test */
    public function it_can_list_streams(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'streams' => [
                    [
                        'id' => 'stream-1',
                        'name' => 'live-stream',
                        'status' => 'active',
                        'protocol' => 'rtmp',
                    ],
                    [
                        'id' => 'stream-2',
                        'name' => 'test-stream',
                        'status' => 'inactive',
                        'protocol' => 'srt',
                    ],
                ],
            ])),
        ]);

        $service = $this->createService($mock);
        $streams = $service->list();

        $this->assertInstanceOf(Collection::class, $streams);
        $this->assertCount(2, $streams);
        $this->assertContainsOnlyInstancesOf(StreamDto::class, $streams);
        $this->assertEquals('stream-1', $streams->first()->id);
        $this->assertEquals('live-stream', $streams->first()->name);
    }

    /** @test */
    public function it_can_get_a_specific_stream(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'id' => 'stream-123',
                'name' => 'my-stream',
                'status' => 'active',
                'protocol' => 'rtmp',
            ])),
        ]);

        $service = $this->createService($mock);
        $stream = $service->get('stream-123');

        $this->assertInstanceOf(StreamDto::class, $stream);
        $this->assertEquals('stream-123', $stream->id);
        $this->assertEquals('my-stream', $stream->name);
        $this->assertEquals('active', $stream->status->value);
    }

    /** @test */
    public function it_can_publish_a_stream(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
                'stream_id' => 'stream-123',
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->publish('live', 'stream1');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_unpublish_a_stream(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->unpublish('live', 'stream1');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_publish_fails(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => false,
                'error' => 'Stream already publishing',
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->publish('live', 'stream1');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_get_stream_statistics(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'stream_id' => 'stream-123',
                'bitrate' => 2500,
                'viewers' => 42,
                'duration' => 3600,
            ])),
        ]);

        $service = $this->createService($mock);
        $stats = $service->statistics('stream-123');

        $this->assertIsArray($stats);
        $this->assertEquals('stream-123', $stats['stream_id']);
        $this->assertEquals(2500, $stats['bitrate']);
        $this->assertEquals(42, $stats['viewers']);
    }

    /** @test */
    public function it_returns_empty_collection_when_no_streams(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'streams' => [],
            ])),
        ]);

        $service = $this->createService($mock);
        $streams = $service->list();

        $this->assertInstanceOf(Collection::class, $streams);
        $this->assertCount(0, $streams);
    }

    /** @test */
    public function it_can_get_live_status_for_specific_stream(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'streams' => [
                    [
                        'stream_name' => 'my-live-stream',
                        'application' => 'live',
                        'bandwidth' => 5000000,
                        'resolution' => '1920x1080',
                        'vcodec' => 'h264',
                        'acodec' => 'aac',
                        'protocol' => 'rtmp',
                        'source_url' => 'rtmp://source.com/live/stream',
                        'publisher_ip' => '192.168.1.100',
                        'publisher_port' => 1935,
                        'viewers' => 42,
                        'fps' => 30.0,
                        'bitrate' => 2500,
                        'duration' => 3600,
                        'start_time' => '2024-01-01 12:00:00',
                    ],
                    [
                        'stream_name' => 'another-stream',
                        'protocol' => 'srt',
                    ],
                ],
            ])),
        ]);

        $service = $this->createService($mock);
        $stats = $service->liveStatus('my-live-stream');

        $this->assertInstanceOf(StreamStatsDto::class, $stats);
        $this->assertEquals('my-live-stream', $stats->streamName);
        $this->assertEquals('live', $stats->application);
        $this->assertEquals(5000000, $stats->bandwidth);
        $this->assertEquals('1920x1080', $stats->resolution);
        $this->assertEquals('h264', $stats->videoCodec);
        $this->assertEquals('aac', $stats->audioCodec);
        $this->assertEquals('rtmp', $stats->protocol);
        $this->assertEquals('192.168.1.100', $stats->publisherIp);
        $this->assertEquals(1935, $stats->publisherPort);
        $this->assertEquals(42, $stats->viewers);
        $this->assertEquals(30.0, $stats->fps);
    }

    /** @test */
    public function it_returns_null_when_stream_is_not_live(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'streams' => [
                    [
                        'stream_name' => 'another-stream',
                        'protocol' => 'srt',
                    ],
                ],
            ])),
        ]);

        $service = $this->createService($mock);
        $stats = $service->liveStatus('non-existent-stream');

        $this->assertNull($stats);
    }

    /** @test */
    public function it_can_get_all_live_streams(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'streams' => [
                    [
                        'stream_name' => 'stream-1',
                        'application' => 'live',
                        'protocol' => 'rtmp',
                        'bandwidth' => 5000000,
                        'resolution' => '1920x1080',
                        'vcodec' => 'h264',
                        'acodec' => 'aac',
                    ],
                    [
                        'stream_name' => 'stream-2',
                        'application' => 'live',
                        'protocol' => 'srt',
                        'bandwidth' => 3000000,
                        'resolution' => '1280x720',
                        'vcodec' => 'h264',
                        'acodec' => 'aac',
                    ],
                    [
                        'stream_name' => 'stream-3',
                        'application' => 'test',
                        'protocol' => 'ndi',
                        'bandwidth' => 10000000,
                        'resolution' => '3840x2160',
                    ],
                ],
            ])),
        ]);

        $service = $this->createService($mock);
        $streams = $service->allLiveStreams();

        $this->assertInstanceOf(Collection::class, $streams);
        $this->assertCount(3, $streams);
        $this->assertContainsOnlyInstancesOf(StreamStatsDto::class, $streams);
        $this->assertEquals('stream-1', $streams->first()->streamName);
        $this->assertEquals('rtmp', $streams->first()->protocol);
        $this->assertEquals('stream-2', $streams->get(1)->streamName);
        $this->assertEquals('srt', $streams->get(1)->protocol);
        $this->assertEquals('stream-3', $streams->get(2)->streamName);
        $this->assertEquals('ndi', $streams->get(2)->protocol);
    }

    /** @test */
    public function it_returns_empty_collection_when_no_live_streams(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'streams' => [],
            ])),
        ]);

        $service = $this->createService($mock);
        $streams = $service->allLiveStreams();

        $this->assertInstanceOf(Collection::class, $streams);
        $this->assertCount(0, $streams);
    }
}
