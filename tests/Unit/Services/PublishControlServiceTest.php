<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\PublishControlEntryDto;
use AlexHackney\LaraNimble\Services\PublishControlService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class PublishControlServiceTest extends TestCase
{
    /** @var array<int, array{request: RequestInterface}> */
    private array $history = [];

    private function createService(MockHandler $mockHandler): PublishControlService
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

        return new PublishControlService($nimbleClient);
    }

    private function lastRequest(): RequestInterface
    {
        $this->assertNotEmpty($this->history);

        return $this->history[count($this->history) - 1]['request'];
    }

    #[Test]
    public function it_lists_active_publishers(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'PublishControlStatus' => [
                    ['key' => 'live/stream1', 'id' => 'pub-1', 'ip' => '192.168.0.10', 'stream' => 'live/stream1'],
                ],
            ])),
        ]);

        $service = $this->createService($mock);
        $publishers = $service->status();

        $this->assertInstanceOf(Collection::class, $publishers);
        $this->assertCount(1, $publishers);
        $this->assertContainsOnlyInstancesOf(PublishControlEntryDto::class, $publishers);
        $this->assertSame('pub-1', $publishers->first()->id);
        $this->assertSame('192.168.0.10', $publishers->first()->ip);

        $request = $this->lastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/manage/publish_control/status', $request->getUri()->getPath());
    }

    #[Test]
    public function it_denies_publishers_by_id(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'PublishControlDenyResponse' => ['status' => 'success'],
            ])),
        ]);

        $service = $this->createService($mock);

        $this->assertTrue($service->deny(['pub-1', 'pub-2']));

        $request = $this->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/manage/publish_control/deny', $request->getUri()->getPath());
        $this->assertSame(
            ['PublishControlDenyRequest' => ['pub-1', 'pub-2']],
            json_decode((string) $request->getBody(), true)
        );
    }

    #[Test]
    public function it_denies_a_single_publisher(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'PublishControlDenyResponse' => ['status' => 'success'],
            ])),
        ]);

        $service = $this->createService($mock);

        $this->assertTrue($service->deny('pub-1'));
    }

    #[Test]
    public function it_refuses_to_deny_an_empty_id_list(): void
    {
        $service = $this->createService(new MockHandler([]));

        $this->assertFalse($service->deny([]));
        $this->assertEmpty($this->history);
    }

    #[Test]
    public function it_returns_false_when_deny_fails(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'PublishControlDenyResponse' => ['status' => 'error'],
            ])),
        ]);

        $service = $this->createService($mock);

        $this->assertFalse($service->deny(['pub-1']));
    }
}
