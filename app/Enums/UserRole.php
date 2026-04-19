<?php

namespace App\Enums;

enum UserRole: string
{
    case Guest         = 'guest';
    case RegularUser   = 'regular_user';
    case Moderator     = 'moderator';
    case Administrator = 'administrator';

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this === self::Administrator;
    }

    public function isModerator(): bool
    {
        return in_array($this, [self::Administrator, self::Moderator]);
    }

    public function isGuest(): bool
    {
        return $this === self::Guest;
    }

    public function isRegularUser(): bool
    {
        return $this === self::RegularUser;
    }

    public function canAccessAdminPanel(): bool
    {
        return $this->isModerator();
    }

    public function label(): string
    {
        return match($this) {
            self::Guest         => 'Guest',
            self::RegularUser   => 'Regular User',
            self::Moderator     => 'Moderator',
            self::Administrator => 'Administrator',
        };
    }
}
