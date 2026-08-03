<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\DTOs;

use AlexHackney\LaraNimble\DTOs\DvrStreamDto;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DvrStreamDtoTest extends TestCase
{
    #[Test]
    public function it_can_be_created_from_array(): void
    {
        $dto = DvrStreamDto::fromArray([
            'stream' => 'live/stream1',
            'size' => 1234567,
            'duration' => 3600,
            'periods' => 2,
            'path' => '/var/dvr/live_stream1',
            'space_available' => 99999999,
            'vcodec' => 'h264',
            'acodec' => 'aac',
            'resolution' => '1920x1080',
            'bandwidth' => 2500000,
            'timeline' => [
                ['start' => 100, 'end' => 200, 'duration' => 100, 'period' => 1],
            ],
        ]);

        $this->assertSame('live/stream1', $dto->stream);
        $this->assertSame(1234567, $dto->size);
        $this->assertSame(3600, $dto->duration);
        $this->assertSame(2, $dto->periods);
        $this->assertSame('/var/dvr/live_stream1', $dto->path);
        $this->assertSame(99999999, $dto->spaceAvailable);
        $this->assertSame('h264', $dto->vcodec);
        $this->assertSame('aac', $dto->acodec);
        $this->assertSame('1920x1080', $dto->resolution);
        $this->assertSame(2500000, $dto->bandwidth);
        $this->assertCount(1, $dto->timeline);
        $this->assertSame(100, $dto->timeline[0]['start']);
    }

    #[Test]
    public function it_can_handle_optional_fields(): void
    {
        $dto = DvrStreamDto::fromArray(['stream' => 'live/stream1']);

        $this->assertSame('live/stream1', $dto->stream);
        $this->assertNull($dto->size);
        $this->assertNull($dto->duration);
        $this->assertNull($dto->path);
        $this->assertSame([], $dto->timeline);
    }

    #[Test]
    public function it_can_be_converted_to_array_with_wire_field_names(): void
    {
        $dto = DvrStreamDto::fromArray([
            'stream' => 'live/stream1',
            'space_available' => 500,
        ]);

        $array = $dto->toArray();

        $this->assertSame('live/stream1', $array['stream']);
        $this->assertSame(500, $array['space_available']);
        $this->assertNull($array['vcodec']);
    }
}
