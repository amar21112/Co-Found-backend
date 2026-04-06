<?php

namespace App\Services;

use App\Models\User;
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
}
