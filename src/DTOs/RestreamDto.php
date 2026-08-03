<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\DTOs;

use AlexHackney\LaraNimble\Enums\AuthSchema;
use InvalidArgumentException;

/**
 * Data Transfer Object for a Nimble RTMP republishing rule.
 *
 * Maps to the native API at /manage/rtmp/republish. The Nimble API treats
 * src_stream as optional, but omitting it republishes every stream in the
 * source application, so toCreatePayload() refuses to send a rule without it.
 */
class RestreamDto
{
    public readonly ?string $authSchema;

    /**
     * @param  AuthSchema|string|null  $authSchema  One of NONE, NIMBLE, AKAMAI, LIMELIGHT, PERISCOPE
     */
    public function __construct(
        public readonly string $srcApp,
        public readonly string $srcStream,
        public readonly string $destAddr,
        public readonly int $destPort,
        public readonly string $destApp,
        public readonly string $destStream,
        public readonly ?bool $ssl = null,
        AuthSchema|string|null $authSchema = null,
        public readonly ?string $destLogin = null,
        public readonly ?string $destPassword = null,
        public readonly ?bool $keepSrcStreamParams = null,
        public readonly ?string $destAppParams = null,
        public readonly ?string $destStreamParams = null,
        public readonly int|string|null $id = null,
    ) {
        $this->authSchema = $authSchema instanceof AuthSchema ? $authSchema->value : $authSchema;
    }

    /**
     * Create a RestreamDto from an array of data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            srcApp: $data['src_app'],
            srcStream: $data['src_stream'] ?? '',
            destAddr: $data['dest_addr'],
            destPort: (int) $data['dest_port'],
            destApp: $data['dest_app'],
            destStream: $data['dest_stream'],
            ssl: isset($data['ssl']) ? (bool) $data['ssl'] : null,
            authSchema: $data['auth_schema'] ?? null,
            destLogin: $data['dest_login'] ?? null,
            destPassword: $data['dest_password'] ?? null,
            keepSrcStreamParams: isset($data['keep_src_stream_params']) ? (bool) $data['keep_src_stream_params'] : null,
            destAppParams: $data['dest_app_params'] ?? null,
            destStreamParams: $data['dest_stream_params'] ?? null,
            id: $data['id'] ?? null,
        );
    }

    /**
     * Create a RestreamDto from an RTMP(S) publishing URL.
     *
     * Decomposes e.g. rtmps://live-api-s.facebook.com:443/rtmp/{key} into
     * destAddr, destPort, destApp, destStream and ssl. Query strings are kept
     * as part of the stream key, as platforms like Facebook require them.
     *
     * @throws InvalidArgumentException
     */
    public static function fromUrl(
        string $srcApp,
        string $srcStream,
        string $url,
        AuthSchema|string|null $authSchema = null,
        ?string $destLogin = null,
        ?string $destPassword = null,
        ?bool $keepSrcStreamParams = null,
    ): self {
        $parts = parse_url(trim($url));

        if ($parts === false) {
            throw new InvalidArgumentException("Could not parse URL: {$url}");
        }

        $scheme = strtolower($parts['scheme'] ?? '');

        if (! in_array($scheme, ['rtmp', 'rtmps'], true)) {
            throw new InvalidArgumentException("URL must use the rtmp or rtmps scheme, got: {$url}");
        }

        $host = $parts['host'] ?? '';

        if ($host === '') {
            throw new InvalidArgumentException("URL is missing a host: {$url}");
        }

        $path = trim($parts['path'] ?? '', '/');
        $segments = $path === '' ? [] : explode('/', $path);

        if (count($segments) < 2) {
            throw new InvalidArgumentException(
                "URL must contain an application and a stream key (rtmp://host/app/stream), got: {$url}"
            );
        }

        $destStream = array_pop($segments);

        if (isset($parts['query']) && $parts['query'] !== '') {
            $destStream .= '?'.$parts['query'];
        }

        $ssl = $scheme === 'rtmps';

        return new self(
            srcApp: $srcApp,
            srcStream: $srcStream,
            destAddr: $host,
            destPort: $parts['port'] ?? ($ssl ? 443 : 1935),
            destApp: implode('/', $segments),
            destStream: $destStream,
            ssl: $ssl ? true : null,
            authSchema: $authSchema,
            destLogin: $destLogin,
            destPassword: $destPassword,
            keepSrcStreamParams: $keepSrcStreamParams,
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
            'ssl' => $this->ssl,
            'auth_schema' => $this->authSchema,
            'dest_login' => $this->destLogin,
            'dest_password' => $this->destPassword,
            'keep_src_stream_params' => $this->keepSrcStreamParams,
            'dest_app_params' => $this->destAppParams,
            'dest_stream_params' => $this->destStreamParams,
        ];
    }

    /**
     * Build the request body for POST /manage/rtmp/republish.
     *
     * Omits the id and any unset optional fields.
     *
     * @throws InvalidArgumentException when src_stream is empty, because
     *                                  Nimble would republish every stream in the source application
     */
    public function toCreatePayload(): array
    {
        if ($this->srcStream === '') {
            throw new InvalidArgumentException(
                'src_stream must be set: omitting it makes Nimble republish every stream in the source application'
            );
        }

        $payload = $this->toArray();
        unset($payload['id']);

        return array_filter($payload, fn (mixed $value): bool => $value !== null);
    }

    /**
     * Normalized identity of the rule, ignoring the server-assigned id.
     *
     * Unset booleans are treated as false and an unset auth schema as NONE,
     * so a rule built locally matches the same rule as Nimble reports it.
     */
    public function fingerprint(): string
    {
        return implode('|', [
            $this->srcApp,
            $this->srcStream,
            strtolower($this->destAddr),
            (string) $this->destPort,
            $this->destApp,
            $this->destStream,
            ($this->ssl ?? false) ? '1' : '0',
            strtoupper($this->authSchema ?? AuthSchema::NONE->value),
            $this->destLogin ?? '',
            $this->destPassword ?? '',
            ($this->keepSrcStreamParams ?? false) ? '1' : '0',
            $this->destAppParams ?? '',
            $this->destStreamParams ?? '',
        ]);
    }

    /**
     * Whether this rule describes the same republish as another, ignoring ids.
     */
    public function matches(self $other): bool
    {
        return $this->fingerprint() === $other->fingerprint();
    }
}
