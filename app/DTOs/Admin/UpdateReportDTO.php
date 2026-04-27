<?php

namespace App\DTOs\Admin;

final readonly class UpdateReportDTO
{
    public function __construct(
        public ?string $status,
        public ?string $priority,
        public ?string $assignedTo,
        public ?string $resolutionAction,
        public ?string $resolutionNotes,
        public ?string $resolvedBy,
    ) {}
}
