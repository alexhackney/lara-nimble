<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Feature;

use AlexHackney\LaraNimble\Facades\Nimble as NimbleFacade;
use AlexHackney\LaraNimble\Nimble;
use AlexHackney\LaraNimble\Services\StreamService;
use AlexHackney\LaraNimble\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class StreamServiceIntegrationTest extends TestCase
{
    #[Test]
    public function it_can_access_stream_service_through_nimble_manager(): void
    {
        $nimble = $this->app->make(Nimble::class);
        $streamService = $nimble->streams();

        $this->assertInstanceOf(StreamService::class, $streamService);
    }

    #[Test]
    public function it_can_access_stream_service_through_facade(): void
    {
        $streamService = NimbleFacade::streams();

        $this->assertInstanceOf(StreamService::class, $streamService);
    }

    #[Test]
    public function stream_service_has_access_to_configured_client(): void
    {
        $streamService = NimbleFacade::streams();

        // Verify it can access the configured client by checking base URL
        $baseUrl = NimbleFacade::getBaseUrl();

        $this->assertEquals('http://localhost:8082', $baseUrl);
    }
}
