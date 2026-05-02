<?php

namespace App\DTOs\Match;

final readonly class ExportDatasetDTO
{
    public function __construct(
        public string  $format,           // 'csv' | 'json'
        public ?string $type,             // 'collaborator' | 'project' | null = all
        public float   $minScore,
        public bool    $withFeedbackOnly,
    ) {}
}
