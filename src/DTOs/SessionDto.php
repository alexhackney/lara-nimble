<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\DTOs;

/**
 * Data Transfer Object for Session information.
 */
class SessionDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $streamId,
        public readonly string $clientIp,
        public readonly string $protocol,
        public readonly ?string $startedAt = null,
        public readonly ?int $duration = null,
        public readonly ?int $bytesTransferred = null,
    ) {}

    /**
     * Create a SessionDto from an array of data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            streamId: $data['stream_id'],
            clientIp: $data['client_ip'],
            protocol: $data['protocol'],
            startedAt: $data['started_at'] ?? null,
            duration: $data['duration'] ?? null,
            bytesTransferred: $data['bytes_transferred'] ?? null,
        );
    }

    /**
     * Convert the DTO to an array.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'stream_id' => $this->streamId,
            'client_ip' => $this->clientIp,
            'protocol' => $this->protocol,
            'started_at' => $this->startedAt,
            'duration' => $this->duration,
            'bytes_transferred' => $this->bytesTransferred,
        ];
    }
}
