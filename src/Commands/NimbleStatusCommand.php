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

            if ($status->connections !== null) {
                $this->components->twoColumnDetail('Active Connections', (string) $status->connections);
            }

            if ($status->outRate !== null) {
                $this->components->twoColumnDetail('Out Rate', number_format($status->outRate));
            }

            if ($status->ramCacheSize !== null) {
                $this->components->twoColumnDetail(
                    'RAM Cache',
                    $this->formatBytes($status->ramCacheSize)
                        .($status->maxRamCacheSize !== null ? ' / '.$this->formatBytes($status->maxRamCacheSize) : '')
                );
            }

            if ($status->fileCacheSize !== null) {
                $this->components->twoColumnDetail(
                    'File Cache',
                    $this->formatBytes($status->fileCacheSize)
                        .($status->maxFileCacheSize !== null ? ' / '.$this->formatBytes($status->maxFileCacheSize) : '')
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
     * Format bytes to human-readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = min((int) floor((strlen((string) $bytes) - 1) / 3), count($units) - 1);

        return sprintf('%.2f %s', $bytes / (1024 ** $factor), $units[$factor]);
    }
}
