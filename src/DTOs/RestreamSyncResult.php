<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\DTOs;

use Illuminate\Support\Collection;

/**
 * Result of RestreamService::sync(): which republishing rules were
 * created, deleted, and kept to converge on the desired set.
 */
class RestreamSyncResult
{
    /**
     * @param  Collection<int, RestreamDto>  $created
     * @param  Collection<int, RestreamDto>  $deleted
     * @param  Collection<int, RestreamDto>  $kept
     */
    public function __construct(
        public readonly Collection $created,
        public readonly Collection $deleted,
        public readonly Collection $kept,
    ) {}

    /**
     * Whether the sync changed anything on the server.
     */
    public function changed(): bool
    {
        return $this->created->isNotEmpty() || $this->deleted->isNotEmpty();
    }

    /**
     * Convert the result to an array.
     */
    public function toArray(): array
    {
        return [
            'created' => $this->created->map(fn (RestreamDto $rule) => $rule->toArray())->all(),
            'deleted' => $this->deleted->map(fn (RestreamDto $rule) => $rule->toArray())->all(),
            'kept' => $this->kept->map(fn (RestreamDto $rule) => $rule->toArray())->all(),
        ];
    }
}
