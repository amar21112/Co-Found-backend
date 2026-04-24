<?php

namespace App\DTOs\Admin;

use App\Enums\RestrictionType;

final readonly class StoreRestrictionDTO
{
    public function __construct(
        public string          $userId,
        public RestrictionType $restrictionType,
        public string          $reason,
        public ?int            $durationHours,
    ) {}
}
