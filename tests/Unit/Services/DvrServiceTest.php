<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\DvrStreamDto;
use AlexHackney\LaraNimble\Services\DvrService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class DvrServiceTest extends TestCase
{
    /** @var array<int, array{request: RequestInterface}> */
    private array $history = [];

    private function createService(MockHandler $mockHandler, array $config = []): DvrService
    {
        $this->history = [];

        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push(Middleware::history($this->history));
        $httpClient = new Client(['handler' => $handlerStack]);

        $nimbleClient = new NimbleClient($config + [
            'host' => 'localhost',
            'port' => 8082,
            'protocol' => 'http',
        ], $httpClient);

        return new DvrService($nimbleClient);
    }

    private function lastRequest(): RequestInterface
    {
        $this->assertNotEmpty($this->history);

        return $this->history[count($this->history) - 1]['request'];
    }

    #[Test]
    public function it_fetches_dvr_status(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                [
                    'stream' => 'live/stream1',
                    'size' => 1234567,
                    'duration' => 3600,
                    'periods' => 2,
                    'path' => '/var/dvr/live_stream1',
                    'space_available' => 99999999,
                    'vcodec' => 'h264',
                    'acodec' => 'aac',
                    'resolution' => '1920x1080',
                    'bandwidth' => 2500000,
                ],
            ])),
        ]);

        $service = $this->createService($mock);
        $status = $service->status();

        $this->assertInstanceOf(Collection::class, $status);
        $this->assertCount(1, $status);
        $this->assertContainsOnlyInstancesOf(DvrStreamDto::class, $status);
        $this->assertSame('live/stream1', $status->first()->stream);
        $this->assertSame(3600, $status->first()->duration);

        $request = $this->lastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/manage/dvr_status', $request->getUri()->getPath());
        $this->assertSame('', $request->getUri()->getQuery());
    }

    #[Test]
    public function it_scopes_dvr_status_to_a_stream_with_timeline(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                [
                    'stream' => 'live/stream1',
                    'timeline' => [['start' => 100, 'end' => 200, 'duration' => 100, 'period' => 1]],
                ],
            ])),
        ]);

        $service = $this->createService($mock);
        $status = $service->status('live', 'stream1', timeline: true);

        $this->assertCount(1, $status->first()->timeline);

        $request = $this->lastRequest();
        $this->assertSame('/manage/dvr_status/live/stream1', $request->getUri()->getPath());
        $this->assertSame('timeline=true', $request->getUri()->getQuery());
    }

    #[Test]
    public function it_builds_an_mp4_export_url(): void
    {
        $service = $this->createService(new MockHandler([]));

        $url = $service->exportMp4Url('live', 'stream1', 1700000000, 1700003600);

        $this->assertSame(
            'http://localhost:8082/manage/dvr/export_mp4/live/stream1?start=1700000000&end=1700003600',
            $url
        );
    }

    #[Test]
    public function it_includes_auth_params_in_the_export_url_when_a_token_is_set(): void
    {
        $service = $this->createService(new MockHandler([]), ['token' => 'secret']);

        $url = $service->exportMp4Url('live', 'stream1');

        $this->assertStringContainsString('/manage/dvr/export_mp4/live/stream1?', $url);
        $this->assertStringContainsString('salt=', $url);
        $this->assertStringContainsString('hash=', $url);
    }

    #[Test]
    public function it_downloads_an_mp4_export(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'video/mp4'], 'RAW-MP4-BYTES'),
        ]);

        $service = $this->createService($mock);

        $this->assertSame('RAW-MP4-BYTES', $service->exportMp4('live', 'stream1', 1700000000));

        $request = $this->lastRequest();
        $this->assertSame('/manage/dvr/export_mp4/live/stream1', $request->getUri()->getPath());
        $this->assertSame('start=1700000000', $request->getUri()->getQuery());
    }

    #[Test]
    public function it_reloads_a_dvr_archive(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Ok'])),
        ]);

        $service = $this->createService($mock);

        $this->assertTrue($service->reload('live', 'stream1'));

        $request = $this->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/manage/dvr/reload/live/stream1', $request->getUri()->getPath());
    }

    #[Test]
    public function it_cleans_up_an_archive_with_a_target_depth(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Ok'])),
        ]);

        $service = $this->createService($mock);

        $this->assertTrue($service->cleanupArchive('live', 'stream1', targetDepth: 60));

        $request = $this->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/manage/dvr/cleanup_archive/live/stream1', $request->getUri()->getPath());
        $this->assertSame('target_depth=60', $request->getUri()->getQuery());
    }

    #[Test]
    public function it_returns_false_when_cleanup_target_is_not_found(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Not found'])),
        ]);

        $service = $this->createService($mock);

        $this->assertFalse($service->cleanupArchive('live', 'missing'));
    }

    #[Test]
    public function it_streams_an_mp4_export_to_a_file(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'video/mp4'], 'RAW-MP4-BYTES'),
        ]);

        $service = $this->createService($mock);
        $path = tempnam(sys_get_temp_dir(), 'nimble-test-');

        try {
            $this->assertTrue($service->exportMp4ToFile('live', 'stream1', $path, start: 1700000000));
            $this->assertSame('RAW-MP4-BYTES', file_get_contents($path));

            $request = $this->lastRequest();
            $this->assertSame('/manage/dvr/export_mp4/live/stream1', $request->getUri()->getPath());
            $this->assertSame('start=1700000000', $request->getUri()->getQuery());
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function it_builds_an_srt_export_url(): void
    {
        $service = $this->createService(new MockHandler([]));

        $this->assertSame(
            'http://localhost:8082/manage/dvr/export_srt/live/stream1?start=100&end=200&track=1&lang=en',
            $service->exportSrtUrl('live', 'stream1', 100, 200, 1, 'en')
        );
    }

    #[Test]
    public function it_downloads_srt_subtitles(): void
    {
        $mock = new MockHandler([
            new Response(200, [], "1\n00:00:01,000 --> 00:00:02,000\nHello\n"),
        ]);

        $service = $this->createService($mock);
        $srt = $service->exportSrt('live', 'stream1', track: 2);

        $this->assertStringContainsString('Hello', $srt);

        $request = $this->lastRequest();
        $this->assertSame('/manage/dvr/export_srt/live/stream1', $request->getUri()->getPath());
        $this->assertSame('track=2', $request->getUri()->getQuery());
    }
}
