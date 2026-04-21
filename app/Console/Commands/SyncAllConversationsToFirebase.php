<?php

namespace App\Console\Commands;

use App\Firebase\FirebaseSyncService;
use App\Models\Conversation;
use Illuminate\Console\Command;

/**
 * One-shot command to re-sync all conversations to Firebase RTDB.
 * Run after a migration, Firebase project reset, or partial outage.
 *
 * Usage:
 *   php artisan firebase:sync-conversations
 *   php artisan firebase:sync-conversations --since="2025-01-01"
 */
class SyncAllConversationsToFirebase extends Command
{
    protected $signature   = 'firebase:sync-conversations {--since= : Only sync conversations updated after this date}';
    protected $description = 'Re-sync all (or recent) conversation meta nodes to Firebase RTDB';

    public function handle(FirebaseSyncService $firebase): int
    {
        $query = Conversation::with([
            'participants' => fn($q) => $q->whereNull('left_at'),
        ]);

        if ($since = $this->option('since')) {
            $query->where('updated_at', '>=', $since);
        }

        $total    = $query->count();
        $bar      = $this->output->createProgressBar($total);
        $synced   = 0;
        $failed   = 0;

        $this->info("Syncing {$total} conversations to Firebase...");
        $bar->start();

        $query->chunkById(100, function ($conversations) use ($firebase, $bar, &$synced, &$failed) {
            foreach ($conversations as $conversation) {
                try {
                    $firebase->syncConversationMeta($conversation);
                    $synced++;
                } catch (\Throwable $e) {
                    $failed++;
                    $this->warn("  Failed: {$conversation->id} — {$e->getMessage()}");
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Synced: {$synced} | Failed: {$failed}");

        return self::SUCCESS;
    }
}
