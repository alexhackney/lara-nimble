<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\DTOs;

use AlexHackney\LaraNimble\DTOs\ServerStatusDto;
use PHPUnit\Framework\TestCase;

class ServerStatusDtoTest extends TestCase
{
    /** @test */
    public function it_can_be_created_from_array(): void
    {
        $data = [
            'status' => 'online',
            'version' => '4.5.1',
            'uptime' => 123456,
            'connections' => 42,
            'bandwidth' => [
                'in' => 1234567,
                'out' => 7654321,
            ],
        ];

        $dto = ServerStatusDto::fromArray($data);

        $this->assertInstanceOf(ServerStatusDto::class, $dto);
        $this->assertEquals('online', $dto->status);
        $this->assertEquals('4.5.1', $dto->version);
        $this->assertEquals(123456, $dto->uptime);
        $this->assertEquals(42, $dto->connections);
        $this->assertIsArray($dto->bandwidth);
        $this->assertEquals(1234567, $dto->bandwidth['in']);
    }

    /** @test */
    public function it_can_handle_optional_fields(): void
    {
        $data = [
            'status' => 'online',
        ];

        $dto = ServerStatusDto::fromArray($data);

        $this->assertNull($dto->version);
        $this->assertNull($dto->uptime);
        $this->assertNull($dto->connections);
        $this->assertNull($dto->bandwidth);
    }

    /** @test */
    public function it_can_be_converted_to_array(): void
    {
        $data = [
            'status' => 'online',
            'version' => '4.5.1',
            'uptime' => 123456,
            'connections' => 42,
            'bandwidth' => [
                'in' => 1234567,
                'out' => 7654321,
            ],
        ];

        $dto = ServerStatusDto::fromArray($data);
        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('online', $array['status']);
        $this->assertEquals('4.5.1', $array['version']);
        $this->assertEquals(42, $array['connections']);
    }
}
