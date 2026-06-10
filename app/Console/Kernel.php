<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();

        // Prune stale guest accounts daily at midnight
        $schedule->command('auth:prune-guests --days=7')->daily();

        // Nightly ML batch — score matches for all active regular users.
        // Runs at 02:00 server time (lowest traffic window).
        // Per-user scoring on approval is handled by AdminVerificationService.
        $schedule->command('ml:generate-matches')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/ml-matches.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
