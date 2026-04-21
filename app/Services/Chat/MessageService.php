<?php

namespace App\Services\Chat;

use App\Enums\MessageType;
use App\Exceptions\CannotEditMessageException;
use App\Exceptions\ChatException;
use App\Exceptions\MessageNotFoundException;
use App\Exceptions\NotAParticipantException;
use App\Firebase\FirebaseSyncService;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use App\Repositories\Contracts\MessageRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MessageService
{
    public function __construct(
        private readonly MessageRepositoryInterface      $messageRepo,
        private readonly ConversationRepositoryInterface $conversationRepo,
        private readonly FirebaseSyncService             $firebase,
    ) {}

    public function list(User $user, Conversation $conversation, array $filters, int $perPage): LengthAwarePaginator
    {
        $this->assertParticipant($user, $conversation);
        return $this->messageRepo->paginateForConversation($conversation->id, $filters, $perPage);
    }

    public function send(User $sender, Conversation $conversation, array $data): Message
    {
        $this->assertParticipant($sender, $conversation);

        $message = $this->messageRepo->create([
            'conversation_id'       => $conversation->id,
            'sender_id'             => $sender->id,
            'message_type'          => $data['message_type'] ?? MessageType::Text->value,
            'content'               => $data['content'],
            'formatted_content'     => $data['formatted_content'] ?? null,
            'replied_to_message_id' => $data['replied_to_message_id'] ?? null,
        ]);

        // Load sender relation for the Firebase payload
        $message->load('sender:id,username,full_name,profile_picture_url');

        // Update MySQL conversation timestamp
        $this->conversationRepo->touchLastMessageAt($conversation->id);

        // Push to Firebase RTDB — this is what makes it real-time
        $this->firebase->syncMessage($message);

        return $message;
    }

    public function edit(User $editor, Message $message, string $newContent): Message
    {
        if ($message->sender_id !== $editor->id) {
            throw new CannotEditMessageException();
        }

        if ($message->trashed()) {
            throw new ChatException('Cannot edit a deleted message.', 422);
        }

        $updated = $this->messageRepo->update($message, [
            'content'   => $newContent,
            'is_edited' => true,
        ]);

        // Update Firebase node so connected clients see the edit instantly
        $this->firebase->updateMessage($updated);

        return $updated;
    }

    public function delete(User $deleter, Message $message): void
    {
        if ($message->sender_id !== $deleter->id) {
            throw new ChatException('You can only delete your own messages.', 403);
        }

        $this->messageRepo->softDelete($message);

        // Mark as deleted in Firebase (content cleared, node preserved for threads)
        $this->firebase->softDeleteMessage($message);
    }

    public function pin(User $actor, Conversation $conversation, Message $message, bool $pin): Message
    {
        $this->assertAdmin($actor, $conversation);

        if ($message->conversation_id !== $conversation->id) {
            throw new MessageNotFoundException();
        }

        $updated = $this->messageRepo->pin($message, $pin);
        $this->firebase->updateMessage($updated);

        return $updated;
    }

    public function markRead(User $user, Message $message): void
    {
        $this->messageRepo->markRead($message->id, $user->id);
        $this->firebase->syncReadReceipts($message->fresh('readReceipts'));
    }

    public function markAllRead(User $user, Conversation $conversation): void
    {
        $this->assertParticipant($user, $conversation);
        $this->messageRepo->markAllReadInConversation($conversation->id, $user->id);
        // No Firebase call needed for bulk read — clients update their own state
    }

    public function addReaction(User $user, Message $message, string $reaction): void
    {
        $this->messageRepo->addReaction($message->id, $user->id, $reaction);
        $this->firebase->syncMessageReactions($message->fresh('reactions'));
    }

    public function removeReaction(User $user, Message $message, string $reaction): void
    {
        $this->messageRepo->removeReaction($message->id, $user->id, $reaction);
        $this->firebase->syncMessageReactions($message->fresh('reactions'));
    }

    public function setTyping(User $user, Conversation $conversation): void
    {
        $this->assertParticipant($user, $conversation);
        $this->firebase->setTyping($conversation->id, $user->id);
    }

    public function clearTyping(User $user, Conversation $conversation): void
    {
        $this->firebase->clearTyping($conversation->id, $user->id);
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
            throw new ChatException('Only conversation admins can pin messages.', 403);
        }
    }
}
