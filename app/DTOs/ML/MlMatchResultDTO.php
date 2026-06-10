<?php

namespace App\DTOs\ML;

use App\DTOs\Match\IngestMatchDTO;

/**
 * Immutable result from the ML /predict/batch endpoint for one pair.
 *
 * Carries both the scoring output and the routing metadata from MatchPairDTO
 * so we can produce an IngestMatchDTO without needing any extra context.
 */
final readonly class MlMatchResultDTO
{
    public function __construct(
        // Routing — copied from MatchPairDTO
        public string  $userId,
        public ?string $matchedUserId,
        public ?string $matchedProjectId,
        public string  $matchType,

        // Scoring — from the ML response
        public bool    $isRelevant,
        public float   $compatibilityScore,
        public array   $matchReasons,
    ) {}

    /**
     * Build from a MatchPairDTO (routing) + raw ML response element (scoring).
     *
     * @param  array<string, mixed>  $mlResult  One element of response['data']
     */
    public static function fromPairAndResponse(MatchPairDTO $pair, array $mlResult): self
    {
        return new self(
            userId:             $pair->userId,
            matchedUserId:      $pair->matchedUserId,
            matchedProjectId:   $pair->matchedProjectId,
            matchType:          $pair->matchType,
            isRelevant:         (bool)  ($mlResult['is_relevant']         ?? false),
            compatibilityScore: (float) ($mlResult['compatibility_score'] ?? 0.0),
            matchReasons:       (array) ($mlResult['match_reasons']       ?? []),
        );
    }

    /**
     * Convert to IngestMatchDTO for MatchService::ingestBatch().
     */
    public function toIngestDto(string $expiresAt): IngestMatchDTO
    {
        return new IngestMatchDTO(
            userId:             $this->userId,
            matchType:          $this->matchType,
            compatibilityScore: $this->compatibilityScore,
            matchReasons:       $this->matchReasons,
            expiresAt:          $expiresAt,
            matchedUserId:      $this->matchedUserId,
            matchedProjectId:   $this->matchedProjectId,
        );
    }
}
