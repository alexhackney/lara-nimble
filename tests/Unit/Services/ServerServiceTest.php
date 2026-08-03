<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\ServerStatusDto;
use AlexHackney\LaraNimble\Services\ServerService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class ServerServiceTest extends TestCase
{
    /** @var array<int, array{request: RequestInterface}> */
    private array $history = [];

    private function createService(MockHandler $mockHandler): ServerService
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

        return new ServerService($nimbleClient);
    }

    private function lastRequest(): RequestInterface
    {
        $this->assertNotEmpty($this->history);

        return $this->history[count($this->history) - 1]['request'];
    }

    #[Test]
    public function it_fetches_server_status(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'Connections' => 10,
                'OutRate' => 5120000,
                'SysInfo' => ['ap' => 2, 'tpms' => 2098434048, 'fpms' => 775127040],
                'RamCacheSize' => 1024,
                'FileCacheSize' => 2048,
                'MaxRamCacheSize' => 4096,
                'MaxFileCacheSize' => 8192,
            ])),
        ]);

        $service = $this->createService($mock);
        $status = $service->status();

        $this->assertInstanceOf(ServerStatusDto::class, $status);
        $this->assertSame(10, $status->connections);
        $this->assertSame(5120000, $status->outRate);
        $this->assertSame(1024, $status->ramCacheSize);
        $this->assertSame(8192, $status->maxFileCacheSize);
        $this->assertSame(2098434048, $status->sysInfo['tpms']);

        $request = $this->lastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/manage/server_status', $request->getUri()->getPath());
    }

    #[Test]
    public function it_reloads_the_configuration(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Ok'])),
        ]);

        $service = $this->createService($mock);

        $this->assertTrue($service->reloadConfig());

        $request = $this->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/manage/reload_config', $request->getUri()->getPath());
        $this->assertSame('', $request->getUri()->getQuery());
    }

    #[Test]
    public function it_reloads_the_configuration_including_drm(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Ok'])),
        ]);

        $service = $this->createService($mock);

        $this->assertTrue($service->reloadConfig(drm: true));

        $this->assertSame('drm=true', $this->lastRequest()->getUri()->getQuery());
    }

    #[Test]
    public function it_reloads_ssl_certificates(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Ok'])),
        ]);

        $service = $this->createService($mock);

        $this->assertTrue($service->reloadSslCertificates());

        $request = $this->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/manage/reload_ssl_certificates', $request->getUri()->getPath());
    }

    #[Test]
    public function it_syncs_panel_settings(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Ok'])),
        ]);

        $service = $this->createService($mock);

        $this->assertTrue($service->syncPanelSettings());

        $request = $this->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/manage/sync_panel_settings', $request->getUri()->getPath());
    }

    #[Test]
    public function it_fetches_playlist_status_as_raw_data(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                ['stream' => 'live/playlist1', 'block_id' => 1, 'block_name' => 'intro'],
            ])),
        ]);

        $service = $this->createService($mock);
        $status = $service->playlistStatus();

        $this->assertSame('live/playlist1', $status[0]['stream']);

        $request = $this->lastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/manage/server_playlist_status', $request->getUri()->getPath());
    }
}
