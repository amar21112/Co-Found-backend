<?php

namespace App\Repositories\Contracts;

use App\Models\PasswordReset;
use App\Models\User;

interface PasswordResetRepositoryInterface
{
    public function createForUser(User $user, string $token, \DateTimeInterface $expiresAt): PasswordReset;

    public function findValidToken(string $token): ?PasswordReset;

    public function markUsed(PasswordReset $reset): void;

    public function deleteExpiredForUser(User $user): void;

    public function deleteAllForUser(User $user): void;

    public function deleteOtherTokensForUser(User $user, string $exceptId): void;
}
