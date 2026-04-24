<?php

namespace App\Repositories\Contracts;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ConversationRepositoryInterface
{
    public function paginateForUser(string $userId, array $filters, int $perPage): LengthAwarePaginator;

    public function findById(string $id): ?Conversation;

    public function findDirectBetween(string $userA, string $userB): ?Conversation;

    public function create(array $data): Conversation;

    public function update(Conversation $conversation, array $data): Conversation;

    public function addParticipant(string $conversationId, string $userId, bool $isAdmin = false): ConversationParticipant;

    public function removeParticipant(string $conversationId, string $userId): void;

    public function isParticipant(string $conversationId, string $userId): bool;

    public function getParticipantIds(string $conversationId): Collection;

    public function touchLastMessageAt(string $conversationId): void;
}
