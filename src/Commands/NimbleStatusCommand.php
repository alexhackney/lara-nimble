<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Commands;

use AlexHackney\LaraNimble\Facades\Nimble;
use Illuminate\Console\Command;

class NimbleStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'nimble:status';

    /**
     * The console command description.
     */
    protected $description = 'Display Nimble server status and statistics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->info('Fetching Nimble server status...');
            $this->newLine();

            $status = Nimble::server()->status();

            $this->components->twoColumnDetail('Server Status', "<fg=green>{$status->status}</>");

            if ($status->version) {
                $this->components->twoColumnDetail('Version', $status->version);
            }

            if ($status->uptime !== null) {
                $uptime = $this->formatUptime($status->uptime);
                $this->components->twoColumnDetail('Uptime', $uptime);
            }

            if ($status->connections !== null) {
                $this->components->twoColumnDetail('Active Connections', (string) $status->connections);
            }

            if ($status->bandwidth) {
                $this->newLine();
                $this->line('<fg=yellow>Bandwidth:</fg=yellow>');
                $this->components->twoColumnDetail(
                    '  Incoming',
                    $this->formatBytes($status->bandwidth['in'] ?? 0)
                );
                $this->components->twoColumnDetail(
                    '  Outgoing',
                    $this->formatBytes($status->bandwidth['out'] ?? 0)
                );
            }

            $this->newLine();
            $this->info('✓ Server is operational');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to fetch server status: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Format uptime seconds to human-readable format.
     */
    private function formatUptime(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = "{$days}d";
        }
        if ($hours > 0) {
            $parts[] = "{$hours}h";
        }
        if ($minutes > 0) {
            $parts[] = "{$minutes}m";
        }

        return implode(' ', $parts) ?: '0m';
    }

    /**
     * Format bytes to human-readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);

        return sprintf('%.2f %s', $bytes / (1024 ** $factor), $units[$factor]);
    }
}
