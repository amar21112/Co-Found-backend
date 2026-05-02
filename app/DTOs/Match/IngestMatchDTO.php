<?php

namespace App\DTOs\Match;

final readonly class IngestMatchDTO
{
    public function __construct(
        public string  $userId,
        public string  $matchType,
        public float   $compatibilityScore,
        public array   $matchReasons,
        public string  $expiresAt,
        public ?string $matchedUserId    = null,
        public ?string $matchedProjectId = null,
    ) {}
}
