<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\DTOs;

use AlexHackney\LaraNimble\DTOs\ServerStatusDto;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ServerStatusDtoTest extends TestCase
{
    #[Test]
    public function it_can_be_created_from_array(): void
    {
        $dto = ServerStatusDto::fromArray([
            'Connections' => 10,
            'OutRate' => 5120000,
            'SysInfo' => ['ap' => 2, 'scl' => 0, 'tpms' => 2098434048, 'fpms' => 775127040],
            'RamCacheSize' => 1024,
            'FileCacheSize' => 2048,
            'MaxRamCacheSize' => 4096,
            'MaxFileCacheSize' => 8192,
        ]);

        $this->assertSame(10, $dto->connections);
        $this->assertSame(5120000, $dto->outRate);
        $this->assertSame(1024, $dto->ramCacheSize);
        $this->assertSame(2048, $dto->fileCacheSize);
        $this->assertSame(4096, $dto->maxRamCacheSize);
        $this->assertSame(8192, $dto->maxFileCacheSize);
        $this->assertSame(775127040, $dto->sysInfo['fpms']);
    }

    #[Test]
    public function it_tolerates_missing_fields(): void
    {
        $dto = ServerStatusDto::fromArray([]);

        $this->assertNull($dto->connections);
        $this->assertNull($dto->outRate);
        $this->assertNull($dto->ramCacheSize);
        $this->assertSame([], $dto->sysInfo);
    }

    #[Test]
    public function it_can_be_converted_to_array_with_wire_field_names(): void
    {
        $data = [
            'Connections' => 10,
            'OutRate' => 5120000,
            'RamCacheSize' => 1024,
            'FileCacheSize' => 2048,
            'MaxRamCacheSize' => 4096,
            'MaxFileCacheSize' => 8192,
            'SysInfo' => ['ap' => 2],
        ];

        $this->assertEquals($data, ServerStatusDto::fromArray($data)->toArray());
    }
}
