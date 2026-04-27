<?php

namespace Database\Seeders\ML;

use App\Enums\FeedbackType;
use App\Enums\IdentityVerificationLevel;
use App\Enums\MatchType;
use App\Models\MatchFeedback;
use App\Models\MatchModel;
use App\Models\Project;
use App\Models\User;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * MLMatchDatasetSeeder — generates realistic match training samples.
 *
 * Produces two types of labelled data:
 *  1. Collaborator matches — user ↔ user pairs with skill overlap, location,
 *     experience and identity verification signals.
 *  2. Project matches — user ↔ project pairs with required skill coverage
 *     and team size signals.
 *
 * Each match row contains:
 *  - compatibility_score — ground-truth score computed from feature overlap
 *  - match_reasons       — feature-level breakdown (used as training features)
 *  - feedback_type       — label signal from simulated user feedback
 *
 * Run: php artisan db:seed --class=Database\\Seeders\\ML\\MLMatchDatasetSeeder
 */
class MLMatchDatasetSeeder extends Seeder
{
    private Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    private const SKILL_POOL = [
        'PHP', 'Laravel', 'JavaScript', 'TypeScript', 'React', 'Vue.js',
        'Node.js', 'Python', 'Django', 'FastAPI', 'Java', 'Spring Boot',
        'Go', 'Rust', 'MySQL', 'PostgreSQL', 'MongoDB', 'Redis',
        'Docker', 'Kubernetes', 'AWS', 'Azure', 'GCP',
        'UI/UX Design', 'Figma', 'Product Management',
        'Data Science', 'Machine Learning', 'DevOps',
        'Swift', 'Kotlin', 'Blockchain', 'Solidity',
    ];

    private const LOCATIONS = [
        'Cairo, Egypt', 'Dubai, UAE', 'Riyadh, Saudi Arabia',
        'London, UK', 'Berlin, Germany', 'Amsterdam, Netherlands',
        'San Francisco, USA', 'New York, USA', 'Toronto, Canada',
        'Singapore', 'Sydney, Australia', 'Remote',
    ];

    public function run(): void
    {
        $this->command->info('Generating ML match training dataset...');

        // ── Seed base users with rich profiles ───────────────────────────────
        $users = $this->seedProfiledUsers(100);

        // ── Seed projects with required skills ────────────────────────────────
        $projects = $this->seedProjectsWithRoles(40);

        // ── Generate collaborator match pairs ─────────────────────────────────
        $this->command->info('  Generating collaborator match pairs...');
        $collabCount = $this->generateCollaboratorMatches($users, 400);

        // ── Generate project match pairs ──────────────────────────────────────
        $this->command->info('  Generating project match pairs...');
        $projectCount = $this->generateProjectMatches($users, $projects, 300);

        $this->command->info('');
        $this->command->info("  ✓ Users created:              100");
        $this->command->info("  ✓ Projects created:           40");
        $this->command->info("  ✓ Collaborator matches:       $collabCount");
        $this->command->info("  ✓ Project matches:            $projectCount");
        $this->command->info("  ✓ Total match records:        " . ($collabCount + $projectCount));
        $this->command->info('');
        $this->command->info('  Export training CSV with:');
        $this->command->info('  php artisan ml:export-matches --output=storage/app/ml/matches.csv');
    }

    // =========================================================================
    // User seeding
    // =========================================================================

    private function seedProfiledUsers(int $count): Collection
    {
        return collect(range(1, $count))->map(function () {
            $location = $this->faker()->randomElement(self::LOCATIONS);
            $user     = User::factory()->create([
                'location'                   => $location,
                'identity_verified'          => $this->faker()->boolean(60),
                'identity_verification_level'=> $this->faker()->boolean(60)
                    ? IdentityVerificationLevel::Advanced->value
                    : IdentityVerificationLevel::None->value,
            ]);

            // 3–8 skills per user with proficiency levels
            $skills = collect(self::SKILL_POOL)->shuffle()->take(rand(3, 8));
            foreach ($skills as $skill) {
                DB::table('user_skills')->insertOrIgnore([
                    'id'                => (string) Str::uuid(),
                    'user_id'           => $user->id,
                    'skill_name'        => $skill,
                    'proficiency_level' => rand(1, 5),
                    'years_experience'  => round(rand(1, 100) / 10, 1),
                    'is_approved'       => true,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }

            return $user;
        });
    }

    // =========================================================================
    // Project seeding
    // =========================================================================

    private function seedProjectsWithRoles(int $count): Collection
    {
        return collect(range(1, $count))->map(function () {
            $owner   = User::inRandomOrder()->first();
            $project = Project::factory()->create(['owner_id' => $owner->id]);

            // Add 2–4 required skills to the project
            $required = collect(self::SKILL_POOL)->shuffle()->take(rand(2, 4));
            foreach ($required as $skill) {
                DB::table('project_skills')->insertOrIgnore([
                    'id'           => (string) Str::uuid(),
                    'project_id'   => $project->id,
                    'skill_name'   => $skill,
                    'is_required'  => true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            return $project;
        });
    }

    // =========================================================================
    // Collaborator match generation
    // =========================================================================

    private function generateCollaboratorMatches($users, int $target): int
    {
        $created = 0;
        $pairs   = [];

        while ($created < $target) {
            [$userA, $userB] = $users->random(2)->values();

            $pairKey = implode('_', [$userA->id, $userB->id]);
            if (isset($pairs[$pairKey])) continue;
            $pairs[$pairKey] = true;

            $features = $this->computeUserFeatures($userA, $userB);
            $score    = $this->computeCollaboratorScore($features);
            $matchId  = (string) Str::uuid();
            $createdAt = now()->subDays(rand(0, 90));

            // Use DB::table() to bypass Eloquent's timestamp auto-fill
            // so created_at can represent historical data for ML training.
            DB::table('matches')->insert([
                'id'                  => $matchId,
                'user_id'             => $userA->id,
                'matched_user_id'     => $userB->id,
                'matched_project_id'  => null,
                'match_type'          => MatchType::Collaborator->value,
                'compatibility_score' => $score,
                'match_reasons'       => json_encode($features),
                'viewed'              => $this->faker()->boolean(70),
                'viewed_at'           => $this->faker()->boolean(70) ? now()->subHours(rand(1, 720)) : null,
                'saved'               => $this->faker()->boolean(20),
                'action_taken'        => $this->faker()->boolean(30),
                'expires_at'          => now()->addDays(rand(7, 60)),
                'created_at'          => $createdAt,
                'updated_at'          => $createdAt,
            ]);

            $match = MatchModel::find($matchId);
            $this->simulateFeedback($match, $userA, $score);

            $created++;
        }

        return $created;
    }

    // =========================================================================
    // Project match generation
    // =========================================================================

    private function generateProjectMatches($users, $projects, int $target): int
    {
        $created = 0;
        $pairs   = [];

        while ($created < $target) {
            $user    = $users->random();
            $project = $projects->random();

            if ($project->owner_id === $user->id) continue;

            $pairKey = "{$user->id}_$project->id";
            if (isset($pairs[$pairKey])) continue;
            $pairs[$pairKey] = true;

            $features  = $this->computeProjectFeatures($user, $project);
            $score     = $this->computeProjectScore($features);
            $matchId   = (string) Str::uuid();
            $createdAt = now()->subDays(rand(0, 90));

            DB::table('matches')->insert([
                'id'                  => $matchId,
                'user_id'             => $user->id,
                'matched_user_id'     => null,
                'matched_project_id'  => $project->id,
                'match_type'          => MatchType::Project->value,
                'compatibility_score' => $score,
                'match_reasons'       => json_encode($features),
                'viewed'              => $this->faker()->boolean(65),
                'viewed_at'           => $this->faker()->boolean(65) ? now()->subHours(rand(1, 720)) : null,
                'saved'               => $this->faker()->boolean(25),
                'action_taken'        => $this->faker()->boolean(35),
                'expires_at'          => now()->addDays(rand(7, 30)),
                'created_at'          => $createdAt,
                'updated_at'          => $createdAt,
            ]);

            $match = MatchModel::find($matchId);
            $this->simulateFeedback($match, $user, $score);

            $created++;
        }

        return $created;
    }

    // =========================================================================
    // Feature computation
    // =========================================================================

    /**
     * Compute pairwise user features for collaborator matching.
     * These features map directly to ML input columns.
     */
    private function computeUserFeatures(User $userA, User $userB): array
    {
        $skillsA = DB::table('user_skills')->where('user_id', $userA->id)->pluck('skill_name')->toArray();
        $skillsB = DB::table('user_skills')->where('user_id', $userB->id)->pluck('skill_name')->toArray();

        $intersection = array_intersect($skillsA, $skillsB);
        $union        = array_unique(array_merge($skillsA, $skillsB));

        $skillOverlap = count($union) > 0
            ? round(count($intersection) / count($union), 3)
            : 0.0;

        // Complementarity — how different their skills are (good for team formation)
        $complementarity = count($union) > 0
            ? round((count($union) - count($intersection)) / count($union), 3)
            : 0.0;

        $locationMatch = $userA->location && $userB->location
            ? (int) ($userA->location === $userB->location)
            : 0;

        return [
            'skill_overlap'         => $skillOverlap,
            'complementarity'       => $complementarity,
            'overlapping_skills'    => array_values($intersection),
            'unique_skills_a'       => count($skillsA),
            'unique_skills_b'       => count($skillsB),
            'location_match'        => $locationMatch,
            'both_identity_verified'=> (int) ($userA->identity_verified && $userB->identity_verified),
            'user_a_verified'       => (int) $userA->identity_verified,
            'user_b_verified'       => (int) $userB->identity_verified,
            'skills_count_a'        => count($skillsA),
            'skills_count_b'        => count($skillsB),
        ];
    }

    /**
     * Compute user-to-project features for project matching.
     */
    private function computeProjectFeatures(User $user, Project $project): array
    {
        $userSkills    = DB::table('user_skills')->where('user_id', $user->id)->pluck('skill_name')->toArray();
        $projectSkills = DB::table('project_skills')->where('project_id', $project->id)->pluck('skill_name')->toArray();

        $covered   = array_intersect($userSkills, $projectSkills);
        $coverage  = count($projectSkills) > 0
            ? round(count($covered) / count($projectSkills), 3)
            : 0.0;

        $teamSize        = (int) $project->current_team_size;
        $maxSize         = (int) $project->team_size_max;
        $teamOpenness    = $maxSize > 0 ? round(($maxSize - $teamSize) / $maxSize, 3) : 0.0;

        return [
            'skill_coverage'          => $coverage,
            'covered_skills'          => array_values($covered),
            'required_skills_count'   => count($projectSkills),
            'user_skills_count'       => count($userSkills),
            'team_openness'           => $teamOpenness,
            'project_accepting'       => (int) (bool) $project->is_accepting_applications,
            'user_identity_verified'  => (int) $user->identity_verified,
            'location_match'          => 0, // extended in production with project location
        ];
    }

    // =========================================================================
    // Score computation
    // =========================================================================

    /**
     * Weighted scoring formula for collaborator matches.
     * Weights tuned to reflect Co-Found's platform goals:
     * complementarity > overlap (teams need diverse skills).
     */
    private function computeCollaboratorScore(array $f): float
    {
        $score =
            ($f['complementarity']        * 0.35) +
            ($f['skill_overlap']          * 0.25) +
            ($f['location_match']         * 0.15) +
            ($f['both_identity_verified'] * 0.15) +
            (min($f['skills_count_a'] / 8, 1.0)  * 0.05) +
            (min($f['skills_count_b'] / 8, 1.0)  * 0.05);

        return round(min(max($score, 0.01), 1.0), 4);
    }

    /**
     * Weighted scoring formula for project matches.
     * Coverage is the dominant signal — the user must cover required skills.
     */
    private function computeProjectScore(array $f): float
    {
        $score =
            ($f['skill_coverage']         * 0.50) +
            ($f['team_openness']          * 0.20) +
            ($f['project_accepting']      * 0.15) +
            ($f['user_identity_verified'] * 0.15);

        return round(min(max($score, 0.01), 1.0), 4);
    }

    // =========================================================================
    // Feedback simulation
    // =========================================================================

    /**
     * Simulate realistic user feedback based on score.
     * Higher scoring matches are more likely to receive positive feedback.
     * ~60% of matches receive feedback (simulating real engagement rates).
     */
    private function simulateFeedback(MatchModel $match, User $user, float $score): void
    {
        // Only 60% of matches receive feedback
        if (! $this->faker()->boolean(60)) return;

        $feedbackType = $this->selectFeedbackType($score);

        MatchFeedback::create([
            'match_id'      => $match->id,
            'user_id'       => $user->id,
            'feedback_type' => $feedbackType->value,
        ]);

        if (! $match->action_taken) {
            $match->update(['action_taken' => true]);
        }
    }

    /**
     * Select a realistic feedback type based on the match score.
     *
     * Score ≥ 0.80 → 70% relevant, 15% already_connected, 15% not_interested
     * Score 0.60–0.80 → 50% relevant, 20% not_relevant, 30% other
     * Score < 0.60 → 20% relevant, 50% not_relevant, 30% other
     */
    private function selectFeedbackType(float $score): FeedbackType
    {
        $rand = $this->faker()->numberBetween(1, 100);

        if ($score >= 0.80) {
            if ($rand <= 70) return FeedbackType::Relevant;
            if ($rand <= 85) return FeedbackType::AlreadyConnected;
            return FeedbackType::NotInterested;
        }

        if ($score >= 0.60) {
            if ($rand <= 50) return FeedbackType::Relevant;
            if ($rand <= 70) return FeedbackType::NotRelevant;
            if ($rand <= 85) return FeedbackType::NotInterested;
            return FeedbackType::AlreadyConnected;
        }

        // Score < 0.60
        if ($rand <= 20) return FeedbackType::Relevant;
        if ($rand <= 70) return FeedbackType::NotRelevant;
        return FeedbackType::NotInterested;
    }

    private function faker(): Generator
    {
        return $this->faker;
    }
}
