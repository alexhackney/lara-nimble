<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\ServerStatusDto;
use AlexHackney\LaraNimble\Support\RemembersResponses;

/**
 * Service for Nimble server status and configuration management.
 */
class ServerService
{
    use RemembersResponses;

    public function __construct(
        private readonly NimbleClient $client
    ) {}

    /**
     * Get server status and statistics.
     *
     * Served from a short-TTL cache when nimble.cache.enabled is on.
     */
    public function status(): ServerStatusDto
    {
        $data = $this->remember('server_status', function (): array {
            return $this->client->get('/manage/server_status')->data();
        });

        return ServerStatusDto::fromArray($data);
    }

    /**
     * Reload the server configuration without a restart.
     *
     * @param  bool  $drm  Also reload drm.conf
     */
    public function reloadConfig(bool $drm = false): bool
    {
        $response = $this->client->post('/manage/reload_config', [], $drm ? ['drm' => 'true'] : []);

        return $response->successful();
    }

    /**
     * Reload SSL certificates without a restart.
     */
    public function reloadSslCertificates(): bool
    {
        $response = $this->client->post('/manage/reload_ssl_certificates');

        return $response->successful();
    }

    /**
     * Trigger synchronization of settings with WMSPanel.
     */
    public function syncPanelSettings(): bool
    {
        $response = $this->client->post('/manage/sync_panel_settings');

        return $response->successful();
    }

    /**
     * Get the status of server playlists.
     *
     * Returned entries carry stream, block_id/block_name, next block info
     * and stream details; the shape is defined by Nimble.
     */
    public function playlistStatus(): array
    {
        $response = $this->client->get('/manage/server_playlist_status');

        return $response->data();
    }
}
