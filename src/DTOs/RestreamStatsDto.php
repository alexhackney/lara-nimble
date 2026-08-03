<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\DTOs;

/**
 * Data Transfer Object for a Nimble RTMP republishing stats entry.
 *
 * Entries from GET /manage/rtmp/republish/stats contain the rule fields
 * plus connection statistics. All fields are nullable because Nimble only
 * includes what applies to the rule's current state.
 */
class RestreamStatsDto
{
    public function __construct(
        public readonly int|string|null $id = null,
        public readonly ?string $srcApp = null,
        public readonly ?string $srcStream = null,
        public readonly ?string $destAddr = null,
        public readonly ?int $destPort = null,
        public readonly ?string $destApp = null,
        public readonly ?string $destStream = null,
        public readonly ?string $state = null,
        public readonly ?int $sessionDuration = null,
        public readonly ?int $bandwidth = null,
        public readonly ?int $bytesRecv = null,
        public readonly ?int $bytesSent = null,
        public readonly ?int $retryCount = null,
    ) {}

    /**
     * Create a RestreamStatsDto from an array of data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            srcApp: $data['src_app'] ?? null,
            srcStream: $data['src_stream'] ?? null,
            destAddr: $data['dest_addr'] ?? null,
            destPort: isset($data['dest_port']) ? (int) $data['dest_port'] : null,
            destApp: $data['dest_app'] ?? null,
            destStream: $data['dest_stream'] ?? null,
            state: $data['state'] ?? null,
            sessionDuration: isset($data['session_duration']) ? (int) $data['session_duration'] : null,
            bandwidth: isset($data['bandwidth']) ? (int) $data['bandwidth'] : null,
            bytesRecv: isset($data['bytes_recv']) ? (int) $data['bytes_recv'] : null,
            bytesSent: isset($data['bytes_sent']) ? (int) $data['bytes_sent'] : null,
            retryCount: isset($data['retry_count']) ? (int) $data['retry_count'] : null,
        );
    }

    /**
     * Convert the DTO to an array.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'src_app' => $this->srcApp,
            'src_stream' => $this->srcStream,
            'dest_addr' => $this->destAddr,
            'dest_port' => $this->destPort,
            'dest_app' => $this->destApp,
            'dest_stream' => $this->destStream,
            'state' => $this->state,
            'session_duration' => $this->sessionDuration,
            'bandwidth' => $this->bandwidth,
            'bytes_recv' => $this->bytesRecv,
            'bytes_sent' => $this->bytesSent,
            'retry_count' => $this->retryCount,
        ];
    }
}
