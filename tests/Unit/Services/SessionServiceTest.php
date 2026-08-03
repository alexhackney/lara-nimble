<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\SessionDto;
use AlexHackney\LaraNimble\Services\SessionService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class SessionServiceTest extends TestCase
{
    /** @var array<int, array{request: RequestInterface}> */
    private array $history = [];

    private function createService(MockHandler $mockHandler): SessionService
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

        return new SessionService($nimbleClient);
    }

    private function lastRequest(): RequestInterface
    {
        $this->assertNotEmpty($this->history);

        return $this->history[count($this->history) - 1]['request'];
    }

    private function sessionsResponse(): Response
    {
        return new Response(200, [], json_encode([
            [
                'id' => 4,
                'app' => 'live',
                'stream' => 'stream1',
                'type' => 'HLS',
                'created' => 1654499440,
                'last_access' => 1654499466,
                'client_ip' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0',
                'bytes_recv' => 100,
                'bytes_sent' => 20000,
            ],
            ['id' => 5, 'app' => 'live', 'stream' => 'stream2', 'type' => 'MPEG-DASH'],
        ]));
    }

    #[Test]
    public function it_lists_active_sessions(): void
    {
        $service = $this->createService(new MockHandler([$this->sessionsResponse()]));
        $sessions = $service->list();

        $this->assertInstanceOf(Collection::class, $sessions);
        $this->assertCount(2, $sessions);
        $this->assertContainsOnlyInstancesOf(SessionDto::class, $sessions);

        $first = $sessions->first();
        $this->assertSame(4, $first->id);
        $this->assertSame('live', $first->app);
        $this->assertSame('HLS', $first->type);
        $this->assertSame(1654499440, $first->created);
        $this->assertSame(20000, $first->bytesSent);

        $request = $this->lastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/manage/sessions', $request->getUri()->getPath());
    }

    #[Test]
    public function it_finds_a_session_by_id(): void
    {
        $service = $this->createService(new MockHandler([$this->sessionsResponse()]));

        $session = $service->find(5);

        $this->assertInstanceOf(SessionDto::class, $session);
        $this->assertSame('stream2', $session->stream);
    }

    #[Test]
    public function it_returns_null_for_an_unknown_session(): void
    {
        $service = $this->createService(new MockHandler([$this->sessionsResponse()]));

        $this->assertNull($service->find(999));
    }

    #[Test]
    public function it_terminates_sessions_with_a_raw_id_array_body(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Ok'])),
        ]);

        $service = $this->createService($mock);

        $this->assertTrue($service->terminate([4, 5]));

        $request = $this->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/manage/sessions/delete', $request->getUri()->getPath());
        $this->assertSame([4, 5], json_decode((string) $request->getBody(), true));
    }

    #[Test]
    public function it_terminates_a_single_session(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Ok'])),
        ]);

        $service = $this->createService($mock);

        $this->assertTrue($service->terminate(4));

        $this->assertSame([4], json_decode((string) $this->lastRequest()->getBody(), true));
    }

    #[Test]
    public function it_returns_false_when_termination_fails(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Error'])),
        ]);

        $service = $this->createService($mock);

        $this->assertFalse($service->terminate([4]));
    }

    #[Test]
    public function it_refuses_to_terminate_an_empty_id_list(): void
    {
        $service = $this->createService(new MockHandler([]));

        $this->assertFalse($service->terminate([]));
        $this->assertEmpty($this->history);
    }
}
