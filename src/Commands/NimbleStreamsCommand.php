<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Commands;

use AlexHackney\LaraNimble\Facades\Nimble;
use Illuminate\Console\Command;

class NimbleStreamsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'nimble:streams
                            {--filter= : Filter streams by status (active/inactive)}';

    /**
     * The console command description.
     */
    protected $description = 'List all Nimble streams';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->info('Fetching streams from Nimble server...');
            $this->newLine();

            $streams = Nimble::streams()->list();

            // Apply filter if provided
            $filterOption = $this->option('filter');
            $filter = is_string($filterOption) ? $filterOption : null;
            if ($filter) {
                $streams = $streams->filter(function ($stream) use ($filter) {
                    return strtolower($stream->status->value) === strtolower($filter);
                });
            }

            if ($streams->isEmpty()) {
                $this->warn('No streams found.');

                return self::SUCCESS;
            }

            // Prepare table data
            $rows = $streams->map(function ($stream) {
                $statusColor = $stream->status->value === 'active' ? 'green' : 'red';

                return [
                    $stream->id,
                    $stream->name,
                    "<fg={$statusColor}>{$stream->status->value}</>",
                    $stream->protocol->value,
                    $stream->viewers ?? 'N/A',
                    $stream->bitrate ? "{$stream->bitrate} kbps" : 'N/A',
                ];
            })->toArray();

            $this->table(
                ['ID', 'Name', 'Status', 'Protocol', 'Viewers', 'Bitrate'],
                $rows
            );

            $this->newLine();
            $this->info("Total: {$streams->count()} stream(s)");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to fetch streams: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
