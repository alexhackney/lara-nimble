<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\DTOs;

use AlexHackney\LaraNimble\DTOs\SessionDto;
use PHPUnit\Framework\TestCase;

class SessionDtoTest extends TestCase
{
    /** @test */
    public function it_can_be_created_from_array(): void
    {
        $data = [
            'id' => 'session-456',
            'stream_id' => 'stream-123',
            'client_ip' => '192.168.1.100',
            'protocol' => 'rtmp',
            'started_at' => '2025-10-10T12:00:00Z',
            'duration' => 3600,
            'bytes_transferred' => 1234567890,
        ];

        $dto = SessionDto::fromArray($data);

        $this->assertInstanceOf(SessionDto::class, $dto);
        $this->assertEquals('session-456', $dto->id);
        $this->assertEquals('stream-123', $dto->streamId);
        $this->assertEquals('192.168.1.100', $dto->clientIp);
        $this->assertEquals('rtmp', $dto->protocol);
    }

    /** @test */
    public function it_can_handle_optional_fields(): void
    {
        $data = [
            'id' => 'session-456',
            'stream_id' => 'stream-123',
            'client_ip' => '192.168.1.100',
            'protocol' => 'rtmp',
        ];

        $dto = SessionDto::fromArray($data);

        $this->assertNull($dto->startedAt);
        $this->assertNull($dto->duration);
        $this->assertNull($dto->bytesTransferred);
    }

    /** @test */
    public function it_can_be_converted_to_array(): void
    {
        $data = [
            'id' => 'session-456',
            'stream_id' => 'stream-123',
            'client_ip' => '192.168.1.100',
            'protocol' => 'rtmp',
            'duration' => 3600,
            'bytes_transferred' => 1234567890,
        ];

        $dto = SessionDto::fromArray($data);
        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('session-456', $array['id']);
        $this->assertEquals('stream-123', $array['stream_id']);
        $this->assertEquals('192.168.1.100', $array['client_ip']);
    }
}
