<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\DTOs;

use AlexHackney\LaraNimble\DTOs\StreamDto;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StreamDtoTest extends TestCase
{
    #[Test]
    public function it_can_be_created_from_array(): void
    {
        $dto = StreamDto::fromArray([
            'app' => 'live',
            'strm' => 'stream1',
            'bandwidth' => 1697348,
            'resolution' => '1280x720',
            'vcodec' => 'avc1.42c01f',
            'acodec' => 'mp4a.40.2',
            'protocol' => 'RTMP',
            'source_url' => 'rtmp://127.0.0.1:1935/live/stream',
            'publisher_ip' => '192.168.0.95',
            'publisher_port' => 60349,
            'publish_time' => '1524060893',
        ]);

        $this->assertSame('live', $dto->app);
        $this->assertSame('stream1', $dto->stream);
        $this->assertSame(1697348, $dto->bandwidth);
        $this->assertSame('1280x720', $dto->resolution);
        $this->assertSame('avc1.42c01f', $dto->vcodec);
        $this->assertSame('mp4a.40.2', $dto->acodec);
        $this->assertSame('RTMP', $dto->protocol);
        $this->assertSame('rtmp://127.0.0.1:1935/live/stream', $dto->sourceUrl);
        $this->assertSame('192.168.0.95', $dto->publisherIp);
        $this->assertSame(60349, $dto->publisherPort);
        $this->assertSame(1524060893, $dto->publishTime);
    }

    #[Test]
    public function it_can_handle_optional_fields(): void
    {
        $dto = StreamDto::fromArray(['app' => 'live', 'strm' => 'stream1']);

        $this->assertNull($dto->bandwidth);
        $this->assertNull($dto->resolution);
        $this->assertNull($dto->vcodec);
        $this->assertNull($dto->protocol);
        $this->assertNull($dto->publisherIp);
        $this->assertNull($dto->publishTime);
    }

    #[Test]
    public function it_accepts_the_stream_key_as_fallback_for_strm(): void
    {
        $dto = StreamDto::fromArray(['app' => 'live', 'stream' => 'stream1']);

        $this->assertSame('stream1', $dto->stream);
    }

    #[Test]
    public function it_can_be_converted_to_array_with_wire_field_names(): void
    {
        $dto = StreamDto::fromArray([
            'app' => 'live',
            'strm' => 'stream1',
            'bandwidth' => 1697348,
        ]);

        $array = $dto->toArray();

        $this->assertSame('live', $array['app']);
        $this->assertSame('stream1', $array['strm']);
        $this->assertSame(1697348, $array['bandwidth']);
        $this->assertNull($array['resolution']);
    }
}
