<?php

namespace App\Services\Chat;

use App\Enums\ConversationType;
use App\Exceptions\ConversationNotFoundException;
use App\Exceptions\DirectConversationExistsException;
use App\Exceptions\NotAParticipantException;
use App\Exceptions\ChatException;
use App\Firebase\FirebaseSyncService;
use App\Models\Conversation;
use App\Models\User;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ConversationService
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversationRepo,
        private readonly FirebaseSyncService             $firebase,
    ) {}

    public function list(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->conversationRepo->paginateForUser($user->id, $filters, $perPage);
    }

    public function show(User $user, string $conversationId): Conversation
    {
        $conversation = $this->conversationRepo->findById($conversationId);

        if (!$conversation) throw new ConversationNotFoundException();

        if (!$this->conversationRepo->isParticipant($conversationId, $user->id)) {
            throw new NotAParticipantException();
        }

        return $conversation;
    }

    public function create(User $creator, array $data): Conversation
    {
        $type = ConversationType::from($data['conversation_type']);

        // Prevent duplicate direct conversations
        if ($type === ConversationType::Direct) {
            $otherUserId = collect($data['participant_ids'])
                ->reject(fn($id) => $id === $creator->id)
                ->first();

            if (!$otherUserId) {
                throw new ChatException('A direct conversation requires exactly one other participant.', 422);
            }

            $existing = $this->conversationRepo->findDirectBetween($creator->id, $otherUserId);
            if ($existing) throw new DirectConversationExistsException();
        }

        $conversation = $this->conversationRepo->create([
            'conversation_type' => $data['conversation_type'],
            'title'             => $data['title'] ?? null,
            'project_id'        => $data['project_id'] ?? null,
            'created_by'        => $creator->id,
        ]);

        // Add creator as admin participant
        $this->conversationRepo->addParticipant($conversation->id, $creator->id, isAdmin: true);

        // Add remaining participants
        $participants = collect($data['participant_ids'])->reject(fn($id) => $id === $creator->id);
        foreach ($participants as $userId) {
            $this->conversationRepo->addParticipant($conversation->id, $userId);
        }

        $fresh = $this->conversationRepo->findById($conversation->id);

        // Sync to Firebase so all clients see the new conversation immediately
        $this->firebase->syncConversationMeta($fresh);

        return $fresh;
    }

    public function update(User $user, Conversation $conversation, array $data): Conversation
    {
        $this->assertParticipant($user, $conversation);

        $updated = $this->conversationRepo->update($conversation, $data);
        $this->firebase->syncConversationMeta($updated);

        return $updated;
    }

    public function addParticipant(User $actor, Conversation $conversation, string $userId): void
    {
        $this->assertAdmin($actor, $conversation);

        if ($this->conversationRepo->isParticipant($conversation->id, $userId)) {
            throw new ChatException('User is already a participant.', 409);
        }

        $this->conversationRepo->addParticipant($conversation->id, $userId);

        $fresh = $this->conversationRepo->findById($conversation->id);
        $this->firebase->syncConversationMeta($fresh);
    }

    public function removeParticipant(User $actor, Conversation $conversation, string $userId): void
    {
        $this->assertAdmin($actor, $conversation);

        $this->conversationRepo->removeParticipant($conversation->id, $userId);

        $fresh = $this->conversationRepo->findById($conversation->id);
        $this->firebase->syncConversationMeta($fresh);
    }

    public function leave(User $user, Conversation $conversation): void
    {
        $this->assertParticipant($user, $conversation);

        if ($conversation->created_by === $user->id && $conversation->isDirect() === false) {
            throw new ChatException('The conversation creator cannot leave. Transfer admin rights first.', 422);
        }

        $this->conversationRepo->removeParticipant($conversation->id, $user->id);

        $fresh = $this->conversationRepo->findById($conversation->id);
        $this->firebase->syncConversationMeta($fresh);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Firebase custom token for frontend auth
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Returns a short-lived Firebase custom token so the frontend can
     * authenticate to Firebase RTDB as the signed-in Laravel user.
     * Call this once after login; refresh when the token expires (1 hour).
     */
    public function getFirebaseToken(User $user): string
    {
        return app(\App\Firebase\FirebaseService::class)->createCustomToken(
            $user->id,
            ['email' => $user->email]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Guards
    // ─────────────────────────────────────────────────────────────────────────

    private function assertParticipant(User $user, Conversation $conversation): void
    {
        if (!$this->conversationRepo->isParticipant($conversation->id, $user->id)) {
            throw new NotAParticipantException();
        }
    }

    private function assertAdmin(User $user, Conversation $conversation): void
    {
        $isAdmin = $conversation->participants()
            ->where('user_id', $user->id)
            ->where('is_admin', true)
            ->whereNull('left_at')
            ->exists();

        if (!$isAdmin && $conversation->created_by !== $user->id) {
            throw new ChatException('Only admins can manage participants.', 403);
        }
    }
}
