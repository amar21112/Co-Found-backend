<?php

namespace App\Firebase;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;
use Kreait\Firebase\Database\Reference;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the Firebase Realtime Database.
 *
 * All write operations are fire-and-forget with exception logging
 * so a Firebase outage never brings down the MySQL-backed API.
 *
 * Install the SDK:
 *   composer require kreait/laravel-firebase
 * Then publish its config:
 *   php artisan vendor:publish --provider="Kreait\Laravel\Firebase\ServiceProvider"
 */
class FirebaseService
{
    private Database $db;

    public function __construct()
    {
        $factory = (new Factory())
            ->withServiceAccount(config('firebase.credentials'))
            ->withDatabaseUri(config('firebase.database_url'));

        $this->db = $factory->createDatabase();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Low-level helpers
    // ─────────────────────────────────────────────────────────────────────────

    public function set(string $path, mixed $value): void
    {
        try {
            $this->db->getReference($path)->set($value);
        } catch (\Throwable $e) {
            Log::error("Firebase set failed [{$path}]: " . $e->getMessage());
        }
    }

    public function update(string $path, array $values): void
    {
        try {
            $this->db->getReference($path)->update($values);
        } catch (\Throwable $e) {
            Log::error("Firebase update failed [{$path}]: " . $e->getMessage());
        }
    }

    public function push(string $path, mixed $value): ?string
    {
        try {
            $ref = $this->db->getReference($path)->push($value);
            return $ref->getKey();
        } catch (\Throwable $e) {
            Log::error("Firebase push failed [{$path}]: " . $e->getMessage());
            return null;
        }
    }

    public function delete(string $path): void
    {
        try {
            $this->db->getReference($path)->remove();
        } catch (\Throwable $e) {
            Log::error("Firebase delete failed [{$path}]: " . $e->getMessage());
        }
    }

    public function get(string $path): mixed
    {
        try {
            return $this->db->getReference($path)->getValue();
        } catch (\Throwable $e) {
            Log::error("Firebase get failed [{$path}]: " . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Path builders — single source of truth for the RTDB tree layout
    // ─────────────────────────────────────────────────────────────────────────

    public function conversationPath(string $conversationId): string
    {
        return "conversations/{$conversationId}";
    }

    public function conversationMetaPath(string $conversationId): string
    {
        return "conversations/{$conversationId}/meta";
    }

    public function messagesPath(string $conversationId): string
    {
        return "conversations/{$conversationId}/messages";
    }

    public function messagePath(string $conversationId, string $messageId): string
    {
        return "conversations/{$conversationId}/messages/{$messageId}";
    }

    public function typingPath(string $conversationId, string $userId): string
    {
        return "conversations/{$conversationId}/typing/{$userId}";
    }

    public function onlinePath(string $conversationId, string $userId): string
    {
        return "conversations/{$conversationId}/online/{$userId}";
    }

    public function notificationsPath(string $userId): string
    {
        return "notifications/{$userId}";
    }

    public function notificationPath(string $userId, string $notificationId): string
    {
        return "notifications/{$userId}/{$notificationId}";
    }

    public function presencePath(string $userId): string
    {
        return "presence/{$userId}";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Convenience: generate a custom auth token for a user
    // Used so the frontend authenticates to Firebase as the signed-in user
    // ─────────────────────────────────────────────────────────────────────────

    public function createCustomToken(string $uid, array $claims = []): string
    {
        $factory = (new Factory())
            ->withServiceAccount(config('firebase.credentials'));

        $auth = $factory->createAuth();
        return $auth->createCustomToken($uid, $claims)->toString();
    }
}
