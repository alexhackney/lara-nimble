<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\Services\CacheService;
use AlexHackney\LaraNimble\Services\DvrService;
use AlexHackney\LaraNimble\Services\IcecastService;
use AlexHackney\LaraNimble\Services\ProtocolService;
use AlexHackney\LaraNimble\Services\PublishControlService;
use AlexHackney\LaraNimble\Services\RestreamService;
use AlexHackney\LaraNimble\Services\Scte35Service;
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
     * Access the Server service.
     */
    public function server(): ServerService
    {
        return $this->services['server'] ??= new ServerService($this->client);
    }

    /**
     * Access the data cache service.
     */
    public function cache(): CacheService
    {
        return $this->services['cache'] ??= new CacheService($this->client);
    }

    /**
     * Access the Publish Control service.
     */
    public function publishControl(): PublishControlService
    {
        return $this->services['publishControl'] ??= new PublishControlService($this->client);
    }

    /**
     * Access the protocol status/settings service.
     */
    public function protocols(): ProtocolService
    {
        return $this->services['protocols'] ??= new ProtocolService($this->client);
    }

    /**
     * Access the Icecast metadata service.
     */
    public function icecast(): IcecastService
    {
        return $this->services['icecast'] ??= new IcecastService($this->client);
    }

    /**
     * Access the SCTE-35 ad marker service.
     */
    public function scte35(): Scte35Service
    {
        return $this->services['scte35'] ??= new Scte35Service($this->client);
    }
}
