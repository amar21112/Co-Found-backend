<?php

namespace App\Enums;

enum RejectionReasonCategory: string
{
    case Forgery  = 'forgery';
    case Expired  = 'expired';
    case Unclear  = 'unclear';
    case Mismatch = 'mismatch';
    case Other    = 'other';

    public function label(): string
    {
        return match($this) {
            self::Forgery  => 'Document appears forged',
            self::Expired  => 'Document is expired',
            self::Unclear  => 'Document image is unclear',
            self::Mismatch => 'Information does not match',
            self::Other    => 'Other reason',
        };
    }
}
