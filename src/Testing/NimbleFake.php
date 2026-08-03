<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Testing;

use AlexHackney\LaraNimble\Nimble;
use PHPUnit\Framework\Assert;

/**
 * Test double for the Nimble manager: real services run against a
 * FakeNimbleClient, so DTO parsing behaves exactly as in production.
 *
 * Install via Nimble::fake([...stubs...]) and assert on what was sent.
 * Assertion callbacks receive (string $method, string $endpoint,
 * array $data, array $query) and return bool.
 */
class NimbleFake extends Nimble
{
    public function __construct(
        private readonly FakeNimbleClient $fakeClient,
    ) {
        parent::__construct($fakeClient);
    }

    /**
     * All recorded requests, in order.
     *
     * @return list<array{method: string, endpoint: string, data: array, query: array}>
     */
    public function recorded(): array
    {
        return $this->fakeClient->recorded();
    }

    /**
     * Assert at least one request matching the callback was sent.
     */
    public function assertSent(callable $callback): void
    {
        Assert::assertTrue(
            $this->hasRecorded($callback),
            'No request matching the given callback was sent to Nimble.'
        );
    }

    /**
     * Assert no request matching the callback was sent.
     */
    public function assertNotSent(callable $callback): void
    {
        Assert::assertFalse(
            $this->hasRecorded($callback),
            'An unexpected request matching the given callback was sent to Nimble.'
        );
    }

    /**
     * Assert exactly this many requests were sent.
     */
    public function assertSentCount(int $count): void
    {
        Assert::assertCount($count, $this->recorded());
    }

    /**
     * Assert no requests were sent at all.
     */
    public function assertNothingSent(): void
    {
        Assert::assertSame([], $this->recorded(), 'Requests were sent to Nimble.');
    }

    /**
     * Assert a republishing rule was created, optionally matching the
     * given callback on the request payload.
     *
     * @param  callable(array $payload): bool|null  $callback
     */
    public function assertRestreamCreated(?callable $callback = null): void
    {
        $this->assertSent(function (string $method, string $endpoint, array $data) use ($callback): bool {
            if ($method !== 'POST' || $endpoint !== '/manage/rtmp/republish') {
                return false;
            }

            return $callback === null || $callback($data);
        });
    }

    /**
     * Assert a republishing rule was deleted, optionally for a specific id.
     */
    public function assertRestreamDeleted(int|string|null $ruleId = null): void
    {
        $this->assertSent(function (string $method, string $endpoint) use ($ruleId): bool {
            if ($method !== 'DELETE') {
                return false;
            }

            return $ruleId === null
                ? str_starts_with($endpoint, '/manage/rtmp/republish/')
                : $endpoint === "/manage/rtmp/republish/{$ruleId}";
        });
    }

    private function hasRecorded(callable $callback): bool
    {
        foreach ($this->recorded() as $request) {
            if ($callback($request['method'], $request['endpoint'], $request['data'], $request['query'])) {
                return true;
            }
        }

        return false;
    }
}
