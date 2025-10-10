<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\DTOs;

/**
 * Data Transfer Object for Restream target information.
 */
class RestreamDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $streamId,
        public readonly string $targetUrl,
        public readonly string $protocol,
        public readonly ?bool $enabled = null,
        public readonly ?string $status = null,
    ) {}

    /**
     * Create a RestreamDto from an array of data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            streamId: $data['stream_id'],
            targetUrl: $data['target_url'],
            protocol: $data['protocol'],
            enabled: $data['enabled'] ?? null,
            status: $data['status'] ?? null,
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
            'target_url' => $this->targetUrl,
            'protocol' => $this->protocol,
            'enabled' => $this->enabled,
            'status' => $this->status,
        ];
    }
}
