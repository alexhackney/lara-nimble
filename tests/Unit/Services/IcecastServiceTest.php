<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\Services\IcecastService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class IcecastServiceTest extends TestCase
{
    /** @var array<int, array{request: RequestInterface}> */
    private array $history = [];

    private function createService(MockHandler $mockHandler): IcecastService
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

        return new IcecastService($nimbleClient);
    }

    private function lastRequest(): RequestInterface
    {
        $this->assertNotEmpty($this->history);

        return $this->history[count($this->history) - 1]['request'];
    }

    #[Test]
    public function it_fetches_stream_info(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'icy-name' => 'Radio name',
                'icy-genre' => 'jazz',
                'icy-br' => 128,
                'streamtitle' => 'Song Name',
            ])),
        ]);

        $service = $this->createService($mock);
        $info = $service->info('radio', 'main');

        $this->assertSame('Radio name', $info['icy-name']);
        $this->assertSame('Song Name', $info['streamtitle']);

        $request = $this->lastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/manage/icecast_stream_info/radio/main', $request->getUri()->getPath());
    }

    #[Test]
    public function it_updates_stream_metadata(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Ok'])),
        ]);

        $service = $this->createService($mock);

        $this->assertTrue($service->updateMetadata('radio', 'main', 'New Song', 'https://example.com'));

        $request = $this->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/manage/icecast_metadata/radio/main', $request->getUri()->getPath());
        $this->assertSame(
            ['streamtitle' => 'New Song', 'streamurl' => 'https://example.com'],
            json_decode((string) $request->getBody(), true)
        );
    }

    #[Test]
    public function it_omits_the_stream_url_when_not_given(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Ok'])),
        ]);

        $service = $this->createService($mock);
        $service->updateMetadata('radio', 'main', 'New Song');

        $this->assertSame(
            ['streamtitle' => 'New Song'],
            json_decode((string) $this->lastRequest()->getBody(), true)
        );
    }
}
