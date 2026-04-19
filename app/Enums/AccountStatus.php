<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Pending   = 'pending';
    case Active    = 'active';
    case Suspended = 'suspended';
    case Banned    = 'banned';
    case Deleted   = 'deleted';

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Statuses that are permanently or administratively blocked from logging in.
     */
    public function isBlocked(): bool
    {
        return in_array($this, [self::Banned, self::Suspended, self::Deleted]);
    }

    /**
     * Statuses that are allowed to authenticate.
     * Pending users get a token but hit the `verified` middleware on write routes.
     */
    public function canAuthenticate(): bool
    {
        return in_array($this, [self::Pending, self::Active]);
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Pending Verification',
            self::Active    => 'Active',
            self::Suspended => 'Suspended',
            self::Banned    => 'Banned',
            self::Deleted   => 'Deleted',
        };
    }
}
