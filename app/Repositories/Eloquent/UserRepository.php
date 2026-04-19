<?php

namespace App\Repositories\Eloquent;

use App\Enums\AccountStatus;
use App\Enums\RestrictionType;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserRestriction;
use App\Repositories\Contracts\UserRepositoryInterface;
use DateTimeInterface;

class UserRepository implements UserRepositoryInterface
{
    // ── Lookups ───────────────────────────────────────────────────────────────

    public function findById(string $id): ?User
    {
        return User::find($id);
    }

    /**
     * Find any non-deleted user by email.
     * Used in forgot-password — we need pending + active users.
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Find a non-deleted user by email, eagerly loading active restrictions.
     * Used exclusively in the login flow so the AuthService can check
     * admin-issued restrictions in a single query.
     */
    public function findAuthenticatableByEmail(string $email): ?User
    {
        return User::where('email', $email)
            ->with(['activeRestrictions'])
            ->first();
    }

    public function findByUsername(string $username): ?User
    {
        return User::where('username', $username)->first();
    }

    public function findByEmailVerificationToken(string $token): ?User
    {
        return User::where('email_verification_token', $token)
            ->where('email_verification_expires', '>', now())
            ->first();
    }

    // ── Creation ──────────────────────────────────────────────────────────────

    public function create(array $data): User
    {
        return User::create($data);
    }

    // ── Updates ───────────────────────────────────────────────────────────────

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    public function markEmailVerified(User $user): User
    {
        $user->update([
            'email_verified'             => true,
            'account_status'             => AccountStatus::Active->value,
            'email_verification_token'   => null,
            'email_verification_expires' => null,
        ]);

        return $user->fresh();
    }

    public function incrementLoginAttempts(User $user): void
    {
        $user->increment('login_attempts');
    }

    public function resetLoginAttempts(User $user): void
    {
        $user->update(['login_attempts' => 0, 'locked_until' => null]);
    }

    public function lockUntil(User $user, DateTimeInterface $until): void
    {
        $user->update(['locked_until' => $until]);
    }

    public function updateLastLogin(User $user, string $ip): void
    {
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }

    // ── Checks ────────────────────────────────────────────────────────────────

    public function existsByEmail(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    public function existsByUsername(string $username): bool
    {
        return User::where('username', $username)->exists();
    }

    /**
     * Returns true if the user has at least one active full_suspension restriction.
     *
     * We intentionally use a direct DB query here rather than the relation method
     * ($user->activeRestrictions()->exists()) because findAuthenticatableByEmail()
     * eager-loads the relation, and calling ->exists() on an already-loaded HasMany
     * can bypass the scope in some Laravel versions. A direct query is explicit,
     * testable, and immune to eager-load cache interference.
     */
    public function hasActiveRestriction(User $user): bool
    {
        return UserRestriction::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where('restriction_type', RestrictionType::FullSuspension->value)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    // ── Deletion ──────────────────────────────────────────────────────────────

    public function deleteGuestOlderThan(DateTimeInterface $threshold): int
    {
        return User::where('role', UserRole::Guest->value)
            ->where('created_at', '<', $threshold)
            ->forceDelete();
    }
}
