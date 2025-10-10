<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Commands;

use AlexHackney\LaraNimble\Facades\Nimble;
use Illuminate\Console\Command;

class NimbleCacheClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'nimble:cache:clear
                            {--type= : Cache type to clear (all, hls, dash)}';

    /**
     * The console command description.
     */
    protected $description = 'Clear Nimble server cache';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $type = $this->option('type');

            $message = $type
                ? "Clearing {$type} cache..."
                : 'Clearing all caches...';

            $this->info($message);

            $result = Nimble::cache()->clear($type);

            if ($result) {
                $this->newLine();
                $this->info('✓ Cache cleared successfully');
                return self::SUCCESS;
            }

            $this->error('Failed to clear cache');
            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error('Failed to clear cache: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
