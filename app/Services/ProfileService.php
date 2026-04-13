<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    /**
     * Update the authenticated user's profile fields.
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    /**
     * Change the user's password after verifying the current one.
     *
     * @throws ValidationException
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }

        $user->update(['password' => Hash::make($newPassword)]);
    }

    /**
     * Return a public profile view.
     * Hides private portfolio items from non-owners.
     */
    public function getPublicProfile(User $viewer, User $target): User
    {
        $target->load([
            'skills.endorsements.endorser',
            'portfolioItems' => function ($query) use ($viewer, $target) {
                if ($viewer->id !== $target->id) {
                    $query->where('visibility', 'public');
                }
                $query->with('skills');
            },
        ]);

        return $target;
    }

    /**
     * Search / browse public users.
     *
     * Filters  : search (full_name, username, location), location, identity_verified
     * Sort     : sort_by (created_at | full_name | last_login_at), sort_dir (asc | desc)
     * Paginate : per_page (default 15, max 50)
     */
    public function searchUsers(array $filters): LengthAwarePaginator
    {
        $query = User::query()
            ->where('account_status', 'active')
            ->whereNull('deleted_at');

        // Full-text search across name, username, and location
        if (! empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('full_name', 'LIKE', $term)
                    ->orWhere('username', 'LIKE', $term)
                    ->orWhere('location', 'LIKE', $term);
            });
        }

        // Filter by location (city / country substring)
        if (! empty($filters['location'])) {
            $query->where('location', 'LIKE', '%' . $filters['location'] . '%');
        }

        // Filter by identity verification status
        if (isset($filters['identity_verified']) && $filters['identity_verified'] !== '') {
            $query->where(
                'identity_verified',
                filter_var($filters['identity_verified'], FILTER_VALIDATE_BOOLEAN)
            );
        }

        // Sorting — whitelist allowed columns
        $allowed = ['full_name', 'last_login_at', 'created_at'];
        $sortBy  = in_array($filters['sort_by'] ?? '', $allowed) ? $filters['sort_by'] : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) ($filters['per_page'] ?? 15), 50);

        return $query->paginate($perPage);
    }
}
