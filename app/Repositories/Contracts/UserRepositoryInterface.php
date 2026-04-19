<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use DateTimeInterface;

interface UserRepositoryInterface
{
    // ── Lookups ───────────────────────────────────────────────────────────────

    public function findById(string $id): ?User;

    /**
     * Find any non-deleted user by email.
     * Used for forgot-password (we need to find pending + active users).
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find a user by email who is allowed to authenticate.
     * Excludes soft-deleted records. Used exclusively in the login flow.
     */
    public function findAuthenticatableByEmail(string $email): ?User;

    public function findByUsername(string $username): ?User;

    public function findByEmailVerificationToken(string $token): ?User;

    // ── Creation ──────────────────────────────────────────────────────────────

    public function create(array $data): User;

    // ── Updates ───────────────────────────────────────────────────────────────

    public function update(User $user, array $data): User;

    public function markEmailVerified(User $user): User;

    public function incrementLoginAttempts(User $user): void;

    public function resetLoginAttempts(User $user): void;

    public function lockUntil(User $user, DateTimeInterface $until): void;

    public function updateLastLogin(User $user, string $ip): void;

    // ── Checks ────────────────────────────────────────────────────────────────

    public function existsByEmail(string $email): bool;

    public function existsByUsername(string $username): bool;

    /**
     * Check whether the user has any active admin-issued restriction.
     * (Different from the brute-force lock — this is an admin action.)
     */
    public function hasActiveRestriction(User $user): bool;

    // ── Deletion ──────────────────────────────────────────────────────────────

    /**
     * Hard-delete stale guest accounts (called by scheduled command).
     */
    public function deleteGuestOlderThan(DateTimeInterface $threshold): int;
}
