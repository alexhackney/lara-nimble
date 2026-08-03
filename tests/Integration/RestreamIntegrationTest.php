<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Integration;

use AlexHackney\LaraNimble\DTOs\RestreamDto;
use AlexHackney\LaraNimble\Services\RestreamService;
use PHPUnit\Framework\Attributes\Test;

/**
 * Full republish rule lifecycle against a real Nimble server.
 *
 * Creates a rule pointing at a non-routable destination, verifies it
 * through get/list/stats, then deletes it. The unique src_stream keeps
 * it from ever matching real traffic, and cleanup runs even on failure.
 */
class RestreamIntegrationTest extends IntegrationTestCase
{
    #[Test]
    public function it_completes_a_republish_rule_lifecycle(): void
    {
        $service = new RestreamService($this->client);

        $rule = new RestreamDto(
            srcApp: 'lara-nimble-itest',
            srcStream: 'itest-'.getmypid().'-'.substr(md5((string) mt_rand()), 0, 8),
            destAddr: '127.0.0.1',
            destPort: 19359,
            destApp: 'itest',
            destStream: 'itest-sink',
        );

        $created = $service->create($rule);
        $this->assertNotNull($created->id, 'Nimble did not assign an id to the created rule');

        try {
            $fetched = $service->get($created->id);
            $this->assertNotNull($fetched, 'Created rule was not retrievable by id');
            $this->assertTrue($rule->matches($fetched), 'Retrieved rule does not match what was sent');

            $listed = $service->list();
            $this->assertTrue(
                $listed->contains(fn (RestreamDto $r) => $r->id === $created->id),
                'Created rule missing from list()'
            );

            // stats() must at least return a well-formed collection
            $service->stats();
        } finally {
            $this->assertTrue($service->delete($created->id), 'Cleanup delete failed — remove the rule manually');
        }

        $this->assertNull($service->get($created->id), 'Rule still present after deletion');
    }
}
