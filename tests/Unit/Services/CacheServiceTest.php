<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\Services\CacheService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class CacheServiceTest extends TestCase
{
    /** @var array<int, array{request: RequestInterface}> */
    private array $history = [];

    private function createService(MockHandler $mockHandler): CacheService
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

        return new CacheService($nimbleClient);
    }

    private function lastRequest(): RequestInterface
    {
        $this->assertNotEmpty($this->history);

        return $this->history[count($this->history) - 1]['request'];
    }

    #[Test]
    public function it_resolves_a_cache_key_for_an_origin_url(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'Ok',
                'key' => '127.0.0.1:8081/vod/sample.mp4',
            ])),
        ]);

        $service = $this->createService($mock);

        $this->assertSame(
            '127.0.0.1:8081/vod/sample.mp4',
            $service->key('http://127.0.0.1:8081/dvr-test-remote-vod/sample.mp4')
        );

        $request = $this->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/manage/data_cache/get_key', $request->getUri()->getPath());
        $this->assertSame(
            ['url' => 'http://127.0.0.1:8081/dvr-test-remote-vod/sample.mp4'],
            json_decode((string) $request->getBody(), true)
        );
    }

    #[Test]
    public function it_returns_null_when_no_cache_key_is_resolved(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'NotFound'])),
        ]);

        $service = $this->createService($mock);

        $this->assertNull($service->key('http://example.com/unknown.mp4'));
    }

    #[Test]
    public function it_deletes_cached_items_and_returns_the_removed_list(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'Ok',
                'removed_items' => [
                    '127.0.0.1:8081/vod/sample.mp4',
                    '127.0.0.1:8081/vod/sample.mp4/chunk.m3u8',
                ],
            ])),
        ]);

        $service = $this->createService($mock);
        $removed = $service->delete('127.0.0.1:8081/vod/sample.mp4');

        $this->assertCount(2, $removed);

        $request = $this->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/manage/data_cache/delete', $request->getUri()->getPath());
        $this->assertSame(
            ['key' => '127.0.0.1:8081/vod/sample.mp4', 'dry_run' => false],
            json_decode((string) $request->getBody(), true)
        );
    }

    #[Test]
    public function it_passes_the_dry_run_flag(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'Ok',
                'removed_items' => ['item-1'],
            ])),
        ]);

        $service = $this->createService($mock);
        $service->delete('some-key', dryRun: true);

        $this->assertSame(
            ['key' => 'some-key', 'dry_run' => true],
            json_decode((string) $this->lastRequest()->getBody(), true)
        );
    }

    #[Test]
    public function it_returns_an_empty_list_when_the_key_is_not_found(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'NotFound'])),
        ]);

        $service = $this->createService($mock);

        $this->assertSame([], $service->delete('missing-key'));
    }
}
