<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\DTOs;

/**
 * Data Transfer Object for an active publisher entry from
 * GET /manage/publish_control/status.
 */
class PublishControlEntryDto
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $key = null,
        public readonly ?string $ip = null,
        public readonly ?string $stream = null,
    ) {}

    /**
     * Create a PublishControlEntryDto from an array of data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            key: isset($data['key']) ? (string) $data['key'] : null,
            ip: $data['ip'] ?? null,
            stream: $data['stream'] ?? null,
        );
    }

    /**
     * Convert the DTO to an array using Nimble's wire field names.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'ip' => $this->ip,
            'stream' => $this->stream,
        ];
    }
}
