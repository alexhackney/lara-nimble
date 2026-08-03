<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\DTOs;

use AlexHackney\LaraNimble\DTOs\RestreamStatsDto;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RestreamStatsDtoTest extends TestCase
{
    #[Test]
    public function it_can_be_created_from_array(): void
    {
        $dto = RestreamStatsDto::fromArray([
            'id' => 42,
            'src_app' => 'live',
            'src_stream' => 'stream1',
            'dest_addr' => 'live-api-s.facebook.com',
            'dest_port' => 443,
            'dest_app' => 'rtmp',
            'dest_stream' => 'fb-key-123',
            'state' => 'connected',
            'session_duration' => 120,
            'bandwidth' => 2500,
            'bytes_recv' => 1000,
            'bytes_sent' => 900,
            'retry_count' => 3,
        ]);

        $this->assertSame(42, $dto->id);
        $this->assertSame('live', $dto->srcApp);
        $this->assertSame('stream1', $dto->srcStream);
        $this->assertSame('live-api-s.facebook.com', $dto->destAddr);
        $this->assertSame(443, $dto->destPort);
        $this->assertSame('rtmp', $dto->destApp);
        $this->assertSame('fb-key-123', $dto->destStream);
        $this->assertSame('connected', $dto->state);
        $this->assertSame(120, $dto->sessionDuration);
        $this->assertSame(2500, $dto->bandwidth);
        $this->assertSame(1000, $dto->bytesRecv);
        $this->assertSame(900, $dto->bytesSent);
        $this->assertSame(3, $dto->retryCount);
    }

    #[Test]
    public function it_tolerates_missing_fields(): void
    {
        $dto = RestreamStatsDto::fromArray(['id' => 1, 'state' => 'reconnecting']);

        $this->assertSame(1, $dto->id);
        $this->assertSame('reconnecting', $dto->state);
        $this->assertNull($dto->srcApp);
        $this->assertNull($dto->destPort);
        $this->assertNull($dto->sessionDuration);
        $this->assertNull($dto->bandwidth);
        $this->assertNull($dto->bytesRecv);
        $this->assertNull($dto->bytesSent);
        $this->assertNull($dto->retryCount);
    }

    #[Test]
    public function it_casts_numeric_strings_to_int(): void
    {
        $dto = RestreamStatsDto::fromArray([
            'dest_port' => '1935',
            'session_duration' => '60',
            'retry_count' => '2',
        ]);

        $this->assertSame(1935, $dto->destPort);
        $this->assertSame(60, $dto->sessionDuration);
        $this->assertSame(2, $dto->retryCount);
    }

    #[Test]
    public function it_can_be_converted_to_array(): void
    {
        $dto = RestreamStatsDto::fromArray([
            'id' => 42,
            'src_app' => 'live',
            'state' => 'connected',
            'bytes_sent' => 900,
        ]);

        $array = $dto->toArray();

        $this->assertSame(42, $array['id']);
        $this->assertSame('live', $array['src_app']);
        $this->assertSame('connected', $array['state']);
        $this->assertSame(900, $array['bytes_sent']);
        $this->assertNull($array['dest_addr']);
    }
}
