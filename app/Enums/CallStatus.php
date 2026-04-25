<?php

namespace App\Enums;

enum CallStatus: string
{
    case Scheduled = 'scheduled';
    case Active    = 'active';
    case Ended     = 'ended';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Ended, self::Cancelled]);
    }

    public function canBeJoined(): bool
    {
        return in_array($this, [self::Scheduled, self::Active]);
    }
}
