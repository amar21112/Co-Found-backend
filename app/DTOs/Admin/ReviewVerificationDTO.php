<?php

namespace App\DTOs\Admin;

use App\Enums\ReviewAction;
use App\Enums\RejectionReasonCategory;

final readonly class ReviewVerificationDTO
{
    public function __construct(
        public ReviewAction             $reviewAction,
        public ?string                  $reviewNotes,
        public ?RejectionReasonCategory $rejectionReasonCategory,
        public bool                     $automatedChecksPassed,
        public ?array                   $automatedChecksData,
    ) {}
}
