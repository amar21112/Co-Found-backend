<?php

namespace App\Repositories\Contracts;

use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminUserRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;

    public function findById(string $id): ?User;

    public function update(User $user, array $data): User;

    public function softDelete(User $user): void;

    /**
     * Find the identity verification record for a specific user,
     * fully loaded with reviews and reviewer details.
     * Returns null when the user has never submitted verification.
     */
    public function findVerificationByUserId(string $userId): ?IdentityVerification;

    /**
     * Paginate all reports filed against a specific user.
     * Includes reporter data and resolution info.
     */
    public function paginateReportsForUser(string $userId, int $perPage): LengthAwarePaginator;
}
