<?php

namespace App\DTOs\ML;

/**
 * Input DTO for a single match pair sent to POST /predict/batch.
 *
 * Field names match the FastAPI Pydantic schemas (CollaboratorInput / ProjectInput)
 * exactly — do not rename them without updating the ML service schema.
 *
 * Two factory methods enforce the correct shape per match type:
 *   MatchPairDTO::collaborator(...)
 *   MatchPairDTO::project(...)
 */
final readonly class MatchPairDTO
{
    private function __construct(
        // ── Routing (not sent to ML — used by MlMatchResultDTO to build IngestMatchDTO) ──
        public string  $userId,
        public ?string $matchedUserId,
        public ?string $matchedProjectId,

        // ── ML feature fields (sent as-is in the JSON body) ──
        public string $matchType,
        public float  $skillOverlap,
        public float  $complementarity,
        public int    $overlappingSkillsCount,
        public int    $skillsCountA,
        public int    $skillsCountB,
        public int    $uniqueSkillsA,
        public int    $uniqueSkillsB,
        public int    $userAVerified,
        public int    $userBVerified,
        public int    $bothIdentityVerified,
        public int    $locationMatch,
        public int    $sameLocation,
        public int    $userIdentityVerified,
        public float  $compatibilityScore,
        public int    $viewed,
        public int    $saved,
        public int    $actionTaken,
        public int    $projectAccepting,
        public float  $teamOpenness,
        public int    $coveredSkillsCount,
    ) {}

    // ── Factories ─────────────────────────────────────────────────────────────

    public static function collaborator(
        string $userId,
        string $matchedUserId,
        float  $skillOverlap,
        int    $overlappingSkillsCount,
        int    $skillsCountA,
        int    $skillsCountB,
        int    $userAVerified,
        int    $userBVerified,
        int    $locationMatch,
    ): self {
        return new self(
            userId:                $userId,
            matchedUserId:         $matchedUserId,
            matchedProjectId:      null,
            matchType:             'collaborator',
            skillOverlap:          $skillOverlap,
            complementarity:       round(1 - $skillOverlap, 4),
            overlappingSkillsCount:$overlappingSkillsCount,
            skillsCountA:          $skillsCountA,
            skillsCountB:          $skillsCountB,
            uniqueSkillsA:         $skillsCountA,
            uniqueSkillsB:         $skillsCountB,
            userAVerified:         $userAVerified,
            userBVerified:         $userBVerified,
            bothIdentityVerified:  (int) ($userAVerified && $userBVerified),
            locationMatch:         $locationMatch,
            sameLocation:          $locationMatch,
            userIdentityVerified:  $userAVerified,
            compatibilityScore:    0.0,
            viewed:                0,
            saved:                 0,
            actionTaken:           0,
            projectAccepting:      0,
            teamOpenness:          0.0,
            coveredSkillsCount:    0,
        );
    }

    public static function project(
        string $userId,
        string $matchedProjectId,
        int    $skillsCountA,
        int    $coveredSkillsCount,
        int    $projectAccepting,
        float  $teamOpenness,
        int    $userIdentityVerified,
        int    $locationMatch,
    ): self {
        return new self(
            userId:                $userId,
            matchedUserId:         null,
            matchedProjectId:      $matchedProjectId,
            matchType:             'project',
            skillOverlap:          0.0,
            complementarity:       0.0,
            overlappingSkillsCount:0,
            skillsCountA:          $skillsCountA,
            skillsCountB:          0,
            uniqueSkillsA:         $skillsCountA,
            uniqueSkillsB:         0,
            userAVerified:         0,
            userBVerified:         0,
            bothIdentityVerified:  0,
            locationMatch:         $locationMatch,
            sameLocation:          $locationMatch,
            userIdentityVerified:  $userIdentityVerified,
            compatibilityScore:    0.0,
            viewed:                0,
            saved:                 0,
            actionTaken:           0,
            projectAccepting:      $projectAccepting,
            teamOpenness:          $teamOpenness,
            coveredSkillsCount:    $coveredSkillsCount,
        );
    }

    /**
     * Serialise to the exact JSON shape the FastAPI endpoint expects.
     * camelCase → snake_case mapping happens here, in one place.
     *
     * @return array<string, mixed>
     */
    public function toMlPayload(): array
    {
        return [
            'match_type'               => $this->matchType,
            'skill_overlap'            => $this->skillOverlap,
            'complementarity'          => $this->complementarity,
            'overlapping_skills_count' => $this->overlappingSkillsCount,
            'skills_count_a'           => $this->skillsCountA,
            'skills_count_b'           => $this->skillsCountB,
            'unique_skills_a'          => $this->uniqueSkillsA,
            'unique_skills_b'          => $this->uniqueSkillsB,
            'user_a_verified'          => $this->userAVerified,
            'user_b_verified'          => $this->userBVerified,
            'both_identity_verified'   => $this->bothIdentityVerified,
            'location_match'           => $this->locationMatch,
            'same_location'            => $this->sameLocation,
            'user_identity_verified'   => $this->userIdentityVerified,
            'compatibility_score'      => $this->compatibilityScore,
            'viewed'                   => $this->viewed,
            'saved'                    => $this->saved,
            'action_taken'             => $this->actionTaken,
            'project_accepting'        => $this->projectAccepting,
            'team_openness'            => $this->teamOpenness,
            'covered_skills_count'     => $this->coveredSkillsCount,
        ];
    }
}
