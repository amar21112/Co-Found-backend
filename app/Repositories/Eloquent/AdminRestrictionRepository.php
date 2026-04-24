<?php

namespace App\Repositories\Eloquent;

use App\DTOs\Admin\StoreRestrictionDTO;
use App\Models\User;
use App\Models\UserRestriction;
use App\Repositories\Contracts\AdminRestrictionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminRestrictionRepository implements AdminRestrictionRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = UserRestriction::with(['user', 'restrictedBy', 'liftedBy'])
            ->orderByDesc('created_at');

        if (isset($filters['is_active'])) {
            $active = (bool) $filters['is_active'];
            $query->where('is_active', $active);

            // When filtering for active restrictions, also exclude expired ones
            // (is_active stays true in DB until explicitly lifted, but logically
            // a timed restriction is inactive once expires_at has passed).
            if ($active) {
                $query->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });
            }
        }

        if (! empty($filters['restriction_type'])) {
            $query->where('restriction_type', $filters['restriction_type']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->paginate($perPage);
    }

    public function findById(string $id): ?UserRestriction
    {
        return UserRestriction::with(['user', 'restrictedBy', 'liftedBy'])->find($id);
    }

    public function paginateForUser(string $userId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = UserRestriction::with(['restrictedBy', 'liftedBy'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at');

        if (isset($filters['is_active'])) {
            $active = (bool) $filters['is_active'];
            $query->where('is_active', $active);
            if ($active) {
                $query->where(fn($q) =>
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now())
                );
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a UserRestriction record from the typed DTO.
     *
     * We use $dto->restrictionType->value (plain string) for the
     * restriction_type column so the enum cast on the model's write
     * path does not try to call ->value on an already-string value.
     */
    public function create(User $target, User $admin, StoreRestrictionDTO $dto): UserRestriction
    {
        $expiresAt = $dto->durationHours
            ? now()->addHours($dto->durationHours)
            : null;

        return UserRestriction::create([
            'user_id'          => $target->id,
            'restricted_by'    => $admin->id,
            'restriction_type' => $dto->restrictionType->value,
            'reason'           => $dto->reason,
            'duration_hours'   => $dto->durationHours,
            'starts_at'        => now(),
            'expires_at'       => $expiresAt,
            'is_active'        => true,
        ]);
    }

    public function lift(UserRestriction $restriction, User $admin): UserRestriction
    {
        $restriction->update([
            'is_active' => false,
            'lifted_by' => $admin->id,
            'lifted_at' => now(),
        ]);

        return $restriction->fresh(['user', 'restrictedBy', 'liftedBy']);
    }

    public function deactivateActiveOfType(User $target, string $type): void
    {
        UserRestriction::where('user_id', $target->id)
            ->where('restriction_type', $type)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
