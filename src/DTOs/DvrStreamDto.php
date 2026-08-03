<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\DTOs;

/**
 * Data Transfer Object for a DVR archive entry from GET /manage/dvr_status.
 *
 * The timeline array (period entries with start/end/duration/period) is
 * only present when dvr_status is requested with timeline=true.
 */
class DvrStreamDto
{
    public function __construct(
        public readonly string $stream,
        public readonly ?int $size = null,
        public readonly ?int $duration = null,
        public readonly ?int $periods = null,
        public readonly ?string $path = null,
        public readonly ?int $spaceAvailable = null,
        public readonly ?string $vcodec = null,
        public readonly ?string $acodec = null,
        public readonly ?string $resolution = null,
        public readonly ?int $bandwidth = null,
        public readonly array $timeline = [],
    ) {}

    /**
     * Create a DvrStreamDto from an array of data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            stream: $data['stream'] ?? '',
            size: isset($data['size']) ? (int) $data['size'] : null,
            duration: isset($data['duration']) ? (int) $data['duration'] : null,
            periods: isset($data['periods']) ? (int) $data['periods'] : null,
            path: $data['path'] ?? null,
            spaceAvailable: isset($data['space_available']) ? (int) $data['space_available'] : null,
            vcodec: $data['vcodec'] ?? null,
            acodec: $data['acodec'] ?? null,
            resolution: $data['resolution'] ?? null,
            bandwidth: isset($data['bandwidth']) ? (int) $data['bandwidth'] : null,
            timeline: $data['timeline'] ?? [],
        );
    }

    /**
     * Convert the DTO to an array using Nimble's wire field names.
     */
    public function toArray(): array
    {
        return [
            'stream' => $this->stream,
            'size' => $this->size,
            'duration' => $this->duration,
            'periods' => $this->periods,
            'path' => $this->path,
            'space_available' => $this->spaceAvailable,
            'vcodec' => $this->vcodec,
            'acodec' => $this->acodec,
            'resolution' => $this->resolution,
            'bandwidth' => $this->bandwidth,
            'timeline' => $this->timeline,
        ];
    }
}
