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
                            {key : The data cache key to remove (see data_cache/get_key)}
                            {--dry-run : Report what would be removed without removing it}';

    /**
     * The console command description.
     */
    protected $description = 'Remove items from the Nimble data cache';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $keyArgument = $this->argument('key');
            $key = is_string($keyArgument) ? $keyArgument : '';
            $dryRun = (bool) $this->option('dry-run');

            if ($key === '') {
                $this->error('A cache key is required.');

                return self::FAILURE;
            }

            $this->info($dryRun ? "Checking cache items for key: {$key}" : "Removing cache items for key: {$key}");

            $removed = Nimble::cache()->delete($key, $dryRun);

            if ($removed === []) {
                $this->warn('No cached items matched the given key.');

                return self::SUCCESS;
            }

            $this->newLine();

            foreach ($removed as $item) {
                $this->line("  - {$item}");
            }

            $this->newLine();
            $count = count($removed);
            $this->info($dryRun ? "✓ {$count} item(s) would be removed" : "✓ {$count} item(s) removed");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to clear cache: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
