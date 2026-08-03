<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\DTOs;

use AlexHackney\LaraNimble\DTOs\PullDto;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PullDtoTest extends TestCase
{
    #[Test]
    public function it_can_be_created_from_array(): void
    {
        $data = [
            'id' => 'pull-123',
            'source_url' => 'rtmp://source.com/live/stream',
            'local_app' => 'live',
            'local_stream' => 'pulled-stream',
            'protocol' => 'rtmp',
            'enabled' => true,
            'status' => 'active',
        ];

        $dto = PullDto::fromArray($data);

        $this->assertInstanceOf(PullDto::class, $dto);
        $this->assertEquals('pull-123', $dto->id);
        $this->assertEquals('rtmp://source.com/live/stream', $dto->sourceUrl);
        $this->assertEquals('live', $dto->localApp);
        $this->assertEquals('pulled-stream', $dto->localStream);
        $this->assertEquals('rtmp', $dto->protocol);
        $this->assertTrue($dto->enabled);
        $this->assertEquals('active', $dto->status);
    }

    #[Test]
    public function it_can_handle_optional_fields(): void
    {
        $data = [
            'id' => 'pull-123',
            'source_url' => 'rtmp://source.com/live/stream',
            'local_app' => 'live',
            'local_stream' => 'pulled-stream',
            'protocol' => 'rtmp',
        ];

        $dto = PullDto::fromArray($data);

        $this->assertNull($dto->enabled);
        $this->assertNull($dto->status);
    }

    #[Test]
    public function it_can_be_converted_to_array(): void
    {
        $data = [
            'id' => 'pull-123',
            'source_url' => 'rtmp://source.com/live/stream',
            'local_app' => 'live',
            'local_stream' => 'pulled-stream',
            'protocol' => 'rtmp',
            'enabled' => true,
            'status' => 'active',
        ];

        $dto = PullDto::fromArray($data);
        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('pull-123', $array['id']);
        $this->assertEquals('rtmp://source.com/live/stream', $array['source_url']);
        $this->assertTrue($array['enabled']);
    }
}
