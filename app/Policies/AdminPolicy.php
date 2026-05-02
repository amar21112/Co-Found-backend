<?php

namespace App\Policies;

use App\Models\User;

/**
 * Gates all admin panel actions.
 *
 * Moderators can access the verification queue, reports, moderation log,
 * action audit log, and system logs.
 *
 * Administrators have all moderator rights plus elevated actions:
 * user management (role/status/delete) and system settings.
 *
 * Usage in controllers:
 *   $this->authorize('moderate', ModelClass::class)     — moderator+
 *   $this->authorize('administrate', ModelClass::class) — administrator only
 */
class AdminPolicy
{
    /**
     * Can access any moderator-level action.
     * Covers: verification queue, reports, moderation log,
     *         action audit log, system logs, restrictions.
     */
    public function moderate(User $user): bool
    {
        return $user->isModerator(); // includes administrators
    }

    /**
     * Can perform administrator-only actions.
     * Covers: user role/status changes, user soft-delete,
     *         system settings read/write.
     */
    public function administrate(User $user): bool
    {
        return $user->isAdmin();
    }
}
