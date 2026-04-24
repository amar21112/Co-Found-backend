<?php

namespace App\Policies;

use App\Models\User;

/**
 * Gates all admin panel actions.
 *
 * Moderators can access verification queue and issue restrictions.
 * Administrators have all moderator rights plus elevated actions.
 *
 * Usage in controllers: $this->authorize('moderate')
 */
class AdminPolicy
{
    /**
     * Can access any admin/moderator action.
     */
    public function moderate(User $user): bool
    {
        return $user->isModerator();
    }

    /**
     * Can perform administrator-only actions (e.g. account banning).
     */
    public function administrate(User $user): bool
    {
        return $user->isAdmin();
    }
}
