<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\DTOs;

use AlexHackney\LaraNimble\DTOs\StreamDto;
use AlexHackney\LaraNimble\Enums\StreamProtocol;
use AlexHackney\LaraNimble\Enums\StreamStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StreamDtoTest extends TestCase
{
    #[Test]
    public function it_can_be_created_from_array(): void
    {
        $data = [
            'id' => 'stream-123',
            'name' => 'test-stream',
            'status' => 'active',
            'protocol' => 'rtmp',
        ];

        $dto = StreamDto::fromArray($data);

        $this->assertInstanceOf(StreamDto::class, $dto);
        $this->assertEquals('stream-123', $dto->id);
        $this->assertEquals('test-stream', $dto->name);
        $this->assertEquals(StreamStatus::ACTIVE, $dto->status);
        $this->assertEquals(StreamProtocol::RTMP, $dto->protocol);
    }

    #[Test]
    public function it_can_handle_optional_fields(): void
    {
        $data = [
            'id' => 'stream-123',
            'name' => 'test-stream',
            'status' => 'active',
            'protocol' => 'rtmp',
            'source' => 'rtmp://source.com/live/stream',
            'bitrate' => 2500,
            'viewers' => 42,
        ];

        $dto = StreamDto::fromArray($data);

        $this->assertEquals('rtmp://source.com/live/stream', $dto->source);
        $this->assertEquals(2500, $dto->bitrate);
        $this->assertEquals(42, $dto->viewers);
    }

    #[Test]
    public function it_can_be_converted_to_array(): void
    {
        $data = [
            'id' => 'stream-123',
            'name' => 'test-stream',
            'status' => 'active',
            'protocol' => 'rtmp',
            'source' => 'rtmp://source.com/live/stream',
            'bitrate' => 2500,
            'viewers' => 42,
        ];

        $dto = StreamDto::fromArray($data);
        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('stream-123', $array['id']);
        $this->assertEquals('test-stream', $array['name']);
        $this->assertEquals('active', $array['status']);
        $this->assertEquals('rtmp', $array['protocol']);
    }

    #[Test]
    public function it_handles_null_optional_fields(): void
    {
        $data = [
            'id' => 'stream-123',
            'name' => 'test-stream',
            'status' => 'inactive',
            'protocol' => 'srt',
        ];

        $dto = StreamDto::fromArray($data);

        $this->assertNull($dto->source);
        $this->assertNull($dto->bitrate);
        $this->assertNull($dto->viewers);
    }

    #[Test]
    public function it_can_handle_different_protocols(): void
    {
        $protocols = ['rtmp', 'mpegts', 'srt', 'ndi', 'hls', 'rtsp'];

        foreach ($protocols as $protocol) {
            $data = [
                'id' => 'stream-123',
                'name' => 'test-stream',
                'status' => 'active',
                'protocol' => $protocol,
            ];

            $dto = StreamDto::fromArray($data);
            $this->assertEquals($protocol, $dto->protocol->value);
        }
    }

    #[Test]
    public function it_can_handle_different_statuses(): void
    {
        $statuses = ['active', 'inactive', 'error'];

        foreach ($statuses as $status) {
            $data = [
                'id' => 'stream-123',
                'name' => 'test-stream',
                'status' => $status,
                'protocol' => 'rtmp',
            ];

            $dto = StreamDto::fromArray($data);
            $this->assertEquals($status, $dto->status->value);
        }
    }
}
