<?php

namespace App\Firebase;

use Kreait\Firebase\Contract\Database;
use Kreait\Firebase\Exception\DatabaseException;

class FirebaseService
{
    public function __construct(
        private readonly Database $db,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // RTDB primitives
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @throws DatabaseException
     */
    public function set(string $path, mixed $value): void
    {
        $this->db->getReference($path)->set($value);
    }

    /**
     * @throws DatabaseException
     */
    public function update(string $path, array $values): void
    {
        $this->db->getReference($path)->update($values);
    }

    /**
     * Check whether a node exists without fetching its full value.
     * @throws DatabaseException
     */
    public function exists(string $path): bool
    {
        return $this->db->getReference($path)->getSnapshot()->exists();
    }

    /**
     * Fetch a node's value.
     * @throws DatabaseException
     */
    public function get(string $path): mixed
    {
        return $this->db->getReference($path)->getValue();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Conversation helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Check if a user is a participant in a direct/private conversation.
     * Reads conversations/{id}/participants → array of user UUID strings.
     *
     * @throws DatabaseException
     */
    public function isConversationParticipant(string $conversationId, string $userId): bool
    {
        $participants = $this->db
            ->getReference("conversations/$conversationId/participants")
            ->getValue();

        return is_array($participants) && in_array($userId, $participants, strict: true);
    }

    /**
     * Check if a user is a participant in a group conversation.
     * Group conversations (project-linked) live under group_conversations/{id}.
     *
     * @throws DatabaseException
     */
    public function isGroupConversationParticipant(string $conversationId, string $userId): bool
    {
        $participants = $this->db
            ->getReference("group_conversations/$conversationId/participants")
            ->getValue();

        return is_array($participants) && in_array($userId, $participants, strict: true);
    }

    /**
     * Check whether a conversation is a private (1-to-1) conversation.
     * type = "private" for direct messages, something else (e.g. "group") for groups.
     *
     * @throws DatabaseException
     */
    public function isPrivateConversation(string $conversationId): bool
    {
        $type = $this->db
            ->getReference("conversations/$conversationId/type")
            ->getValue();

        return $type === 'private';
    }

    /**
     * Count participants in a direct/private conversation.
     * Reads conversations/{id}/participants → count the array.
     *
     * @throws DatabaseException
     */
    public function conversationParticipantCount(string $conversationId): int
    {
        $participants = $this->db
            ->getReference("conversations/$conversationId/participants")
            ->getValue();

        return is_array($participants) ? count($participants) : 0;
    }

    /**
     * Count participants in a group conversation.
     *
     * @throws DatabaseException
     */
    public function groupConversationParticipantCount(string $conversationId): int
    {
        $participants = $this->db
            ->getReference("group_conversations/$conversationId/participants")
            ->getValue();

        return is_array($participants) ? count($participants) : 0;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Path builders
    // ─────────────────────────────────────────────────────────────────────────

    public function conversationPath(string $conversationId): string
    {
        return "conversations/$conversationId";
    }

    public function conversationMetaPath(string $conversationId): string
    {
        return "conversations/$conversationId/meta";
    }

    public function groupConversationPath(string $conversationId): string
    {
        return "group_conversations/$conversationId";
    }

    public function notificationsPath(string $userId): string
    {
        return "notifications/$userId";
    }

    public function notificationPath(string $userId, string $notificationId): string
    {
        return "notifications/$userId/$notificationId";
    }
}
