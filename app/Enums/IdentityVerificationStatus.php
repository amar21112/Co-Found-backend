<?php

namespace App\Enums;

enum IdentityVerificationStatus: string
{
    case Pending     = 'pending';
    case UnderReview = 'under_review';
    case Verified    = 'verified';
    case Rejected    = 'rejected';
    case Escalated   = 'escalated';

    public function isApproved(): bool
    {
        return $this === self::Verified;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Verified, self::Rejected]);
    }

    public function isInReview(): bool
    {
        return in_array($this, [self::Pending, self::UnderReview, self::Escalated]);
    }

    public function label(): string
    {
        return match($this) {
            self::Pending     => 'Pending Submission',
            self::UnderReview => 'Under Review',
            self::Verified    => 'Verified',
            self::Rejected    => 'Rejected',
            self::Escalated   => 'Escalated',
        };
    }
}
