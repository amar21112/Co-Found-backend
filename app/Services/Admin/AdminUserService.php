<?php

namespace App\Services\Admin;

use App\DTOs\Admin\UpdateAdminUserDTO;
use App\Enums\AccountStatus;
use App\Exceptions\Admin\AdminUserNotFoundException;
use App\Exceptions\Admin\CannotDeleteSelfException;
use App\Models\User;
use App\Repositories\Contracts\AdminUserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

readonly class AdminUserService
{
    public function __construct(
        private AdminUserRepositoryInterface $userRepo,
        private AdminActionLogger            $logger,
    ) {}

    // =========================================================================
    // List
    // =========================================================================

    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->userRepo->paginate($filters, $perPage);
    }

    // =========================================================================
    // Show
    // =========================================================================

    public function show(string $id): User
    {
        $user = $this->userRepo->findById($id);

        if (!$user) {
            throw new AdminUserNotFoundException();
        }

        return $user;
    }

    // =========================================================================
    // Update role / status
    // =========================================================================

    /**
     * Update a user's role or account_status.
     *
     * Business rules:
     * - Only administrators may change roles or statuses.
     * - Logged to admin_actions for audit trail.
     */
    public function update(
        User               $target,
        UpdateAdminUserDTO $dto,
        User               $admin,
        string             $ip,
    ): User {
        $payload = array_filter([
            'role'           => $dto->role,
            'account_status' => $dto->accountStatus,
        ], fn($v) => $v !== null);

        $updated = $this->userRepo->update($target, $payload);

        $this->logger->log(
            admin: $admin,
            actionType: 'user_updated',
            targetType: 'user',
            targetId: $target->id,
            details: ['changes' => $payload],
            ip: $ip,
        );

        return $updated;
    }

    // =========================================================================
    // Get user's identity verification
    // =========================================================================

    /**
     * Return the full identity verification record for any user.
     *
     * Gives the administrator access to document images, biometric check
     * results, and the full review history in one call without having to
     * navigate through the verification queue.
     *
     * Returns null when the user has never submitted verification — the
     * controller exposes this as a 200 with data: null so the caller knows
     * the user simply hasn't submitted yet (vs a 404 which would be ambiguous).
     *
     * @throws AdminUserNotFoundException when the user does not exist
     */
    public function getVerification(string $userId): ?\App\Models\IdentityVerification
    {
        // Assert the user exists first so we return 404 for unknown users
        // rather than silently returning null.
        $this->show($userId);

        return $this->userRepo->findVerificationByUserId($userId);
    }

    // =========================================================================
    // List reports filed against a user
    // =========================================================================

    /**
     * Return all reports filed against a specific user, paginated.
     *
     * Useful when reviewing a user account — the admin gets the full
     * report history without filtering the global reports list manually.
     *
     * @throws AdminUserNotFoundException when the user does not exist
     */
    public function listReports(string $userId, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $this->show($userId); // assert user exists

        return $this->userRepo->paginateReportsForUser($userId, $perPage);
    }

    /**
     * Soft-delete a user account.
     *
     * Business rules:
     * - An administrator cannot delete their own account via this endpoint.
     * - Sets account_status to 'deleted' so guards also block login.
     * - Revokes all active Sanctum tokens immediately.
     * - Logged to admin_actions.
     */
    public function delete(User $target, User $admin, string $ip): void
    {
        if ($target->id === $admin->id) {
            throw new CannotDeleteSelfException();
        }

        // Revoke all tokens so active sessions are killed immediately
        $target->tokens()->delete();

        // Mark status as deleted before soft-deleting for guard consistency
        $this->userRepo->update($target, ['account_status' => AccountStatus::Deleted->value]);

        $this->userRepo->softDelete($target);

        $this->logger->log(
            admin: $admin,
            actionType: 'user_deleted',
            targetType: 'user',
            targetId: $target->id,
            details: [
                'email'    => $target->email,
                'username' => $target->username,
                'role'     => $target->role->value,
            ],
            ip: $ip,
        );
    }
}
