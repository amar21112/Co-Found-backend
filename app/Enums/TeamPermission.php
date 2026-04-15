<?php

namespace App\Enums;

enum TeamPermission: string
{
    case Owner  = 'owner';
    case Admin  = 'admin';
    case Member = 'member';

    public function canManageTeam(): bool
    {
        return in_array($this, [self::Owner, self::Admin]);
    }

    public function canManageProject(): bool
    {
        return $this === self::Owner;
    }
}
