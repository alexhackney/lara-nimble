<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\SessionDto;
use AlexHackney\LaraNimble\Services\SessionService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class SessionServiceTest extends TestCase
{
    private function createService(MockHandler $mockHandler): SessionService
    {
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'host' => 'localhost',
            'port' => 8082,
            'protocol' => 'http',
        ];

        $nimbleClient = new NimbleClient($config, $httpClient);

        return new SessionService($nimbleClient);
    }

    /** @test */
    public function it_can_list_sessions(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'sessions' => [
                    [
                        'id' => 'session-1',
                        'stream_id' => 'stream-123',
                        'client_ip' => '192.168.1.100',
                        'protocol' => 'rtmp',
                        'duration' => 3600,
                    ],
                    [
                        'id' => 'session-2',
                        'stream_id' => 'stream-456',
                        'client_ip' => '192.168.1.101',
                        'protocol' => 'srt',
                        'duration' => 1800,
                    ],
                ],
            ])),
        ]);

        $service = $this->createService($mock);
        $sessions = $service->list();

        $this->assertInstanceOf(Collection::class, $sessions);
        $this->assertCount(2, $sessions);
        $this->assertContainsOnlyInstancesOf(SessionDto::class, $sessions);
        $this->assertEquals('session-1', $sessions->first()->id);
    }

    /** @test */
    public function it_can_get_a_specific_session(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'id' => 'session-456',
                'stream_id' => 'stream-123',
                'client_ip' => '192.168.1.100',
                'protocol' => 'rtmp',
                'duration' => 3600,
            ])),
        ]);

        $service = $this->createService($mock);
        $session = $service->get('session-456');

        $this->assertInstanceOf(SessionDto::class, $session);
        $this->assertEquals('session-456', $session->id);
        $this->assertEquals('stream-123', $session->streamId);
    }

    /** @test */
    public function it_can_terminate_a_session(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
                'message' => 'Session terminated',
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->terminate('session-456');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_terminate_fails(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => false,
                'error' => 'Session not found',
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->terminate('session-456');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_get_session_statistics(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'session_id' => 'session-456',
                'duration' => 3600,
                'bytes_transferred' => 1234567890,
                'bitrate' => 2500,
            ])),
        ]);

        $service = $this->createService($mock);
        $stats = $service->statistics('session-456');

        $this->assertIsArray($stats);
        $this->assertEquals('session-456', $stats['session_id']);
        $this->assertEquals(3600, $stats['duration']);
    }
}
