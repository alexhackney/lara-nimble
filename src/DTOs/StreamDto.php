<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\DTOs;

/**
 * Data Transfer Object for a live stream entry from
 * GET /manage/live_streams_status.
 *
 * Nimble groups streams by application; StreamService flattens the groups
 * and injects the application name under the 'app' key.
 */
class StreamDto
{
    public function __construct(
        public readonly string $app,
        public readonly string $stream,
        public readonly ?int $bandwidth = null,
        public readonly ?string $resolution = null,
        public readonly ?string $vcodec = null,
        public readonly ?string $acodec = null,
        public readonly ?string $protocol = null,
        public readonly ?string $sourceUrl = null,
        public readonly ?string $publisherIp = null,
        public readonly ?int $publisherPort = null,
        public readonly ?int $publishTime = null,
    ) {}

    /**
     * Create a StreamDto from an array of data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            app: $data['app'] ?? '',
            stream: $data['strm'] ?? $data['stream'] ?? '',
            bandwidth: isset($data['bandwidth']) ? (int) $data['bandwidth'] : null,
            resolution: $data['resolution'] ?? null,
            vcodec: $data['vcodec'] ?? null,
            acodec: $data['acodec'] ?? null,
            protocol: $data['protocol'] ?? null,
            sourceUrl: $data['source_url'] ?? null,
            publisherIp: $data['publisher_ip'] ?? null,
            publisherPort: isset($data['publisher_port']) ? (int) $data['publisher_port'] : null,
            publishTime: isset($data['publish_time']) ? (int) $data['publish_time'] : null,
        );
    }

    /**
     * Convert the DTO to an array using Nimble's wire field names.
     */
    public function toArray(): array
    {
        return [
            'app' => $this->app,
            'strm' => $this->stream,
            'bandwidth' => $this->bandwidth,
            'resolution' => $this->resolution,
            'vcodec' => $this->vcodec,
            'acodec' => $this->acodec,
            'protocol' => $this->protocol,
            'source_url' => $this->sourceUrl,
            'publisher_ip' => $this->publisherIp,
            'publisher_port' => $this->publisherPort,
            'publish_time' => $this->publishTime,
        ];
    }
}
