<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Pending   = 'pending';
    case Reviewing = 'reviewing';
    case Accepted  = 'accepted';
    case Rejected  = 'rejected';
    case Withdrawn = 'withdrawn';
    case Expired   = 'expired';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Accepted, self::Rejected, self::Withdrawn, self::Expired]);
    }

    public function isReviewable(): bool
    {
        return in_array($this, [self::Pending, self::Reviewing]);
    }
}
