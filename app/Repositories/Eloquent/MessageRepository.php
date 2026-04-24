<?php

namespace App\Repositories\Eloquent;

use App\Models\Message;
use App\Models\MessageReadReceipt;
use App\Models\MessageReaction;
use App\Repositories\Contracts\MessageRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MessageRepository implements MessageRepositoryInterface
{
    public function paginateForConversation(string $conversationId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Message::withTrashed()
            ->where('conversation_id', $conversationId)
            ->with([
                'sender:id,username,full_name,profile_picture_url',
                'reactions',
                'readReceipts',
                'sharedFiles.file',
            ]);

        if (!empty($filters['before'])) {
            $pivotMessage = Message::find($filters['before']);
            if ($pivotMessage) {
                $query->where('created_at', '<', $pivotMessage->created_at);
            }
        }

        return $query->latest('created_at')->paginate($perPage);
    }

    public function findById(string $id): ?Message
    {
        return Message::withTrashed()
            ->with(['sender:id,username,full_name,profile_picture_url', 'reactions', 'readReceipts'])
            ->find($id);
    }

    public function create(array $data): Message
    {
        return Message::create($data);
    }

    public function update(Message $message, array $data): Message
    {
        $message->update($data);
        return $message->fresh(['sender', 'reactions', 'readReceipts']);
    }

    public function softDelete(Message $message): void
    {
        $message->delete(); // SoftDeletes trait
    }

    public function pin(Message $message, bool $pin): Message
    {
        $message->update(['is_pinned' => $pin]);
        return $message->fresh();
    }

    public function markRead(string $messageId, string $userId): void
    {
        MessageReadReceipt::firstOrCreate(
            ['message_id' => $messageId, 'user_id' => $userId],
            ['read_at' => now()]
        );
    }

    public function markAllReadInConversation(string $conversationId, string $userId): void
    {
        // Fetch unread message IDs for this user in one query
        $unreadIds = Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->whereDoesntHave('readReceipts', fn($q) => $q->where('user_id', $userId))
            ->pluck('id');

        if ($unreadIds->isEmpty()) return;

        $now  = now();
        $rows = $unreadIds->map(fn($id) => [
            'message_id' => $id,
            'user_id'    => $userId,
            'read_at'    => $now,
        ])->all();

        MessageReadReceipt::insertOrIgnore($rows);
    }

    public function addReaction(string $messageId, string $userId, string $reaction): void
    {
        MessageReaction::firstOrCreate([
            'message_id' => $messageId,
            'user_id'    => $userId,
            'reaction'   => $reaction,
        ]);
    }

    public function removeReaction(string $messageId, string $userId, string $reaction): void
    {
        MessageReaction::where('message_id', $messageId)
            ->where('user_id', $userId)
            ->where('reaction', $reaction)
            ->delete();
    }
}
