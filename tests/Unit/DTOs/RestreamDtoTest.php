<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\DTOs;

use AlexHackney\LaraNimble\DTOs\RestreamDto;
use PHPUnit\Framework\TestCase;

class RestreamDtoTest extends TestCase
{
    /** @test */
    public function it_can_be_created_from_array(): void
    {
        $data = [
            'id' => 'restream-123',
            'stream_id' => 'stream-456',
            'target_url' => 'rtmp://live.youtube.com/stream/key123',
            'protocol' => 'rtmp',
            'enabled' => true,
            'status' => 'active',
        ];

        $dto = RestreamDto::fromArray($data);

        $this->assertInstanceOf(RestreamDto::class, $dto);
        $this->assertEquals('restream-123', $dto->id);
        $this->assertEquals('stream-456', $dto->streamId);
        $this->assertEquals('rtmp://live.youtube.com/stream/key123', $dto->targetUrl);
        $this->assertEquals('rtmp', $dto->protocol);
        $this->assertTrue($dto->enabled);
        $this->assertEquals('active', $dto->status);
    }

    /** @test */
    public function it_can_handle_optional_fields(): void
    {
        $data = [
            'id' => 'restream-123',
            'stream_id' => 'stream-456',
            'target_url' => 'rtmp://example.com/live',
            'protocol' => 'rtmp',
        ];

        $dto = RestreamDto::fromArray($data);

        $this->assertNull($dto->enabled);
        $this->assertNull($dto->status);
    }

    /** @test */
    public function it_can_be_converted_to_array(): void
    {
        $data = [
            'id' => 'restream-123',
            'stream_id' => 'stream-456',
            'target_url' => 'rtmp://live.youtube.com/stream/key123',
            'protocol' => 'rtmp',
            'enabled' => true,
            'status' => 'active',
        ];

        $dto = RestreamDto::fromArray($data);
        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('restream-123', $array['id']);
        $this->assertEquals('rtmp://live.youtube.com/stream/key123', $array['target_url']);
        $this->assertTrue($array['enabled']);
    }
}
