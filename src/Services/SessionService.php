<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\SessionDto;
use AlexHackney\LaraNimble\Events\SessionTerminated;
use Illuminate\Support\Collection;

/**
 * Service for managing Nimble sessions.
 */
class SessionService
{
    public function __construct(
        private readonly NimbleClient $client
    ) {}

    /**
     * List all active sessions.
     *
     * @return Collection<int, SessionDto>
     */
    public function list(): Collection
    {
        $response = $this->client->get('/manage/sessions');

        /** @var array<int, array<string, mixed>> $sessions */
        $sessions = $response->get('sessions', []);

        return collect($sessions)->map(function (array $sessionData) {
            return SessionDto::fromArray($sessionData);
        });
    }

    /**
     * Get details of a specific session.
     */
    public function get(string $sessionId): SessionDto
    {
        $response = $this->client->get("/manage/session/{$sessionId}");

        return SessionDto::fromArray($response->data());
    }

    /**
     * Terminate a specific session.
     */
    public function terminate(string $sessionId): bool
    {
        $response = $this->client->delete("/manage/session/{$sessionId}");

        $success = $response->get('success', false) === true;

        if ($success) {
            try {
                event(new SessionTerminated($sessionId));
            } catch (\Throwable $e) {
                // Event dispatcher not available (e.g., in unit tests)
            }
        }

        return $success;
    }

    /**
     * Get session statistics.
     */
    public function statistics(string $sessionId): array
    {
        $response = $this->client->get("/manage/session/{$sessionId}/stats");

        return $response->data();
    }
}
