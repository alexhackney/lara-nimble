<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Integration;

use AlexHackney\LaraNimble\DTOs\DvrStreamDto;
use AlexHackney\LaraNimble\DTOs\ServerStatusDto;
use AlexHackney\LaraNimble\DTOs\SessionDto;
use AlexHackney\LaraNimble\DTOs\StreamDto;
use AlexHackney\LaraNimble\Services\DvrService;
use AlexHackney\LaraNimble\Services\ServerService;
use AlexHackney\LaraNimble\Services\SessionService;
use AlexHackney\LaraNimble\Services\StreamService;
use PHPUnit\Framework\Attributes\Test;

/**
 * Read-only checks against a real Nimble server.
 */
class ServerIntegrationTest extends IntegrationTestCase
{
    #[Test]
    public function it_fetches_server_status(): void
    {
        $status = (new ServerService($this->client))->status();

        $this->assertInstanceOf(ServerStatusDto::class, $status);
        // A live server always reports a connection count
        $this->assertNotNull($status->connections);
    }

    #[Test]
    public function it_lists_live_streams(): void
    {
        $streams = (new StreamService($this->client))->list();

        $this->assertContainsOnlyInstancesOf(StreamDto::class, $streams);
    }

    #[Test]
    public function it_lists_sessions(): void
    {
        $sessions = (new SessionService($this->client))->list();

        $this->assertContainsOnlyInstancesOf(SessionDto::class, $sessions);
    }

    #[Test]
    public function it_fetches_dvr_status(): void
    {
        $archives = (new DvrService($this->client))->status();

        $this->assertContainsOnlyInstancesOf(DvrStreamDto::class, $archives);
    }
}
