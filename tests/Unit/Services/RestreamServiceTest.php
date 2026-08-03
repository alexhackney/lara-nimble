<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\RestreamDto;
use AlexHackney\LaraNimble\DTOs\RestreamStatsDto;
use AlexHackney\LaraNimble\Exceptions\NimbleApiException;
use AlexHackney\LaraNimble\Services\RestreamService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class RestreamServiceTest extends TestCase
{
    /** @var array<int, array{request: RequestInterface}> */
    private array $history = [];

    private function createService(MockHandler $mockHandler): RestreamService
    {
        $this->history = [];

        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push(Middleware::history($this->history));
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'host' => 'localhost',
            'port' => 8082,
            'protocol' => 'http',
        ];

        $nimbleClient = new NimbleClient($config, $httpClient);

        return new RestreamService($nimbleClient);
    }

    private function lastRequest(): RequestInterface
    {
        $this->assertNotEmpty($this->history);

        return $this->history[count($this->history) - 1]['request'];
    }

    private function sampleRule(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'src_app' => 'live',
            'src_stream' => 'stream1',
            'dest_addr' => 'live-api-s.facebook.com',
            'dest_port' => 443,
            'dest_app' => 'rtmp',
            'dest_stream' => 'fb-key-123',
            'ssl' => true,
        ], $overrides);
    }

    #[Test]
    public function it_lists_republish_rules_from_the_native_endpoint(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'Ok',
                'rules' => [
                    $this->sampleRule(),
                    $this->sampleRule([
                        'id' => 2,
                        'dest_addr' => 'a.rtmp.youtube.com',
                        'dest_port' => 1935,
                        'dest_app' => 'live2',
                        'dest_stream' => 'yt-key',
                        'ssl' => false,
                    ]),
                ],
            ])),
        ]);

        $service = $this->createService($mock);
        $rules = $service->list();

        $this->assertInstanceOf(Collection::class, $rules);
        $this->assertCount(2, $rules);
        $this->assertContainsOnlyInstancesOf(RestreamDto::class, $rules);
        $this->assertSame(1, $rules->first()->id);
        $this->assertSame('live-api-s.facebook.com', $rules->first()->destAddr);

        $request = $this->lastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/manage/rtmp/republish', $request->getUri()->getPath());
    }

    #[Test]
    public function it_returns_an_empty_collection_when_there_are_no_rules(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Ok', 'rules' => []])),
        ]);

        $service = $this->createService($mock);

        $this->assertTrue($service->list()->isEmpty());
    }

    #[Test]
    public function it_gets_a_single_rule_by_id(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'Ok',
                'rules' => [$this->sampleRule(['id' => 42])],
            ])),
        ]);

        $service = $this->createService($mock);
        $rule = $service->get(42);

        $this->assertInstanceOf(RestreamDto::class, $rule);
        $this->assertSame(42, $rule->id);
        $this->assertSame('live', $rule->srcApp);
        $this->assertSame('stream1', $rule->srcStream);

        $request = $this->lastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/manage/rtmp/republish/42', $request->getUri()->getPath());
    }

    #[Test]
    public function it_gets_a_single_rule_when_nimble_returns_a_bare_rule_object(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'Ok',
                'rule' => $this->sampleRule(['id' => 7]),
            ])),
        ]);

        $service = $this->createService($mock);
        $rule = $service->get(7);

        $this->assertInstanceOf(RestreamDto::class, $rule);
        $this->assertSame(7, $rule->id);
    }

    #[Test]
    public function it_returns_null_when_a_rule_does_not_exist(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'NotFound', 'rules' => []])),
        ]);

        $service = $this->createService($mock);

        $this->assertNull($service->get(999));
    }

    #[Test]
    public function it_creates_a_rule_and_returns_it_with_the_assigned_id(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'Ok',
                'rule' => $this->sampleRule(['id' => 11]),
            ])),
        ]);

        $service = $this->createService($mock);

        $created = $service->create(new RestreamDto(
            srcApp: 'live',
            srcStream: 'stream1',
            destAddr: 'live-api-s.facebook.com',
            destPort: 443,
            destApp: 'rtmp',
            destStream: 'fb-key-123',
            ssl: true,
        ));

        $this->assertInstanceOf(RestreamDto::class, $created);
        $this->assertSame(11, $created->id);

        $request = $this->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/manage/rtmp/republish', $request->getUri()->getPath());

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame([
            'src_app' => 'live',
            'src_stream' => 'stream1',
            'dest_addr' => 'live-api-s.facebook.com',
            'dest_port' => 443,
            'dest_app' => 'rtmp',
            'dest_stream' => 'fb-key-123',
            'ssl' => true,
        ], $body);
    }

    #[Test]
    public function it_throws_when_creation_does_not_return_a_rule(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Error'])),
        ]);

        $service = $this->createService($mock);

        $this->expectException(NimbleApiException::class);

        $service->create(new RestreamDto(
            srcApp: 'live',
            srcStream: 'stream1',
            destAddr: 'example.com',
            destPort: 1935,
            destApp: 'live',
            destStream: 'key',
        ));
    }

    #[Test]
    public function it_deletes_a_rule(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Ok'])),
        ]);

        $service = $this->createService($mock);

        $this->assertTrue($service->delete(42));

        $request = $this->lastRequest();
        $this->assertSame('DELETE', $request->getMethod());
        $this->assertSame('/manage/rtmp/republish/42', $request->getUri()->getPath());
    }

    #[Test]
    public function it_returns_false_when_deleting_a_missing_rule(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'NotFound'])),
        ]);

        $service = $this->createService($mock);

        $this->assertFalse($service->delete(999));
    }

    #[Test]
    public function it_fetches_republish_stats(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'Ok',
                'stats' => [
                    array_merge($this->sampleRule(), [
                        'state' => 'connected',
                        'session_duration' => 120,
                        'bandwidth' => 2500,
                        'bytes_recv' => 1000,
                        'bytes_sent' => 900,
                        'retry_count' => 0,
                    ]),
                ],
            ])),
        ]);

        $service = $this->createService($mock);
        $stats = $service->stats();

        $this->assertInstanceOf(Collection::class, $stats);
        $this->assertCount(1, $stats);
        $this->assertContainsOnlyInstancesOf(RestreamStatsDto::class, $stats);

        $entry = $stats->first();
        $this->assertSame('connected', $entry->state);
        $this->assertSame(120, $entry->sessionDuration);
        $this->assertSame(0, $entry->retryCount);
        $this->assertSame('live-api-s.facebook.com', $entry->destAddr);

        $request = $this->lastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/manage/rtmp/republish/stats', $request->getUri()->getPath());
    }

    private function desiredRule(array $overrides = []): RestreamDto
    {
        $data = array_merge($this->sampleRule(), $overrides);
        unset($data['id']);

        return RestreamDto::fromArray($data);
    }

    #[Test]
    public function sync_creates_missing_rules(): void
    {
        $mock = new MockHandler([
            // list(): nothing on the server
            new Response(200, [], json_encode(['status' => 'Ok', 'rules' => []])),
            // create()
            new Response(200, [], json_encode(['status' => 'Ok', 'rule' => $this->sampleRule(['id' => 1])])),
        ]);

        $service = $this->createService($mock);
        $result = $service->sync([$this->desiredRule()]);

        $this->assertCount(1, $result->created);
        $this->assertCount(0, $result->deleted);
        $this->assertCount(0, $result->kept);
        $this->assertTrue($result->changed());
        $this->assertSame(1, $result->created->first()->id);
    }

    #[Test]
    public function sync_deletes_rules_that_are_no_longer_desired(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Ok', 'rules' => [$this->sampleRule(['id' => 5])]])),
            new Response(200, [], json_encode(['status' => 'Ok'])),
        ]);

        $service = $this->createService($mock);
        $result = $service->sync([]);

        $this->assertCount(0, $result->created);
        $this->assertCount(1, $result->deleted);
        $this->assertSame(5, $result->deleted->first()->id);

        $request = $this->lastRequest();
        $this->assertSame('DELETE', $request->getMethod());
        $this->assertSame('/manage/rtmp/republish/5', $request->getUri()->getPath());
    }

    #[Test]
    public function sync_keeps_matching_rules_untouched(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Ok', 'rules' => [$this->sampleRule(['id' => 5])]])),
        ]);

        $service = $this->createService($mock);
        $result = $service->sync([$this->desiredRule()]);

        $this->assertCount(0, $result->created);
        $this->assertCount(0, $result->deleted);
        $this->assertCount(1, $result->kept);
        $this->assertFalse($result->changed());
        // Only the list() call happened
        $this->assertCount(1, $this->history);
    }

    #[Test]
    public function sync_converges_a_mixed_state(): void
    {
        $keep = $this->sampleRule(['id' => 1]);
        $stale = $this->sampleRule(['id' => 2, 'dest_stream' => 'old-key']);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Ok', 'rules' => [$keep, $stale]])),
            // create for the new rule
            new Response(200, [], json_encode(['status' => 'Ok', 'rule' => $this->sampleRule(['id' => 3, 'dest_stream' => 'new-key'])])),
            // delete for the stale rule
            new Response(200, [], json_encode(['status' => 'Ok'])),
        ]);

        $service = $this->createService($mock);
        $result = $service->sync([
            $this->desiredRule(),
            $this->desiredRule(['dest_stream' => 'new-key']),
        ]);

        $this->assertSame([3], $result->created->map(fn (RestreamDto $r) => $r->id)->all());
        $this->assertSame([2], $result->deleted->map(fn (RestreamDto $r) => $r->id)->all());
        $this->assertSame([1], $result->kept->map(fn (RestreamDto $r) => $r->id)->all());
    }

    #[Test]
    public function sync_deletes_duplicate_rules_for_the_same_target(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'Ok',
                'rules' => [$this->sampleRule(['id' => 1]), $this->sampleRule(['id' => 2])],
            ])),
            new Response(200, [], json_encode(['status' => 'Ok'])),
        ]);

        $service = $this->createService($mock);
        $result = $service->sync([$this->desiredRule()]);

        $this->assertSame([1], $result->kept->map(fn (RestreamDto $r) => $r->id)->all());
        $this->assertSame([2], $result->deleted->map(fn (RestreamDto $r) => $r->id)->all());
    }

    #[Test]
    public function sync_deduplicates_the_desired_set(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'Ok', 'rules' => []])),
            new Response(200, [], json_encode(['status' => 'Ok', 'rule' => $this->sampleRule(['id' => 1])])),
        ]);

        $service = $this->createService($mock);
        $result = $service->sync([$this->desiredRule(), $this->desiredRule()]);

        $this->assertCount(1, $result->created);
        // list + one create only
        $this->assertCount(2, $this->history);
    }
}
