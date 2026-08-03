<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\DTOs;

use AlexHackney\LaraNimble\DTOs\RestreamDto;
use AlexHackney\LaraNimble\Enums\AuthSchema;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RestreamDtoTest extends TestCase
{
    private function fullData(): array
    {
        return [
            'id' => 42,
            'src_app' => 'live',
            'src_stream' => 'stream1',
            'dest_addr' => 'live-api-s.facebook.com',
            'dest_port' => 443,
            'dest_app' => 'rtmp',
            'dest_stream' => 'fb-key-123',
            'ssl' => true,
            'auth_schema' => 'NIMBLE',
            'dest_login' => 'user',
            'dest_password' => 'secret',
            'keep_src_stream_params' => true,
            'dest_app_params' => 'appParam=1',
            'dest_stream_params' => 'streamParam=1',
        ];
    }

    #[Test]
    public function it_can_be_created_from_array(): void
    {
        $dto = RestreamDto::fromArray($this->fullData());

        $this->assertInstanceOf(RestreamDto::class, $dto);
        $this->assertSame(42, $dto->id);
        $this->assertSame('live', $dto->srcApp);
        $this->assertSame('stream1', $dto->srcStream);
        $this->assertSame('live-api-s.facebook.com', $dto->destAddr);
        $this->assertSame(443, $dto->destPort);
        $this->assertSame('rtmp', $dto->destApp);
        $this->assertSame('fb-key-123', $dto->destStream);
        $this->assertTrue($dto->ssl);
        $this->assertSame('NIMBLE', $dto->authSchema);
        $this->assertSame('user', $dto->destLogin);
        $this->assertSame('secret', $dto->destPassword);
        $this->assertTrue($dto->keepSrcStreamParams);
        $this->assertSame('appParam=1', $dto->destAppParams);
        $this->assertSame('streamParam=1', $dto->destStreamParams);
    }

    #[Test]
    public function it_can_handle_optional_fields(): void
    {
        $dto = RestreamDto::fromArray([
            'src_app' => 'live',
            'src_stream' => 'stream1',
            'dest_addr' => 'example.com',
            'dest_port' => 1935,
            'dest_app' => 'live',
            'dest_stream' => 'key',
        ]);

        $this->assertNull($dto->id);
        $this->assertNull($dto->ssl);
        $this->assertNull($dto->authSchema);
        $this->assertNull($dto->destLogin);
        $this->assertNull($dto->destPassword);
        $this->assertNull($dto->keepSrcStreamParams);
        $this->assertNull($dto->destAppParams);
        $this->assertNull($dto->destStreamParams);
    }

    #[Test]
    public function it_casts_a_string_dest_port_to_int(): void
    {
        // The Nimble docs themselves show "dest_port":"1999" as a string
        $dto = RestreamDto::fromArray([
            'src_app' => 'live',
            'src_stream' => 'stream1',
            'dest_addr' => 'example.com',
            'dest_port' => '1999',
            'dest_app' => 'live-re',
            'dest_stream' => 'key',
        ]);

        $this->assertSame(1999, $dto->destPort);
    }

    #[Test]
    public function it_defaults_a_missing_src_stream_to_an_empty_string(): void
    {
        $dto = RestreamDto::fromArray([
            'src_app' => 'live',
            'dest_addr' => 'example.com',
            'dest_port' => 1935,
            'dest_app' => 'live',
            'dest_stream' => 'key',
        ]);

        $this->assertSame('', $dto->srcStream);
    }

    #[Test]
    public function it_can_be_converted_to_array(): void
    {
        $array = RestreamDto::fromArray($this->fullData())->toArray();

        $this->assertSame(42, $array['id']);
        $this->assertSame('live', $array['src_app']);
        $this->assertSame('stream1', $array['src_stream']);
        $this->assertSame('live-api-s.facebook.com', $array['dest_addr']);
        $this->assertSame(443, $array['dest_port']);
        $this->assertSame('rtmp', $array['dest_app']);
        $this->assertSame('fb-key-123', $array['dest_stream']);
        $this->assertTrue($array['ssl']);
        $this->assertSame('NIMBLE', $array['auth_schema']);
        $this->assertTrue($array['keep_src_stream_params']);
    }

    #[Test]
    public function it_builds_a_create_payload_without_id_or_unset_fields(): void
    {
        $dto = new RestreamDto(
            srcApp: 'live',
            srcStream: 'stream1',
            destAddr: 'example.com',
            destPort: 1935,
            destApp: 'live',
            destStream: 'key',
            id: 42,
        );

        $this->assertSame([
            'src_app' => 'live',
            'src_stream' => 'stream1',
            'dest_addr' => 'example.com',
            'dest_port' => 1935,
            'dest_app' => 'live',
            'dest_stream' => 'key',
        ], $dto->toCreatePayload());
    }

    #[Test]
    public function it_includes_optional_fields_in_the_create_payload_when_set(): void
    {
        $payload = RestreamDto::fromArray($this->fullData())->toCreatePayload();

        $this->assertArrayNotHasKey('id', $payload);
        $this->assertTrue($payload['ssl']);
        $this->assertSame('NIMBLE', $payload['auth_schema']);
        $this->assertSame('user', $payload['dest_login']);
        $this->assertSame('secret', $payload['dest_password']);
        $this->assertTrue($payload['keep_src_stream_params']);
        $this->assertSame('appParam=1', $payload['dest_app_params']);
        $this->assertSame('streamParam=1', $payload['dest_stream_params']);
    }

    #[Test]
    public function it_refuses_to_build_a_create_payload_without_a_src_stream(): void
    {
        $dto = new RestreamDto(
            srcApp: 'live',
            srcStream: '',
            destAddr: 'example.com',
            destPort: 1935,
            destApp: 'live',
            destStream: 'key',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('src_stream');

        $dto->toCreatePayload();
    }

    #[Test]
    public function it_decomposes_a_facebook_secure_stream_url(): void
    {
        $dto = RestreamDto::fromUrl(
            'live',
            'stream1',
            'rtmps://live-api-s.facebook.com:443/rtmp/FB-1234567890'
        );

        $this->assertSame('live', $dto->srcApp);
        $this->assertSame('stream1', $dto->srcStream);
        $this->assertSame('live-api-s.facebook.com', $dto->destAddr);
        $this->assertSame(443, $dto->destPort);
        $this->assertSame('rtmp', $dto->destApp);
        $this->assertSame('FB-1234567890', $dto->destStream);
        $this->assertTrue($dto->ssl);
    }

    #[Test]
    public function it_defaults_rtmps_urls_to_port_443(): void
    {
        $dto = RestreamDto::fromUrl('live', 'stream1', 'rtmps://live-api-s.facebook.com/rtmp/FB-KEY');

        $this->assertSame(443, $dto->destPort);
        $this->assertTrue($dto->ssl);
    }

    #[Test]
    public function it_defaults_rtmp_urls_to_port_1935_without_ssl(): void
    {
        $dto = RestreamDto::fromUrl('live', 'stream1', 'rtmp://a.rtmp.youtube.com/live2/yt-key');

        $this->assertSame('a.rtmp.youtube.com', $dto->destAddr);
        $this->assertSame(1935, $dto->destPort);
        $this->assertSame('live2', $dto->destApp);
        $this->assertSame('yt-key', $dto->destStream);
        $this->assertNull($dto->ssl);
    }

    #[Test]
    public function it_keeps_query_strings_as_part_of_the_stream_key(): void
    {
        $dto = RestreamDto::fromUrl(
            'live',
            'stream1',
            'rtmps://live-api-s.facebook.com:443/rtmp/FB-KEY?s_bl=1&s_psm=1'
        );

        $this->assertSame('FB-KEY?s_bl=1&s_psm=1', $dto->destStream);
    }

    #[Test]
    public function it_treats_all_leading_path_segments_as_the_application(): void
    {
        $dto = RestreamDto::fromUrl('live', 'stream1', 'rtmp://example.com/live/nested/key');

        $this->assertSame('live/nested', $dto->destApp);
        $this->assertSame('key', $dto->destStream);
    }

    #[Test]
    public function it_passes_optional_auth_settings_through_from_url(): void
    {
        $dto = RestreamDto::fromUrl(
            'live',
            'stream1',
            'rtmp://example.com/live/key',
            authSchema: 'NIMBLE',
            destLogin: 'user',
            destPassword: 'secret',
            keepSrcStreamParams: true,
        );

        $this->assertSame('NIMBLE', $dto->authSchema);
        $this->assertSame('user', $dto->destLogin);
        $this->assertSame('secret', $dto->destPassword);
        $this->assertTrue($dto->keepSrcStreamParams);
    }

    #[Test]
    public function it_rejects_non_rtmp_urls(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('rtmp or rtmps');

        RestreamDto::fromUrl('live', 'stream1', 'https://example.com/live/key');
    }

    #[Test]
    public function it_rejects_urls_without_a_stream_key_segment(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RestreamDto::fromUrl('live', 'stream1', 'rtmp://example.com/apponly');
    }

    #[Test]
    public function it_rejects_urls_without_a_host(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RestreamDto::fromUrl('live', 'stream1', 'rtmp:///live/key');
    }

    #[Test]
    public function it_accepts_an_auth_schema_enum(): void
    {
        $dto = new RestreamDto(
            srcApp: 'live',
            srcStream: 'stream1',
            destAddr: 'example.com',
            destPort: 1935,
            destApp: 'live',
            destStream: 'key',
            authSchema: AuthSchema::NIMBLE,
        );

        $this->assertSame('NIMBLE', $dto->authSchema);

        $fromUrl = RestreamDto::fromUrl('live', 'stream1', 'rtmp://example.com/live/key', authSchema: AuthSchema::AKAMAI);

        $this->assertSame('AKAMAI', $fromUrl->authSchema);
    }

    #[Test]
    public function it_matches_rules_regardless_of_id(): void
    {
        $a = RestreamDto::fromArray($this->fullData());
        $b = RestreamDto::fromArray(['id' => 999] + $this->fullData());

        $this->assertTrue($a->matches($b));
        $this->assertSame($a->fingerprint(), $b->fingerprint());
    }

    #[Test]
    public function it_treats_unset_optionals_as_defaults_when_matching(): void
    {
        $local = new RestreamDto(
            srcApp: 'live',
            srcStream: 'stream1',
            destAddr: 'Example.com',
            destPort: 1935,
            destApp: 'live',
            destStream: 'key',
        );

        $fromServer = RestreamDto::fromArray([
            'id' => 7,
            'src_app' => 'live',
            'src_stream' => 'stream1',
            'dest_addr' => 'example.com',
            'dest_port' => 1935,
            'dest_app' => 'live',
            'dest_stream' => 'key',
            'ssl' => false,
            'auth_schema' => 'NONE',
            'keep_src_stream_params' => false,
        ]);

        $this->assertTrue($local->matches($fromServer));
    }

    #[Test]
    public function it_does_not_match_rules_with_different_destinations(): void
    {
        $a = RestreamDto::fromArray($this->fullData());
        $b = RestreamDto::fromArray(['dest_stream' => 'other-key'] + $this->fullData());

        $this->assertFalse($a->matches($b));
    }
}
