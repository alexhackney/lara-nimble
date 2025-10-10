<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\ArchiveDto;
use Illuminate\Support\Collection;

/**
 * Service for managing Nimble DVR archives.
 */
class DvrService
{
    public function __construct(
        private readonly NimbleClient $client
    ) {
    }

    /**
     * List all DVR archives.
     *
     * @return Collection<int, ArchiveDto>
     */
    public function listArchives(): Collection
    {
        $response = $this->client->get('/manage/dvr/archives');

        $archives = $response->get('archives', []);

        return collect($archives)->map(function (array $archiveData) {
            return ArchiveDto::fromArray($archiveData);
        });
    }

    /**
     * Get details of a specific archive.
     */
    public function getArchive(string $archiveId): ArchiveDto
    {
        $response = $this->client->get("/manage/dvr/archive/{$archiveId}");

        return ArchiveDto::fromArray($response->data());
    }

    /**
     * Delete a specific archive.
     */
    public function deleteArchive(string $archiveId): bool
    {
        $response = $this->client->delete("/manage/dvr/archive/{$archiveId}");

        return $response->get('success', false) === true;
    }

    /**
     * Configure DVR settings.
     */
    public function configure(array $settings): bool
    {
        $response = $this->client->post('/manage/dvr/configure', $settings);

        return $response->get('success', false) === true;
    }
}
