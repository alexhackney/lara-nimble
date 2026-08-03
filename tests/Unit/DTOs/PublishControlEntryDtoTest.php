<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\DTOs;

use AlexHackney\LaraNimble\DTOs\PublishControlEntryDto;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PublishControlEntryDtoTest extends TestCase
{
    #[Test]
    public function it_can_be_created_from_array(): void
    {
        $dto = PublishControlEntryDto::fromArray([
            'key' => 'live/stream1',
            'id' => 'pub-1',
            'ip' => '192.168.0.10',
            'stream' => 'live/stream1',
        ]);

        $this->assertSame('pub-1', $dto->id);
        $this->assertSame('live/stream1', $dto->key);
        $this->assertSame('192.168.0.10', $dto->ip);
        $this->assertSame('live/stream1', $dto->stream);
    }

    #[Test]
    public function it_tolerates_missing_fields_and_casts_numeric_ids(): void
    {
        $dto = PublishControlEntryDto::fromArray(['id' => 42]);

        $this->assertSame('42', $dto->id);
        $this->assertNull($dto->key);
        $this->assertNull($dto->ip);
        $this->assertNull($dto->stream);
    }

    #[Test]
    public function it_can_be_converted_to_array(): void
    {
        $dto = PublishControlEntryDto::fromArray(['id' => 'pub-1', 'ip' => '10.0.0.1']);

        $array = $dto->toArray();

        $this->assertSame('pub-1', $array['id']);
        $this->assertSame('10.0.0.1', $array['ip']);
        $this->assertNull($array['key']);
    }
}
