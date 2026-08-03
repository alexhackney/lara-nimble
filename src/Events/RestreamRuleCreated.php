<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Events;

use AlexHackney\LaraNimble\DTOs\RestreamDto;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RestreamRuleCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly RestreamDto $rule,
    ) {}
}
