<?php

namespace App\Repositories\Contracts;

use App\DTOs\Admin\StoreRestrictionDTO;
use App\Models\User;
use App\Models\UserRestriction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminRestrictionRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;

    public function findById(string $id): ?UserRestriction;

    /**
     * List all restrictions for a specific user.
     */
    public function paginateForUser(string $userId, array $filters, int $perPage): LengthAwarePaginator;

    public function create(User $target, User $admin, StoreRestrictionDTO $dto): UserRestriction;

    public function lift(UserRestriction $restriction, User $admin): UserRestriction;

    /**
     * Deactivate all currently active restrictions of a given type for a user.
     * Called before issuing a new restriction of the same type.
     */
    public function deactivateActiveOfType(User $target, string $type): void;
}
