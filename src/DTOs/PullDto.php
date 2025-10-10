<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\DTOs;

/**
 * Data Transfer Object for Pull stream configuration.
 */
class PullDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $sourceUrl,
        public readonly string $localApp,
        public readonly string $localStream,
        public readonly string $protocol,
        public readonly ?bool $enabled = null,
        public readonly ?string $status = null,
    ) {
    }

    /**
     * Create a PullDto from an array of data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            sourceUrl: $data['source_url'],
            localApp: $data['local_app'],
            localStream: $data['local_stream'],
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
            'source_url' => $this->sourceUrl,
            'local_app' => $this->localApp,
            'local_stream' => $this->localStream,
            'protocol' => $this->protocol,
            'enabled' => $this->enabled,
            'status' => $this->status,
        ];
    }
}
