<?php

namespace App\Console\Commands;

use App\Firebase\FirebaseSyncService;
use App\Models\Notification;
use Illuminate\Console\Command;

/**
 * Re-sync unread notifications for all users to Firebase RTDB.
 * Useful after a Firebase project reset or partial outage.
 *
 * Usage:
 *   php artisan firebase:sync-notifications
 *   php artisan firebase:sync-notifications --user=uuid
 */
class SyncAllNotificationsToFirebase extends Command
{
    protected $signature   = 'firebase:sync-notifications {--user= : Only sync for a specific user UUID}';
    protected $description = 'Re-sync unread notifications to Firebase RTDB';

    public function handle(FirebaseSyncService $firebase): int
    {
        $query = Notification::query()->where('read', false);

        if ($userId = $this->option('user')) {
            $query->where('user_id', $userId);
        }

        $total  = $query->count();
        $bar    = $this->output->createProgressBar($total);
        $synced = 0;

        $this->info("Syncing {$total} unread notifications...");
        $bar->start();

        $query->chunkById(200, function ($notifications) use ($firebase, $bar, &$synced) {
            foreach ($notifications as $notification) {
                try {
                    $firebase->syncNotification($notification);
                    $synced++;
                } catch (\Throwable $e) {
                    $this->warn("  Failed: {$notification->id}");
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Synced: {$synced}");

        return self::SUCCESS;
    }
}
