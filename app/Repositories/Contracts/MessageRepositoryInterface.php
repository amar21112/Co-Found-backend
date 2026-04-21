<?php

namespace App\Repositories\Contracts;

use App\Models\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MessageRepositoryInterface
{
    public function paginateForConversation(string $conversationId, array $filters, int $perPage): LengthAwarePaginator;

    public function findById(string $id): ?Message;

    public function create(array $data): Message;

    public function update(Message $message, array $data): Message;

    public function softDelete(Message $message): void;

    public function pin(Message $message, bool $pin): Message;

    public function markRead(string $messageId, string $userId): void;

    public function markAllReadInConversation(string $conversationId, string $userId): void;

    public function addReaction(string $messageId, string $userId, string $reaction): void;

    public function removeReaction(string $messageId, string $userId, string $reaction): void;
}
