<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\PublishControlEntryDto;
use Illuminate\Support\Collection;

/**
 * Service for Nimble's publish control API (/manage/publish_control).
 *
 * Requires publish control to be enabled in Nimble's RTMP settings;
 * publishers are then identified by the ids listed in status().
 */
class PublishControlService
{
    public function __construct(
        private readonly NimbleClient $client
    ) {}

    /**
     * List the currently active publishers.
     *
     * @return Collection<int, PublishControlEntryDto>
     */
    public function status(): Collection
    {
        $response = $this->client->get('/manage/publish_control/status');

        /** @var array<int, array<string, mixed>> $entries */
        $entries = $response->get('PublishControlStatus', []);

        return collect($entries)->map(function (array $entry) {
            return PublishControlEntryDto::fromArray($entry);
        });
    }

    /**
     * Deny (disconnect) publishers by id.
     *
     * @param  string|array<int, string>  $publisherIds
     */
    public function deny(string|array $publisherIds): bool
    {
        $ids = array_values((array) $publisherIds);

        if ($ids === []) {
            return false;
        }

        $response = $this->client->post('/manage/publish_control/deny', [
            'PublishControlDenyRequest' => $ids,
        ]);

        $status = (string) $response->get('PublishControlDenyResponse.status', '');

        return strcasecmp($status, 'success') === 0 || strcasecmp($status, 'ok') === 0;
    }
}
