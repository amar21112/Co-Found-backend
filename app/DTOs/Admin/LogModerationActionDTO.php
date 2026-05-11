<?php

namespace App\DTOs\Admin;

final readonly class LogModerationActionDTO
{
    public function __construct(
        public string  $moderatorId,
        public string  $contentType,
        public string  $contentId,
        public string  $moderationType,
        public ?string $originalContent,
        public ?string $moderatedContent,
        public string  $actionTaken,
        public string  $reason,
        public ?string $guidelineReferenced,
    ) {}
}
