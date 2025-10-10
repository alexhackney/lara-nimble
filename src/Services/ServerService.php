<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\ServerStatusDto;

/**
 * Service for managing Nimble server.
 */
class ServerService
{
    public function __construct(
        private readonly NimbleClient $client
    ) {}

    /**
     * Get server status and statistics.
     */
    public function status(): ServerStatusDto
    {
        $response = $this->client->get('/manage/status');

        return ServerStatusDto::fromArray($response->data());
    }

    /**
     * Reload server configuration without restart.
     */
    public function reload(): bool
    {
        $response = $this->client->post('/manage/reload');

        return $response->get('success', false) === true;
    }

    /**
     * Trigger synchronization with WMSPanel.
     */
    public function sync(): bool
    {
        $response = $this->client->post('/manage/sync');

        return $response->get('success', false) === true;
    }
}
