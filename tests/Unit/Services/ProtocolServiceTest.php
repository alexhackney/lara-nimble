<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\Services\ProtocolService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class ProtocolServiceTest extends TestCase
{
    /** @var array<int, array{request: RequestInterface}> */
    private array $history = [];

    private function createService(MockHandler $mockHandler): ProtocolService
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

        return new ProtocolService($nimbleClient);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function endpointProvider(): array
    {
        return [
            'rtmp settings' => ['rtmpSettings', '/manage/rtmp_settings'],
            'mpegts status' => ['mpegtsStatus', '/manage/mpeg2ts_status'],
            'mpegts settings' => ['mpegtsSettings', '/manage/mpeg2ts_settings'],
            'srt sender' => ['srtSenderStats', '/manage/srt_sender_stats'],
            'srt receiver' => ['srtReceiverStats', '/manage/srt_receiver_stats'],
            'rist sender' => ['ristSenderStats', '/manage/rist_sender_stats'],
            'rist receiver' => ['ristReceiverStats', '/manage/rist_receiver_stats'],
            'ndi list' => ['ndiList', '/manage/ndi/list'],
        ];
    }

    #[Test]
    #[DataProvider('endpointProvider')]
    public function it_fetches_protocol_data_from_the_right_endpoint(string $method, string $path): void
    {
        $payload = ['some' => 'data'];

        $service = $this->createService(new MockHandler([
            new Response(200, [], json_encode($payload)),
        ]));

        $this->assertSame($payload, $service->{$method}());

        $request = $this->history[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame($path, $request->getUri()->getPath());
    }

    #[Test]
    public function it_parses_mpegts_camera_settings(): void
    {
        $service = $this->createService(new MockHandler([
            new Response(200, [], json_encode([
                'CamerasHash' => '1623888659745',
                'Cameras' => [
                    ['id' => '60cabad9', 'ip' => '192.168.0.1', 'port' => 3131, 'protocol' => 'udp'],
                ],
            ])),
        ]));

        $settings = $service->mpegtsSettings();

        $this->assertSame('1623888659745', $settings['CamerasHash']);
        $this->assertCount(1, $settings['Cameras']);
    }
}
