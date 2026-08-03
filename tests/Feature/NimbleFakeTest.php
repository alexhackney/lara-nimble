<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Feature;

use AlexHackney\LaraNimble\DTOs\RestreamDto;
use AlexHackney\LaraNimble\Facades\Nimble;
use AlexHackney\LaraNimble\Testing\NimbleFake;
use AlexHackney\LaraNimble\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class NimbleFakeTest extends TestCase
{
    #[Test]
    public function it_swaps_the_facade_root_with_a_fake(): void
    {
        $fake = Nimble::fake();

        $this->assertInstanceOf(NimbleFake::class, Nimble::getFacadeRoot());
        $this->assertSame($fake, Nimble::getFacadeRoot());
    }

    #[Test]
    public function it_serves_stubbed_responses_through_real_services(): void
    {
        Nimble::fake([
            'GET /manage/rtmp/republish' => [
                'status' => 'Ok',
                'rules' => [[
                    'id' => 1,
                    'src_app' => 'live',
                    'src_stream' => 'stream1',
                    'dest_addr' => 'a.rtmp.youtube.com',
                    'dest_port' => 1935,
                    'dest_app' => 'live2',
                    'dest_stream' => 'yt-key',
                ]],
            ],
        ]);

        $rules = Nimble::restream()->list();

        $this->assertCount(1, $rules);
        $this->assertInstanceOf(RestreamDto::class, $rules->first());
        $this->assertSame('a.rtmp.youtube.com', $rules->first()->destAddr);
    }

    #[Test]
    public function it_matches_wildcard_and_method_specific_stubs(): void
    {
        Nimble::fake([
            'DELETE /manage/rtmp/republish/*' => ['status' => 'NotFound'],
            '/manage/rtmp/republish/*' => [
                'status' => 'Ok',
                'rules' => [[
                    'id' => 42,
                    'src_app' => 'live',
                    'src_stream' => 'stream1',
                    'dest_addr' => 'example.com',
                    'dest_port' => 1935,
                    'dest_app' => 'live',
                    'dest_stream' => 'key',
                ]],
            ],
        ]);

        // GET falls through the DELETE-specific stub to the wildcard
        $this->assertSame(42, Nimble::restream()->get(42)->id);

        // DELETE hits the method-specific NotFound stub
        $this->assertFalse(Nimble::restream()->delete(42));
    }

    #[Test]
    public function it_supports_closure_stubs(): void
    {
        Nimble::fake([
            'POST /manage/rtmp/republish' => function (string $method, string $endpoint, array $data): array {
                return ['status' => 'Ok', 'rule' => ['id' => 99] + $data];
            },
        ]);

        $created = Nimble::restream()->create(new RestreamDto(
            srcApp: 'live',
            srcStream: 'stream1',
            destAddr: 'example.com',
            destPort: 1935,
            destApp: 'live',
            destStream: 'key',
        ));

        $this->assertSame(99, $created->id);
        $this->assertSame('key', $created->destStream);
    }

    #[Test]
    public function unstubbed_requests_get_a_generic_ok(): void
    {
        Nimble::fake();

        $this->assertTrue(Nimble::server()->reloadConfig());
        $this->assertTrue(Nimble::restream()->list()->isEmpty());
    }

    #[Test]
    public function it_records_requests_and_supports_assertions(): void
    {
        $fake = Nimble::fake([
            'POST /manage/rtmp/republish' => ['status' => 'Ok', 'rule' => [
                'id' => 7,
                'src_app' => 'live',
                'src_stream' => 'stream1',
                'dest_addr' => 'live-api-s.facebook.com',
                'dest_port' => 443,
                'dest_app' => 'rtmp',
                'dest_stream' => 'fb-key',
                'ssl' => true,
            ]],
        ]);

        Nimble::restream()->create(RestreamDto::fromUrl(
            'live',
            'stream1',
            'rtmps://live-api-s.facebook.com:443/rtmp/fb-key'
        ));
        Nimble::restream()->delete(7);

        $fake->assertSentCount(2);
        $fake->assertRestreamCreated(fn (array $payload) => $payload['dest_addr'] === 'live-api-s.facebook.com' && $payload['ssl'] === true);
        $fake->assertRestreamDeleted(7);
        $fake->assertSent(fn (string $method, string $endpoint) => $method === 'DELETE');
        $fake->assertNotSent(fn (string $method) => $method === 'PUT');
    }

    #[Test]
    public function it_asserts_nothing_sent(): void
    {
        $fake = Nimble::fake();

        $fake->assertNothingSent();
    }
}
