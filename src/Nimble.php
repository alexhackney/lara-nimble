<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\Services\CacheService;
use AlexHackney\LaraNimble\Services\DvrService;
use AlexHackney\LaraNimble\Services\PullService;
use AlexHackney\LaraNimble\Services\RestreamService;
use AlexHackney\LaraNimble\Services\ServerService;
use AlexHackney\LaraNimble\Services\SessionService;
use AlexHackney\LaraNimble\Services\StreamService;

/**
 * Main Nimble Manager class that provides access to the Nimble API.
 */
class Nimble
{
    /**
     * Cached service instances.
     */
    private array $services = [];

    public function __construct(
        private readonly NimbleClient $client
    ) {}

    /**
     * Get the underlying Nimble HTTP client.
     */
    public function client(): NimbleClient
    {
        return $this->client;
    }

    /**
     * Get the base URL of the Nimble server.
     */
    public function getBaseUrl(): string
    {
        return $this->client->getBaseUrl();
    }

    /**
     * Access the Stream service.
     */
    public function streams(): StreamService
    {
        return $this->services['streams'] ??= new StreamService($this->client);
    }

    /**
     * Access the Session service.
     */
    public function sessions(): SessionService
    {
        return $this->services['sessions'] ??= new SessionService($this->client);
    }

    /**
     * Access the DVR service.
     */
    public function dvr(): DvrService
    {
        return $this->services['dvr'] ??= new DvrService($this->client);
    }

    /**
     * Access the Restream service.
     */
    public function restream(): RestreamService
    {
        return $this->services['restream'] ??= new RestreamService($this->client);
    }

    /**
     * Access the Pull service.
     */
    public function pull(): PullService
    {
        return $this->services['pull'] ??= new PullService($this->client);
    }

    /**
     * Access the Server service.
     */
    public function server(): ServerService
    {
        return $this->services['server'] ??= new ServerService($this->client);
    }

    /**
     * Access the Cache service.
     */
    public function cache(): CacheService
    {
        return $this->services['cache'] ??= new CacheService($this->client);
    }
}
