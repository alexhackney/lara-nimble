<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\DTOs;

use AlexHackney\LaraNimble\Enums\StreamProtocol;
use AlexHackney\LaraNimble\Enums\StreamStatus;

/**
 * Data Transfer Object for Stream information.
 */
class StreamDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly StreamStatus $status,
        public readonly StreamProtocol $protocol,
        public readonly ?string $source = null,
        public readonly ?int $bitrate = null,
        public readonly ?int $viewers = null,
    ) {}

    /**
     * Create a StreamDto from an array of data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            status: StreamStatus::from($data['status']),
            protocol: StreamProtocol::from($data['protocol']),
            source: $data['source'] ?? null,
            bitrate: $data['bitrate'] ?? null,
            viewers: $data['viewers'] ?? null,
        );
    }

    /**
     * Convert the DTO to an array.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status->value,
            'protocol' => $this->protocol->value,
            'source' => $this->source,
            'bitrate' => $this->bitrate,
            'viewers' => $this->viewers,
        ];
    }
}
