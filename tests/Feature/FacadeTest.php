<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Feature;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\Facades\Nimble as NimbleFacade;
use AlexHackney\LaraNimble\Nimble;
use AlexHackney\LaraNimble\Tests\TestCase;

class FacadeTest extends TestCase
{
    /** @test */
    public function it_resolves_to_nimble_manager_instance(): void
    {
        $instance = NimbleFacade::getFacadeRoot();

        $this->assertInstanceOf(Nimble::class, $instance);
    }

    /** @test */
    public function it_can_access_nimble_client_through_facade(): void
    {
        $client = NimbleFacade::client();

        $this->assertInstanceOf(NimbleClient::class, $client);
    }

    /** @test */
    public function facade_returns_same_instance_on_multiple_calls(): void
    {
        $instance1 = NimbleFacade::getFacadeRoot();
        $instance2 = NimbleFacade::getFacadeRoot();

        $this->assertSame($instance1, $instance2);
    }

    /** @test */
    public function it_can_get_base_url_through_facade(): void
    {
        $baseUrl = NimbleFacade::getBaseUrl();

        $this->assertEquals('http://localhost:8082', $baseUrl);
    }
}
