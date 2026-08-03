<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\SessionDto;
use AlexHackney\LaraNimble\Events\SessionTerminated;
use Illuminate\Support\Collection;

/**
 * Service for managing Nimble client sessions.
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
        $sessions = $response->data();

        return collect($sessions)->map(function (array $sessionData) {
            return SessionDto::fromArray($sessionData);
        });
    }

    /**
     * Find a session by id, or null when it does not exist.
     */
    public function find(int $sessionId): ?SessionDto
    {
        return $this->list()->first(
            fn (SessionDto $session) => $session->id === $sessionId
        );
    }

    /**
     * Terminate one or more sessions by id.
     *
     * @param  int|array<int, int>  $sessionIds
     */
    public function terminate(int|array $sessionIds): bool
    {
        $ids = array_values(array_map(intval(...), (array) $sessionIds));

        if ($ids === []) {
            return false;
        }

        $response = $this->client->post('/manage/sessions/delete', $ids);

        $success = strcasecmp((string) $response->get('status', ''), 'ok') === 0;

        if ($success) {
            foreach ($ids as $id) {
                try {
                    event(new SessionTerminated($id));
                } catch (\Throwable) {
                    // Event dispatcher not available (e.g., in unit tests)
                }
            }
        }

        return $success;
    }
}
