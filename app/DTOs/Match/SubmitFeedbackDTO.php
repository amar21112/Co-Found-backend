<?php

namespace App\DTOs\Match;

use App\Enums\FeedbackType;

final readonly class SubmitFeedbackDTO
{
    public function __construct(
        public FeedbackType $feedbackType,
    ) {}
}
