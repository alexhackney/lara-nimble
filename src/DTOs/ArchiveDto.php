<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\DTOs;

/**
 * Data Transfer Object for DVR Archive information.
 */
class ArchiveDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $streamId,
        public readonly string $filename,
        public readonly ?int $size = null,
        public readonly ?int $duration = null,
        public readonly ?string $startTime = null,
        public readonly ?string $endTime = null,
        public readonly ?string $path = null,
    ) {
    }

    /**
     * Create an ArchiveDto from an array of data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            streamId: $data['stream_id'],
            filename: $data['filename'],
            size: $data['size'] ?? null,
            duration: $data['duration'] ?? null,
            startTime: $data['start_time'] ?? null,
            endTime: $data['end_time'] ?? null,
            path: $data['path'] ?? null,
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
            'filename' => $this->filename,
            'size' => $this->size,
            'duration' => $this->duration,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'path' => $this->path,
        ];
    }
}
