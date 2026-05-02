<?php

namespace App\DTOs\Report;

final readonly class UpdateReportDTO
{
    public function __construct(
        public ?string $description,
        public ?array  $evidence,
    ) {}
}
