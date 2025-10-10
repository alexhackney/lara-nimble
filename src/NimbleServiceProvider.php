<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\Commands\NimbleCacheClearCommand;
use AlexHackney\LaraNimble\Commands\NimbleHealthCommand;
use AlexHackney\LaraNimble\Commands\NimbleStatusCommand;
use AlexHackney\LaraNimble\Commands\NimbleStreamsCommand;
use Illuminate\Support\ServiceProvider;

/**
 * Laravel Service Provider for the Nimble package.
 */
class NimbleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/nimble.php',
            'nimble'
        );

        $this->app->singleton(NimbleClient::class, function ($app) {
            return new NimbleClient([
                'host' => config('nimble.host'),
                'port' => config('nimble.port'),
                'protocol' => config('nimble.protocol'),
                'token' => config('nimble.token'),
                'timeout' => config('nimble.timeout'),
                'connect_timeout' => config('nimble.connect_timeout'),
                'retry_times' => config('nimble.retry_times'),
                'retry_sleep' => config('nimble.retry_sleep'),
                'log_requests' => config('nimble.log_requests'),
                'log_channel' => config('nimble.log_channel'),
                'verify_ssl' => config('nimble.verify_ssl'),
            ]);
        });

        $this->app->singleton(Nimble::class, function ($app) {
            return new Nimble($app->make(NimbleClient::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/nimble.php' => config_path('nimble.php'),
            ], 'nimble-config');

            $this->commands([
                NimbleStatusCommand::class,
                NimbleStreamsCommand::class,
                NimbleCacheClearCommand::class,
                NimbleHealthCommand::class,
            ]);
        }
    }
}
