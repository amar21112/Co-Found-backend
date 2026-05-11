<?php

namespace App\DTOs\Report;

final readonly class StoreReportDTO
{
    public function __construct(
        public string  $reporterId,
        public ?string $reportedUserId,
        public ?string $reportedContentType,
        public ?string $reportedContentId,
        public string  $reportType,
        public ?string $description,
        public ?array  $evidence,
    ) {}
}
