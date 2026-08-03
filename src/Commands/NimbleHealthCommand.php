<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Commands;

use AlexHackney\LaraNimble\Facades\Nimble;
use Illuminate\Console\Command;

class NimbleHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'nimble:health';

    /**
     * The console command description.
     */
    protected $description = 'Perform comprehensive health check of Nimble server';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Performing Nimble server health check...');
        $this->newLine();

        // Check server connectivity
        $allPassed = $this->checkServerStatus();
        $this->newLine();

        // Check configuration
        $allPassed = $this->checkConfiguration() && $allPassed;
        $this->newLine();

        // Display summary
        if ($allPassed) {
            $this->info('✓ All health checks passed');

            return self::SUCCESS;
        }

        $this->error('✗ Some health checks failed');

        return self::FAILURE;
    }

    /**
     * Check server status.
     */
    private function checkServerStatus(): bool
    {
        try {
            $status = Nimble::server()->status();

            $this->components->info('Server Status: Online');

            if ($status->connections !== null) {
                $this->components->task("Active connections: {$status->connections}");
            }

            if ($status->outRate !== null) {
                $this->components->task('Out rate: '.number_format($status->outRate));
            }

            return true;
        } catch (\Exception $e) {
            $this->components->error('Server Status: Failed to connect');
            $this->line("  Error: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Check configuration.
     */
    private function checkConfiguration(): bool
    {
        $this->line('<fg=yellow>Configuration Check:</>');

        $host = config('nimble.host');
        $port = config('nimble.port');
        $protocol = config('nimble.protocol');
        $hasToken = ! empty(config('nimble.token'));

        $this->components->task("Host: {$host}");
        $this->components->task("Port: {$port}");
        $this->components->task("Protocol: {$protocol}");
        $this->components->task('Authentication: '.($hasToken ? 'Enabled' : 'Disabled'));

        // Check if host is configured
        if (empty($host) || $host === 'localhost') {
            $this->components->warn('Warning: Using default/localhost host');
        }

        return true;
    }
}
