<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\DTOs;

use AlexHackney\LaraNimble\DTOs\SessionDto;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SessionDtoTest extends TestCase
{
    #[Test]
    public function it_can_be_created_from_array(): void
    {
        $dto = SessionDto::fromArray([
            'id' => 4,
            'app' => 'live',
            'stream' => 'stream1',
            'type' => 'HLS',
            'created' => 1654499440,
            'last_access' => 1654499466,
            'client_ip' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'bytes_recv' => 100,
            'bytes_sent' => 20000,
            'ppv_id' => 'viewer-1',
        ]);

        $this->assertSame(4, $dto->id);
        $this->assertSame('live', $dto->app);
        $this->assertSame('stream1', $dto->stream);
        $this->assertSame('HLS', $dto->type);
        $this->assertSame(1654499440, $dto->created);
        $this->assertSame(1654499466, $dto->lastAccess);
        $this->assertSame('127.0.0.1', $dto->clientIp);
        $this->assertSame('Mozilla/5.0', $dto->userAgent);
        $this->assertSame(100, $dto->bytesRecv);
        $this->assertSame(20000, $dto->bytesSent);
        $this->assertSame('viewer-1', $dto->ppvId);
    }

    #[Test]
    public function it_can_handle_optional_fields(): void
    {
        $dto = SessionDto::fromArray(['id' => 7]);

        $this->assertSame(7, $dto->id);
        $this->assertNull($dto->app);
        $this->assertNull($dto->stream);
        $this->assertNull($dto->type);
        $this->assertNull($dto->created);
        $this->assertNull($dto->clientIp);
        $this->assertNull($dto->ppvId);
    }

    #[Test]
    public function it_can_be_converted_to_array_with_wire_field_names(): void
    {
        $dto = SessionDto::fromArray([
            'id' => 4,
            'app' => 'live',
            'last_access' => 1654499466,
            'bytes_sent' => 20000,
        ]);

        $array = $dto->toArray();

        $this->assertSame(4, $array['id']);
        $this->assertSame('live', $array['app']);
        $this->assertSame(1654499466, $array['last_access']);
        $this->assertSame(20000, $array['bytes_sent']);
        $this->assertNull($array['ppv_id']);
    }
}
