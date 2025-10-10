<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\RestreamDto;
use AlexHackney\LaraNimble\Services\RestreamService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class RestreamServiceTest extends TestCase
{
    private function createService(MockHandler $mockHandler): RestreamService
    {
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'host' => 'localhost',
            'port' => 8082,
            'protocol' => 'http',
        ];

        $nimbleClient = new NimbleClient($config, $httpClient);

        return new RestreamService($nimbleClient);
    }

    /** @test */
    public function it_can_list_restream_targets(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'restreams' => [
                    [
                        'id' => 'restream-1',
                        'stream_id' => 'stream-123',
                        'target_url' => 'rtmp://live.youtube.com/stream/key1',
                        'protocol' => 'rtmp',
                        'enabled' => true,
                        'status' => 'active',
                    ],
                    [
                        'id' => 'restream-2',
                        'stream_id' => 'stream-123',
                        'target_url' => 'rtmp://live.facebook.com/stream/key2',
                        'protocol' => 'rtmp',
                        'enabled' => false,
                        'status' => 'inactive',
                    ],
                ],
            ])),
        ]);

        $service = $this->createService($mock);
        $restreams = $service->list();

        $this->assertInstanceOf(Collection::class, $restreams);
        $this->assertCount(2, $restreams);
        $this->assertContainsOnlyInstancesOf(RestreamDto::class, $restreams);
        $this->assertEquals('restream-1', $restreams->first()->id);
    }

    /** @test */
    public function it_can_get_a_specific_restream_target(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'id' => 'restream-789',
                'stream_id' => 'stream-123',
                'target_url' => 'rtmp://live.youtube.com/stream/key789',
                'protocol' => 'rtmp',
                'enabled' => true,
                'status' => 'active',
            ])),
        ]);

        $service = $this->createService($mock);
        $restream = $service->get('restream-789');

        $this->assertInstanceOf(RestreamDto::class, $restream);
        $this->assertEquals('restream-789', $restream->id);
        $this->assertEquals('stream-123', $restream->streamId);
    }

    /** @test */
    public function it_can_add_a_restream_target(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
                'id' => 'restream-new',
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->add('stream-123', [
            'target_url' => 'rtmp://live.youtube.com/stream/newkey',
            'protocol' => 'rtmp',
            'enabled' => true,
        ]);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_update_a_restream_target(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->update('restream-789', [
            'enabled' => false,
        ]);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_delete_a_restream_target(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
                'message' => 'Restream target deleted',
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->delete('restream-789');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_delete_fails(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => false,
                'error' => 'Restream target not found',
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->delete('restream-789');

        $this->assertFalse($result);
    }
}
