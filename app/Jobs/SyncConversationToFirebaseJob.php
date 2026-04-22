<?php

namespace App\Jobs;

use App\Firebase\FirebaseSyncService;
use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Re-syncs a conversation's meta node to Firebase.
 * Dispatched after bulk operations (e.g. importing messages) where
 * individual sync calls would be too chatty.
 */
class SyncConversationToFirebaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        private readonly string $conversationId,
    ) {}

    public function handle(FirebaseSyncService $firebase): void
    {
        $conversation = Conversation::with(['participants' => fn($q) => $q->whereNull('left_at')])
            ->find($this->conversationId);

        if (!$conversation) return;

        $firebase->syncConversationMeta($conversation);
    }
}
