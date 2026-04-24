<?php

namespace App\Enums;

enum ReviewAction: string
{
    case Approved             = 'approved';
    case Rejected             = 'rejected';
    case RequestResubmission  = 'request_resubmission';

    public function approvesUser(): bool
    {
        return $this === self::Approved;
    }

    public function label(): string
    {
        return match($this) {
            self::Approved            => 'Approved',
            self::Rejected            => 'Rejected',
            self::RequestResubmission => 'Resubmission Requested',
        };
    }
}
