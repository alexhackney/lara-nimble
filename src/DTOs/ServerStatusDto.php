<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\DTOs;

/**
 * Data Transfer Object for GET /manage/server_status.
 *
 * SysInfo is kept as the raw array Nimble returns; its abbreviated keys
 * (ap, scl, tpms, fpms, tsss, fsss) are not documented by Softvelum.
 */
class ServerStatusDto
{
    public function __construct(
        public readonly ?int $connections = null,
        public readonly ?int $outRate = null,
        public readonly ?int $ramCacheSize = null,
        public readonly ?int $fileCacheSize = null,
        public readonly ?int $maxRamCacheSize = null,
        public readonly ?int $maxFileCacheSize = null,
        public readonly array $sysInfo = [],
    ) {}

    /**
     * Create a ServerStatusDto from an array of data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            connections: isset($data['Connections']) ? (int) $data['Connections'] : null,
            outRate: isset($data['OutRate']) ? (int) $data['OutRate'] : null,
            ramCacheSize: isset($data['RamCacheSize']) ? (int) $data['RamCacheSize'] : null,
            fileCacheSize: isset($data['FileCacheSize']) ? (int) $data['FileCacheSize'] : null,
            maxRamCacheSize: isset($data['MaxRamCacheSize']) ? (int) $data['MaxRamCacheSize'] : null,
            maxFileCacheSize: isset($data['MaxFileCacheSize']) ? (int) $data['MaxFileCacheSize'] : null,
            sysInfo: $data['SysInfo'] ?? [],
        );
    }

    /**
     * Convert the DTO to an array using Nimble's wire field names.
     */
    public function toArray(): array
    {
        return [
            'Connections' => $this->connections,
            'OutRate' => $this->outRate,
            'RamCacheSize' => $this->ramCacheSize,
            'FileCacheSize' => $this->fileCacheSize,
            'MaxRamCacheSize' => $this->maxRamCacheSize,
            'MaxFileCacheSize' => $this->maxFileCacheSize,
            'SysInfo' => $this->sysInfo,
        ];
    }
}
