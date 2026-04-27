<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\AdminUserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminUserRepository implements AdminUserRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        // withTrashed so admins can see soft-deleted accounts too.
        // identityVerification summary (no nested relations) so the list
        // shows each user's verification status at a glance.
        $query = User::withTrashed()
            ->with(['identityVerification'])
            ->orderByDesc('created_at');

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('full_name', 'LIKE', $term)
                    ->orWhere('username',  'LIKE', $term)
                    ->orWhere('email',     'LIKE', $term);
            });
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['account_status'])) {
            $query->where('account_status', $filters['account_status']);
        }

        if (isset($filters['identity_verified']) && $filters['identity_verified'] !== '') {
            $query->where(
                'identity_verified',
                filter_var($filters['identity_verified'], FILTER_VALIDATE_BOOLEAN),
            );
        }

        if (isset($filters['email_verified']) && $filters['email_verified'] !== '') {
            $query->where(
                'email_verified',
                filter_var($filters['email_verified'], FILTER_VALIDATE_BOOLEAN),
            );
        }

        return $query->paginate($perPage);
    }

    public function findById(string $id): ?User
    {
        // withTrashed so admins can inspect deleted accounts.
        //
        // Eager-loads:
        //   identityVerification.reviews — full doc + review history
        //   identityVerification.latestReview — latest reviewer shortcut
        //   activeRestrictions.restrictedBy — current bans/suspensions
        //   reportsReceived — count of reports filed against this user
        return User::withTrashed()
            ->with([
                'identityVerification.reviews.reviewer:id,username,full_name,role',
                'identityVerification.latestReview',
                'activeRestrictions.restrictedBy:id,username',
                'reportsReceived',
            ])
            ->find($id);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    public function softDelete(User $user): void
    {
        $user->delete(); // Eloquent SoftDeletes trait
    }

    public function findVerificationByUserId(string $userId): ?\App\Models\IdentityVerification
    {
        return \App\Models\IdentityVerification::with([
            'reviews.reviewer:id,username,full_name,role',
            'latestReview.reviewer:id,username,full_name,role',
        ])->where('user_id', $userId)->first();
    }

    public function paginateReportsForUser(string $userId, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return \App\Models\Report::with([
            'reporter:id,username,full_name,profile_picture_url',
            'assignedModerator:id,username,full_name',
            'resolver:id,username,full_name',
        ])
            ->where('reported_user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
