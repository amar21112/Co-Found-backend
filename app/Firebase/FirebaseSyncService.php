<?php

namespace App\Firebase;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Notification;
use App\Models\File;

/**
 * Handles syncing MySQL records into Firebase Realtime Database.
 *
 * Role split:
 *   MySQL  → source of truth, pagination, complex queries, file storage
 *   Firebase RTDB → real-time delivery to connected clients
 *
 * Every method is safe to call on every write; Firebase failures are
 * caught inside FirebaseService and only logged, never thrown.
 */
class FirebaseSyncService
{
    public function __construct(
        private readonly FirebaseService $firebase,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Conversations
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Write or refresh the conversation meta node.
     * Called on: create, title update, participant change, last_message_at update.
     */
    public function syncConversationMeta(Conversation $conversation): void
    {
        $participants = $conversation->participants()
            ->whereNull('left_at')
            ->pluck('user_id')
            ->values()
            ->all();

        $this->firebase->set(
            $this->firebase->conversationMetaPath($conversation->id),
            [
                'id'              => $conversation->id,
                'type'            => $conversation->conversation_type,
                'title'           => $conversation->title,
                'project_id'      => $conversation->project_id,
                'created_by'      => $conversation->created_by,
                'participant_ids' => $participants,
                'last_message_at' => $conversation->last_message_at?->toISOString(),
                'updated_at'      => now()->toISOString(),
            ]
        );
    }

    /**
     * Remove the entire conversation node from RTDB.
     * Called when a conversation is deleted on the MySQL side.
     */
    public function deleteConversation(string $conversationId): void
    {
        $this->firebase->delete(
            $this->firebase->conversationPath($conversationId)
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Messages
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Push a new message into RTDB and refresh the conversation meta.
     * The Firebase key is the MySQL UUID so both systems stay in sync.
     */
    public function syncMessage(Message $message): void
    {
        $this->firebase->set(
            $this->firebase->messagePath($message->conversation_id, $message->id),
            $this->buildMessagePayload($message)
        );

        // Update last_message_at on the conversation meta node
        $this->firebase->update(
            $this->firebase->conversationMetaPath($message->conversation_id),
            [
                'last_message_at'    => $message->created_at->toISOString(),
                'last_message_preview' => $this->buildPreview($message),
            ]
        );
    }

    /**
     * Patch an existing message node (edit, pin, soft-delete).
     */
    public function updateMessage(Message $message): void
    {
        $this->firebase->update(
            $this->firebase->messagePath($message->conversation_id, $message->id),
            $this->buildMessagePayload($message)
        );
    }

    /**
     * Mark a message as deleted in RTDB (soft delete — content hidden,
     * node preserved so reply threads remain intact).
     */
    public function softDeleteMessage(Message $message): void
    {
        $this->firebase->update(
            $this->firebase->messagePath($message->conversation_id, $message->id),
            [
                'deleted'    => true,
                'content'    => null,
                'deleted_at' => now()->toISOString(),
            ]
        );
    }

    /**
     * Add or remove a reaction count summary on the message node.
     * The full reaction list lives in MySQL; RTDB only carries a summary
     * so clients can show emoji counts without a REST call.
     */
    public function syncMessageReactions(Message $message): void
    {
        $summary = $message->reactions()
            ->selectRaw('reaction, count(*) as count')
            ->groupBy('reaction')
            ->pluck('count', 'reaction')
            ->toArray();

        $this->firebase->update(
            $this->firebase->messagePath($message->conversation_id, $message->id),
            ['reactions' => $summary ?: null]
        );
    }

    /**
     * Sync read receipt counts so clients can show "seen by N" indicators.
     */
    public function syncReadReceipts(Message $message): void
    {
        $readCount = $message->readReceipts()->count();

        $this->firebase->update(
            $this->firebase->messagePath($message->conversation_id, $message->id),
            ['read_count' => $readCount]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Typing indicators (ephemeral — no MySQL equivalent)
    // ─────────────────────────────────────────────────────────────────────────

    public function setTyping(string $conversationId, string $userId): void
    {
        $this->firebase->set(
            $this->firebase->typingPath($conversationId, $userId),
            true
        );
    }

    public function clearTyping(string $conversationId, string $userId): void
    {
        $this->firebase->delete(
            $this->firebase->typingPath($conversationId, $userId)
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Notifications
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Write a new notification into the user's RTDB notification inbox.
     * Called immediately after the MySQL row is created.
     */
    public function syncNotification(Notification $notification): void
    {
        $this->firebase->set(
            $this->firebase->notificationPath($notification->user_id, $notification->id),
            [
                'id'           => $notification->id,
                'type'         => $notification->type,
                'title'        => $notification->title,
                'body'         => $notification->body,
                'data'         => $notification->data,
                'priority'     => $notification->priority,
                'read'         => $notification->read,
                'read_at'      => $notification->read_at?->toISOString(),
                'delivered_at' => $notification->delivered_at?->toISOString(),
                'created_at'   => $notification->created_at?->toISOString(),
            ]
        );
    }

    /**
     * Mark a single notification as read in RTDB.
     */
    public function markNotificationRead(Notification $notification): void
    {
        $this->firebase->update(
            $this->firebase->notificationPath($notification->user_id, $notification->id),
            [
                'read'    => true,
                'read_at' => $notification->read_at?->toISOString(),
            ]
        );
    }

    /**
     * Mark all of a user's notifications as read in RTDB.
     * Uses a bulk update so it's a single HTTP call to Firebase.
     */
    public function markAllNotificationsRead(string $userId, array $notificationIds): void
    {
        if (empty($notificationIds)) return;

        $updates = [];
        $readAt  = now()->toISOString();

        foreach ($notificationIds as $id) {
            $updates["{$id}/read"]    = true;
            $updates["{$id}/read_at"] = $readAt;
        }

        $this->firebase->update(
            $this->firebase->notificationsPath($userId),
            $updates
        );
    }

    /**
     * Remove a notification from RTDB when deleted from MySQL.
     */
    public function deleteNotification(string $userId, string $notificationId): void
    {
        $this->firebase->delete(
            $this->firebase->notificationPath($userId, $notificationId)
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Presence
    // ─────────────────────────────────────────────────────────────────────────

    public function setPresenceOnline(string $userId): void
    {
        $this->firebase->set(
            $this->firebase->presencePath($userId),
            [
                'online'    => true,
                'last_seen' => now()->toISOString(),
            ]
        );
    }

    public function setPresenceOffline(string $userId): void
    {
        $this->firebase->update(
            $this->firebase->presencePath($userId),
            [
                'online'    => false,
                'last_seen' => now()->toISOString(),
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function buildMessagePayload(Message $message): array
    {
        return [
            'id'                    => $message->id,
            'conversation_id'       => $message->conversation_id,
            'sender_id'             => $message->sender_id,
            'sender_name'           => $message->sender?->full_name,
            'sender_avatar'         => $message->sender?->profile_picture_url,
            'message_type'          => $message->message_type,
            'content'               => $message->trashed() ? null : $message->content,
            'replied_to_message_id' => $message->replied_to_message_id,
            'is_pinned'             => $message->is_pinned,
            'is_edited'             => $message->is_edited,
            'deleted'               => $message->trashed(),
            'created_at'            => $message->created_at?->toISOString(),
            'updated_at'            => $message->updated_at?->toISOString(),
        ];
    }

    private function buildPreview(Message $message): string
    {
        if ($message->message_type === 'file') {
            return '📎 File';
        }

        return mb_substr($message->content ?? '', 0, 60);
    }
}
