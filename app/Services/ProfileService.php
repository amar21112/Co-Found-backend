<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    public function __construct(
        private readonly ProfilePictureService $pictureService,
    ) {}

    // =========================================================================
    // Profile Update
    // =========================================================================

    /**
     * Update the authenticated user's profile fields.
     *
     * When a profile_picture file is present it is stored via
     * ProfilePictureService and the old file is deleted. The stored
     * relative path is written to profile_picture_url.
     *
     * @param array $data  Validated data from UpdateProfileRequest.
     *                     May contain 'profile_picture' as an UploadedFile.
     */
    public function updateProfile(User $user, array $data): User
    {
        // ── Handle profile picture upload ─────────────────────────────────────
        if (isset($data['profile_picture']) && $data['profile_picture'] instanceof UploadedFile) {
            $this->pictureService->delete($user->profile_picture_url);

            $data['profile_picture_url'] = $this->pictureService->store($data['profile_picture']);
        }

        // Remove the file key — the User model only knows profile_picture_url
        unset($data['profile_picture']);

        $user->update($data);

        return $user->fresh();
    }

    // =========================================================================
    // Password Change
    // =========================================================================

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

    // =========================================================================
    // Public Profile
    // =========================================================================

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

    // =========================================================================
    // User Search
    // =========================================================================

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

        if (! empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('full_name', 'LIKE', $term)
                    ->orWhere('username', 'LIKE', $term)
                    ->orWhere('location', 'LIKE', $term);
            });
        }

        if (! empty($filters['location'])) {
            $query->where('location', 'LIKE', '%' . $filters['location'] . '%');
        }

        if (isset($filters['identity_verified']) && $filters['identity_verified'] !== '') {
            $query->where(
                'identity_verified',
                filter_var($filters['identity_verified'], FILTER_VALIDATE_BOOLEAN)
            );
        }

        $allowed = ['full_name', 'last_login_at', 'created_at'];
        $sortBy  = in_array($filters['sort_by'] ?? '', $allowed) ? $filters['sort_by'] : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) ($filters['per_page'] ?? 15), 50);

        return $query->paginate($perPage);
    }
}
