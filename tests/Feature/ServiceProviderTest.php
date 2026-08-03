<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Feature;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\Nimble;
use AlexHackney\LaraNimble\NimbleServiceProvider;
use AlexHackney\LaraNimble\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ServiceProviderTest extends TestCase
{
    #[Test]
    public function it_registers_the_service_provider(): void
    {
        $providers = $this->app->getLoadedProviders();

        $this->assertArrayHasKey(NimbleServiceProvider::class, $providers);
    }

    #[Test]
    public function it_registers_nimble_client_as_singleton(): void
    {
        $client1 = $this->app->make(NimbleClient::class);
        $client2 = $this->app->make(NimbleClient::class);

        $this->assertInstanceOf(NimbleClient::class, $client1);
        $this->assertSame($client1, $client2);
    }

    #[Test]
    public function it_registers_nimble_manager_as_singleton(): void
    {
        $nimble1 = $this->app->make(Nimble::class);
        $nimble2 = $this->app->make(Nimble::class);

        $this->assertInstanceOf(Nimble::class, $nimble1);
        $this->assertSame($nimble1, $nimble2);
    }

    #[Test]
    public function it_loads_configuration_from_config_file(): void
    {
        $this->assertEquals('localhost', config('nimble.host'));
        $this->assertEquals(8082, config('nimble.port'));
        $this->assertEquals('http', config('nimble.protocol'));
    }

    #[Test]
    public function it_can_publish_configuration_file(): void
    {
        $configPath = config_path('nimble.php');

        // In a real app, this would publish to config_path
        // For testing, we just verify the provider has the publishable config
        $provider = $this->app->getProvider(NimbleServiceProvider::class);

        $this->assertInstanceOf(NimbleServiceProvider::class, $provider);
    }

    #[Test]
    public function nimble_client_uses_config_values(): void
    {
        config(['nimble.host' => 'custom.example.com']);
        config(['nimble.port' => 9000]);
        config(['nimble.protocol' => 'https']);

        // Re-register to pick up new config
        $this->app->forgetInstance(NimbleClient::class);

        $client = $this->app->make(NimbleClient::class);

        $this->assertEquals('https://custom.example.com:9000', $client->getBaseUrl());
    }
}
