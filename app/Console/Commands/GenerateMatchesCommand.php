<?php

namespace App\Console\Commands;

use App\Jobs\GenerateMatchesJob;
use Illuminate\Console\Command;

/**
 * Artisan command for the nightly ML batch.
 *
 * Dispatches GenerateMatchesJob (no userId = batch all users) onto the queue.
 * The job delegates entirely to MlMatchingService — no logic lives here.
 *
 * Usage:
 *   php artisan ml:generate-matches
 */
class GenerateMatchesCommand extends Command
{
    protected $signature   = 'ml:generate-matches';
    protected $description = 'Queue a batch ML match generation run for all active users';

    public function handle(): int
    {
        GenerateMatchesJob::dispatch();
        $this->info('GenerateMatchesJob dispatched to queue.');
        return self::SUCCESS;
    }
}
