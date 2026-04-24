<?php

namespace App\Services\Admin;

use App\DTOs\Admin\StoreRestrictionDTO;
use App\Enums\AccountStatus;
use App\Enums\RestrictionType;
use App\Exceptions\Admin\RestrictionAlreadyLiftedException;
use App\Exceptions\Admin\RestrictionNotFoundException;
use App\Models\User;
use App\Models\UserRestriction;
use App\Repositories\Contracts\AdminRestrictionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

readonly class AdminRestrictionService
{
    public function __construct(
        private AdminRestrictionRepositoryInterface $restrictionRepo,
        private UserRepositoryInterface             $userRepo,
        private AdminActionLogger                   $logger,
    ) {}

    // =========================================================================
    // List
    // =========================================================================

    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->restrictionRepo->paginate($filters, $perPage);
    }

    // =========================================================================
    // Show
    // =========================================================================

    public function show(string $id): UserRestriction
    {
        $restriction = $this->restrictionRepo->findById($id);

        if (! $restriction) {
            throw new RestrictionNotFoundException();
        }

        return $restriction;
    }

    // =========================================================================
    // User-scoped list
    // =========================================================================

    public function listForUser(string $userId, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->restrictionRepo->paginateForUser($userId, $filters, $perPage);
    }

    // =========================================================================
    // Store
    // =========================================================================

    /**
     * Issue a restriction against a user.
     *
     * Business rules:
     * - Any active restriction of the same type is deactivated first — no duplicates.
     * - full_suspension sets account_status → suspended immediately.
     * - Every action logged to admin_actions.
     */
    public function store(
        User               $target,
        User               $admin,
        StoreRestrictionDTO $dto,
        string             $ip,
    ): UserRestriction {
        // Deactivate any existing active restriction of the same type
        $this->restrictionRepo->deactivateActiveOfType($target, $dto->restrictionType->value);

        $restriction = $this->restrictionRepo->create($target, $admin, $dto);

        // Sync account status for full suspensions
        if ($dto->restrictionType === RestrictionType::FullSuspension) {
            $this->userRepo->update($target, [
                'account_status' => AccountStatus::Suspended->value,
            ]);
        }

        $this->logger->log(
            admin:      $admin,
            actionType: 'restriction_issued',
            targetType: 'user',
            targetId:   $target->id,
            details:    [
                'restriction_id'   => $restriction->id,
                'restriction_type' => $dto->restrictionType->value,
                'reason'           => $dto->reason,
                'duration_hours'   => $dto->durationHours,
                'expires_at'       => $restriction->expires_at?->toISOString(),
            ],
            ip: $ip,
        );

        return $restriction->load(['user', 'restrictedBy']);
    }

    // =========================================================================
    // Lift
    // =========================================================================

    /**
     * Lift a restriction before its expiry.
     *
     * Business rules:
     * - Cannot lift an already-inactive restriction.
     * - If lifted restriction was the last active full_suspension, account_status → active.
     * - Logged to admin_actions.
     */
    public function lift(
        string $restrictionId,
        User   $admin,
        string $ip,
    ): UserRestriction {
        $restriction = $this->restrictionRepo->findById($restrictionId);

        if (! $restriction) {
            throw new RestrictionNotFoundException();
        }

        if (! $restriction->is_active) {
            throw new RestrictionAlreadyLiftedException();
        }

        $lifted = $this->restrictionRepo->lift($restriction, $admin);

        // Restore account if this was the user's last full_suspension
        if ($restriction->restriction_type === RestrictionType::FullSuspension) {
            $hasOtherSuspension = $lifted->user->activeRestrictions()->exists();

            if (! $hasOtherSuspension) {
                $this->userRepo->update($lifted->user, [
                    'account_status' => AccountStatus::Active->value,
                ]);
            }
        }

        $this->logger->log(
            admin:      $admin,
            actionType: 'restriction_lifted',
            targetType: 'user_restriction',
            targetId:   $restriction->id,
            details:    [
                'restriction_type' => $restriction->restriction_type->value,
                'user_id'          => $restriction->user_id,
                'original_reason'  => $restriction->reason,
            ],
            ip: $ip,
        );

        return $lifted;
    }
}
