<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function update(User $user, Message $message): bool
    {
        return $user->id === $message->sender_id && !$message->trashed();
    }

    public function delete(User $user, Message $message): bool
    {
        if ($user->id === $message->sender_id) {
            return true;
        }

        // Conversation creator or admin participant may delete any message
        $conversation = $message->conversation;

        if ($conversation->created_by === $user->id) {
            return true;
        }

        return $conversation->participants()
            ->where('user_id', $user->id)
            ->where('is_admin', true)
            ->whereNull('left_at')
            ->exists();
    }
}
