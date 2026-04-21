<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->exists();
    }

    public function update(User $user, Conversation $conversation): bool
    {
        if ($conversation->isDirect()) return false;

        return $conversation->participants()
            ->where('user_id', $user->id)
            ->where('is_admin', true)
            ->whereNull('left_at')
            ->exists();
    }

    public function manageParticipants(User $user, Conversation $conversation): bool
    {
        return $this->update($user, $conversation);
    }
}
