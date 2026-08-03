<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\DTOs;

/**
 * Data Transfer Object for a session entry from GET /manage/sessions.
 */
class SessionDto
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $app = null,
        public readonly ?string $stream = null,
        public readonly ?string $type = null,
        public readonly ?int $created = null,
        public readonly ?int $lastAccess = null,
        public readonly ?string $clientIp = null,
        public readonly ?string $userAgent = null,
        public readonly ?int $bytesRecv = null,
        public readonly ?int $bytesSent = null,
        public readonly ?string $ppvId = null,
    ) {}

    /**
     * Create a SessionDto from an array of data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            app: $data['app'] ?? null,
            stream: $data['stream'] ?? null,
            type: $data['type'] ?? null,
            created: isset($data['created']) ? (int) $data['created'] : null,
            lastAccess: isset($data['last_access']) ? (int) $data['last_access'] : null,
            clientIp: $data['client_ip'] ?? null,
            userAgent: $data['user_agent'] ?? null,
            bytesRecv: isset($data['bytes_recv']) ? (int) $data['bytes_recv'] : null,
            bytesSent: isset($data['bytes_sent']) ? (int) $data['bytes_sent'] : null,
            ppvId: isset($data['ppv_id']) ? (string) $data['ppv_id'] : null,
        );
    }

    /**
     * Convert the DTO to an array using Nimble's wire field names.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'app' => $this->app,
            'stream' => $this->stream,
            'type' => $this->type,
            'created' => $this->created,
            'last_access' => $this->lastAccess,
            'client_ip' => $this->clientIp,
            'user_agent' => $this->userAgent,
            'bytes_recv' => $this->bytesRecv,
            'bytes_sent' => $this->bytesSent,
            'ppv_id' => $this->ppvId,
        ];
    }
}
