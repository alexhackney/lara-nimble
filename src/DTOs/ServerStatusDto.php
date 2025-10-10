<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\DTOs;

/**
 * Data Transfer Object for Server status information.
 */
class ServerStatusDto
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $version = null,
        public readonly ?int $uptime = null,
        public readonly ?int $connections = null,
        public readonly ?array $bandwidth = null,
    ) {}

    /**
     * Create a ServerStatusDto from an array of data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'],
            version: $data['version'] ?? null,
            uptime: $data['uptime'] ?? null,
            connections: $data['connections'] ?? null,
            bandwidth: $data['bandwidth'] ?? null,
        );
    }

    /**
     * Convert the DTO to an array.
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'version' => $this->version,
            'uptime' => $this->uptime,
            'connections' => $this->connections,
            'bandwidth' => $this->bandwidth,
        ];
    }
}
