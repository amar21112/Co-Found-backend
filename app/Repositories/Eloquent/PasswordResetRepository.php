<?php

namespace App\Repositories\Eloquent;

use App\Models\PasswordReset;
use App\Models\User;
use App\Repositories\Contracts\PasswordResetRepositoryInterface;
use DateTimeInterface;

class PasswordResetRepository implements PasswordResetRepositoryInterface
{
    public function createForUser(User $user, string $token, DateTimeInterface $expiresAt): PasswordReset
    {
        return PasswordReset::create([
            'user_id'     => $user->id,
            'reset_token' => $token,
            'expires_at'  => $expiresAt,
        ]);
    }

    public function findValidToken(string $token): ?PasswordReset
    {
        return PasswordReset::where('reset_token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function markUsed(PasswordReset $reset): void
    {
        $reset->update(['used_at' => now()]);
    }

    public function deleteExpiredForUser(User $user): void
    {
        PasswordReset::where('user_id', $user->id)
            ->where('expires_at', '<=', now())
            ->delete();
    }

    public function deleteAllForUser(User $user): void
    {
        PasswordReset::where('user_id', $user->id)->delete();
    }

    public function deleteOtherTokensForUser(User $user, string $exceptId): void
    {
        PasswordReset::where('user_id', $user->id)
            ->where('id', '!=', $exceptId)
            ->delete();
    }
}
