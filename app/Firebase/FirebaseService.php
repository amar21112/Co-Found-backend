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
    // Path builders
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Check if a user is a participant in a conversation.
     * @throws DatabaseException
     */
    public function isConversationParticipant(string $conversationId, string $userId): bool
    {
        $participants = $this->db
            ->getReference("conversations/$conversationId/participants")
            ->getValue();

        return is_array($participants) && in_array($userId, $participants);
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

    public function notificationsPath(string $userId): string
    {
        return "notifications/$userId";
    }

    public function notificationPath(string $userId, string $notificationId): string
    {
        return "notifications/$userId/$notificationId";
    }
}
