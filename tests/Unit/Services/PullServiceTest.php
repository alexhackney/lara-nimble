<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\PullDto;
use AlexHackney\LaraNimble\Services\PullService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class PullServiceTest extends TestCase
{
    private function createService(MockHandler $mockHandler): PullService
    {
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'host' => 'localhost',
            'port' => 8082,
            'protocol' => 'http',
        ];

        $nimbleClient = new NimbleClient($config, $httpClient);

        return new PullService($nimbleClient);
    }

    /** @test */
    public function it_can_list_pull_configurations(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'pulls' => [
                    [
                        'id' => 'pull-1',
                        'source_url' => 'rtmp://source1.com/live/stream',
                        'local_app' => 'live',
                        'local_stream' => 'pulled-1',
                        'protocol' => 'rtmp',
                        'enabled' => true,
                        'status' => 'active',
                    ],
                    [
                        'id' => 'pull-2',
                        'source_url' => 'rtmp://source2.com/live/stream',
                        'local_app' => 'live',
                        'local_stream' => 'pulled-2',
                        'protocol' => 'rtmp',
                        'enabled' => false,
                        'status' => 'inactive',
                    ],
                ],
            ])),
        ]);

        $service = $this->createService($mock);
        $pulls = $service->list();

        $this->assertInstanceOf(Collection::class, $pulls);
        $this->assertCount(2, $pulls);
        $this->assertContainsOnlyInstancesOf(PullDto::class, $pulls);
        $this->assertEquals('pull-1', $pulls->first()->id);
    }

    /** @test */
    public function it_can_get_a_specific_pull_configuration(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'id' => 'pull-789',
                'source_url' => 'rtmp://source.com/live/stream',
                'local_app' => 'live',
                'local_stream' => 'pulled-stream',
                'protocol' => 'rtmp',
                'enabled' => true,
                'status' => 'active',
            ])),
        ]);

        $service = $this->createService($mock);
        $pull = $service->get('pull-789');

        $this->assertInstanceOf(PullDto::class, $pull);
        $this->assertEquals('pull-789', $pull->id);
        $this->assertEquals('live', $pull->localApp);
    }

    /** @test */
    public function it_can_add_a_pull_configuration(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
                'id' => 'pull-new',
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->add([
            'source_url' => 'rtmp://source.com/live/stream',
            'local_app' => 'live',
            'local_stream' => 'pulled-stream',
            'protocol' => 'rtmp',
            'enabled' => true,
        ]);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_update_a_pull_configuration(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->update('pull-789', [
            'enabled' => false,
        ]);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_delete_a_pull_configuration(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
                'message' => 'Pull configuration deleted',
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->delete('pull-789');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_delete_fails(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => false,
                'error' => 'Pull configuration not found',
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->delete('pull-789');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_get_pull_status(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'id' => 'pull-789',
                'status' => 'active',
                'uptime' => 3600,
                'bitrate' => 2500,
                'dropped_frames' => 0,
            ])),
        ]);

        $service = $this->createService($mock);
        $status = $service->status('pull-789');

        $this->assertIsArray($status);
        $this->assertEquals('active', $status['status']);
        $this->assertEquals(3600, $status['uptime']);
    }
}
