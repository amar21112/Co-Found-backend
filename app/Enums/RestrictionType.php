<?php

namespace App\Enums;

enum RestrictionType: string
{
    case MessagingBan    = 'messaging_ban';
    case PostingBan      = 'posting_ban';
    case ApplicationBan  = 'application_ban';
    case FullSuspension  = 'full_suspension';

    /**
     * Returns true for restriction types that completely block login.
     * Targeted bans (messaging, posting, applications) still allow login.
     */
    public function blocksLogin(): bool
    {
        return $this === self::FullSuspension;
    }
}
