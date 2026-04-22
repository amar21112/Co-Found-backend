<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after a message is written to MySQL and synced to Firebase.
 * Listeners use this to fan out notifications, update unread counts, etc.
 */
class MessageSentEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Message      $message,
        public readonly Conversation $conversation,
        public readonly User         $sender,
    ) {}
}
