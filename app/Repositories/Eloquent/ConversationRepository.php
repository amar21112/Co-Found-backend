<?php

namespace App\Repositories\Eloquent;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ConversationRepository implements ConversationRepositoryInterface
{
    public function paginateForUser(string $userId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Conversation::query()
            ->whereHas('participants', fn($q) =>
                $q->where('user_id', $userId)->whereNull('left_at')
            )
            ->with([
                'creator:id,username,full_name,profile_picture_url',
                'latestMessage.sender:id,username,full_name,profile_picture_url',
                'activeParticipants.user:id,username,full_name,profile_picture_url,identity_verified',
            ]);

        if (!empty($filters['type'])) {
            $query->where('conversation_type', $filters['type']);
        }

        return $query->orderByDesc('last_message_at')->paginate($perPage);
    }

    public function findById(string $id): ?Conversation
    {
        return Conversation::with([
            'creator:id,username,full_name,profile_picture_url',
            'activeParticipants.user:id,username,full_name,profile_picture_url,identity_verified',
            'project:id,title,slug',
        ])->find($id);
    }

    public function findDirectBetween(string $userA, string $userB): ?Conversation
    {
        return Conversation::where('conversation_type', 'direct')
            ->whereHas('participants', fn($q) => $q->where('user_id', $userA))
            ->whereHas('participants', fn($q) => $q->where('user_id', $userB))
            ->first();
    }

    public function create(array $data): Conversation
    {
        return Conversation::create($data);
    }

    public function update(Conversation $conversation, array $data): Conversation
    {
        $conversation->update($data);
        return $conversation->fresh();
    }

    public function addParticipant(string $conversationId, string $userId, bool $isAdmin = false): ConversationParticipant
    {
        return ConversationParticipant::updateOrCreate(
            ['conversation_id' => $conversationId, 'user_id' => $userId],
            ['joined_at' => now(), 'left_at' => null, 'is_admin' => $isAdmin]
        );
    }

    public function removeParticipant(string $conversationId, string $userId): void
    {
        ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->update(['left_at' => now()]);
    }

    public function isParticipant(string $conversationId, string $userId): bool
    {
        return ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->exists();
    }

    public function getParticipantIds(string $conversationId): Collection
    {
        return ConversationParticipant::where('conversation_id', $conversationId)
            ->whereNull('left_at')
            ->pluck('user_id');
    }

    public function touchLastMessageAt(string $conversationId): void
    {
        Conversation::where('id', $conversationId)
            ->update(['last_message_at' => now()]);
    }
}
