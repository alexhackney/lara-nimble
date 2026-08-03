<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Feature;

use AlexHackney\LaraNimble\DTOs\RestreamDto;
use AlexHackney\LaraNimble\Events\RestreamRuleCreated;
use AlexHackney\LaraNimble\Events\RestreamRuleDeleted;
use AlexHackney\LaraNimble\Facades\Nimble;
use AlexHackney\LaraNimble\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;

class RestreamEventsTest extends TestCase
{
    private function sampleRule(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'src_app' => 'live',
            'src_stream' => 'stream1',
            'dest_addr' => 'example.com',
            'dest_port' => 1935,
            'dest_app' => 'live',
            'dest_stream' => 'key',
        ], $overrides);
    }

    #[Test]
    public function it_dispatches_an_event_when_a_rule_is_created(): void
    {
        Event::fake();

        Nimble::fake([
            'POST /manage/rtmp/republish' => ['status' => 'Ok', 'rule' => $this->sampleRule()],
        ]);

        Nimble::restream()->create(new RestreamDto(
            srcApp: 'live',
            srcStream: 'stream1',
            destAddr: 'example.com',
            destPort: 1935,
            destApp: 'live',
            destStream: 'key',
        ));

        Event::assertDispatched(RestreamRuleCreated::class, function (RestreamRuleCreated $event): bool {
            return $event->rule->id === 1 && $event->rule->destStream === 'key';
        });
    }

    #[Test]
    public function it_dispatches_an_event_when_a_rule_is_deleted(): void
    {
        Event::fake();

        Nimble::fake([
            'DELETE /manage/rtmp/republish/*' => ['status' => 'Ok'],
        ]);

        Nimble::restream()->delete(42);

        Event::assertDispatched(RestreamRuleDeleted::class, fn (RestreamRuleDeleted $event) => $event->ruleId === 42);
    }

    #[Test]
    public function it_does_not_dispatch_a_delete_event_on_failure(): void
    {
        Event::fake();

        Nimble::fake([
            'DELETE /manage/rtmp/republish/*' => ['status' => 'NotFound'],
        ]);

        Nimble::restream()->delete(42);

        Event::assertNotDispatched(RestreamRuleDeleted::class);
    }

    #[Test]
    public function sync_dispatches_events_for_creates_and_deletes(): void
    {
        Event::fake();

        Nimble::fake([
            'GET /manage/rtmp/republish' => [
                'status' => 'Ok',
                'rules' => [$this->sampleRule(['id' => 9, 'dest_stream' => 'stale-key'])],
            ],
            'POST /manage/rtmp/republish' => ['status' => 'Ok', 'rule' => $this->sampleRule(['id' => 10])],
            'DELETE /manage/rtmp/republish/*' => ['status' => 'Ok'],
        ]);

        $result = Nimble::restream()->sync([
            RestreamDto::fromArray($this->sampleRule(['id' => null])),
        ]);

        $this->assertTrue($result->changed());
        Event::assertDispatched(RestreamRuleCreated::class);
        Event::assertDispatched(RestreamRuleDeleted::class, fn (RestreamRuleDeleted $event) => $event->ruleId === 9);
    }
}
