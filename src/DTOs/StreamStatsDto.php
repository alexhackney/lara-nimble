<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\DTOs;

/**
 * Data Transfer Object for real-time Stream statistics.
 */
class StreamStatsDto
{
    public function __construct(
        public readonly string $streamName,
        public readonly ?string $application = null,
        public readonly ?int $bandwidth = null,
        public readonly ?string $resolution = null,
        public readonly ?string $videoCodec = null,
        public readonly ?string $audioCodec = null,
        public readonly ?string $protocol = null,
        public readonly ?string $sourceUrl = null,
        public readonly ?string $publisherIp = null,
        public readonly ?int $publisherPort = null,
        public readonly ?int $viewers = null,
        public readonly ?float $fps = null,
        public readonly ?int $bitrate = null,
        public readonly ?int $duration = null,
        public readonly ?string $startTime = null,
    ) {
    }

    /**
     * Create a StreamStatsDto from an array of data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            streamName: $data['stream_name'] ?? $data['name'] ?? 'unknown',
            application: $data['application'] ?? $data['app'] ?? null,
            bandwidth: isset($data['bandwidth']) ? (int) $data['bandwidth'] : null,
            resolution: $data['resolution'] ?? null,
            videoCodec: $data['vcodec'] ?? $data['video_codec'] ?? null,
            audioCodec: $data['acodec'] ?? $data['audio_codec'] ?? null,
            protocol: $data['protocol'] ?? null,
            sourceUrl: $data['source_url'] ?? null,
            publisherIp: $data['publisher_ip'] ?? null,
            publisherPort: isset($data['publisher_port']) ? (int) $data['publisher_port'] : null,
            viewers: isset($data['viewers']) ? (int) $data['viewers'] : null,
            fps: isset($data['fps']) ? (float) $data['fps'] : null,
            bitrate: isset($data['bitrate']) ? (int) $data['bitrate'] : null,
            duration: isset($data['duration']) ? (int) $data['duration'] : null,
            startTime: $data['start_time'] ?? $data['started_at'] ?? null,
        );
    }

    /**
     * Convert the DTO to an array.
     */
    public function toArray(): array
    {
        return [
            'stream_name' => $this->streamName,
            'application' => $this->application,
            'bandwidth' => $this->bandwidth,
            'resolution' => $this->resolution,
            'video_codec' => $this->videoCodec,
            'audio_codec' => $this->audioCodec,
            'protocol' => $this->protocol,
            'source_url' => $this->sourceUrl,
            'publisher_ip' => $this->publisherIp,
            'publisher_port' => $this->publisherPort,
            'viewers' => $this->viewers,
            'fps' => $this->fps,
            'bitrate' => $this->bitrate,
            'duration' => $this->duration,
            'start_time' => $this->startTime,
        ];
    }
}
