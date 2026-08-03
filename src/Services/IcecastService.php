<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;

/**
 * Service for Icecast stream metadata (/manage/icecast_*).
 */
class IcecastService
{
    public function __construct(
        private readonly NimbleClient $client
    ) {}

    /**
     * Get Icecast metadata for a stream (icy-name, icy-genre,
     * streamtitle, ...).
     */
    public function info(string $app, string $stream): array
    {
        $response = $this->client->get("/manage/icecast_stream_info/{$app}/{$stream}");

        return $response->data();
    }

    /**
     * Update the current Icecast metadata of a stream.
     */
    public function updateMetadata(string $app, string $stream, string $title, ?string $url = null): bool
    {
        $payload = ['streamtitle' => $title];

        if ($url !== null) {
            $payload['streamurl'] = $url;
        }

        $response = $this->client->post("/manage/icecast_metadata/{$app}/{$stream}", $payload);

        return strcasecmp((string) $response->get('status', ''), 'ok') === 0;
    }
}
