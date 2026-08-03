<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\DTOs;

use AlexHackney\LaraNimble\DTOs\ArchiveDto;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ArchiveDtoTest extends TestCase
{
    #[Test]
    public function it_can_be_created_from_array(): void
    {
        $data = [
            'id' => 'archive-789',
            'stream_id' => 'stream-123',
            'filename' => 'stream1-2025-10-10-12-00-00.mp4',
            'size' => 1234567890,
            'duration' => 3600,
            'start_time' => '2025-10-10T12:00:00Z',
            'end_time' => '2025-10-10T13:00:00Z',
            'path' => '/var/dvr/stream1/archive-789.mp4',
        ];

        $dto = ArchiveDto::fromArray($data);

        $this->assertInstanceOf(ArchiveDto::class, $dto);
        $this->assertEquals('archive-789', $dto->id);
        $this->assertEquals('stream-123', $dto->streamId);
        $this->assertEquals('stream1-2025-10-10-12-00-00.mp4', $dto->filename);
        $this->assertEquals(1234567890, $dto->size);
        $this->assertEquals(3600, $dto->duration);
    }

    #[Test]
    public function it_can_handle_optional_fields(): void
    {
        $data = [
            'id' => 'archive-789',
            'stream_id' => 'stream-123',
            'filename' => 'stream1.mp4',
        ];

        $dto = ArchiveDto::fromArray($data);

        $this->assertNull($dto->size);
        $this->assertNull($dto->duration);
        $this->assertNull($dto->path);
    }

    #[Test]
    public function it_can_be_converted_to_array(): void
    {
        $data = [
            'id' => 'archive-789',
            'stream_id' => 'stream-123',
            'filename' => 'stream1.mp4',
            'size' => 1234567890,
            'duration' => 3600,
            'path' => '/var/dvr/archive.mp4',
        ];

        $dto = ArchiveDto::fromArray($data);
        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('archive-789', $array['id']);
        $this->assertEquals(1234567890, $array['size']);
    }
}
