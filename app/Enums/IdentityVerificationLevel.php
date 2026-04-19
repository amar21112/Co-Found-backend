<?php

namespace App\Enums;

enum IdentityVerificationLevel: string
{
    case None     = 'none';
    case Basic    = 'basic';
    case Advanced = 'advanced';

    public function isVerified(): bool
    {
        return $this !== self::None;
    }

    public function label(): string
    {
        return match($this) {
            self::None     => 'Not Verified',
            self::Basic    => 'Basic Verification',
            self::Advanced => 'Advanced Verification',
        };
    }
}
