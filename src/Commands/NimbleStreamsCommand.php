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
                            {--app= : Only show streams of this application}';

    /**
     * The console command description.
     */
    protected $description = 'List currently live Nimble streams';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->info('Fetching live streams from Nimble server...');
            $this->newLine();

            $appOption = $this->option('app');
            $app = is_string($appOption) && $appOption !== '' ? $appOption : null;

            $streams = $app !== null
                ? Nimble::streams()->byApp($app)
                : Nimble::streams()->list();

            if ($streams->isEmpty()) {
                $this->warn('No live streams found.');

                return self::SUCCESS;
            }

            $rows = $streams->map(function ($stream) {
                return [
                    $stream->app,
                    $stream->stream,
                    $stream->protocol ?? 'N/A',
                    $stream->resolution ?? 'N/A',
                    $stream->bandwidth !== null ? number_format($stream->bandwidth) : 'N/A',
                    $stream->publisherIp ?? 'N/A',
                ];
            })->toArray();

            $this->table(
                ['App', 'Stream', 'Protocol', 'Resolution', 'Bandwidth', 'Publisher'],
                $rows
            );

            $this->newLine();
            $this->info("Total: {$streams->count()} live stream(s)");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to fetch streams: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
