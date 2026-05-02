<?php

namespace App\Generators;

use App\Enums\FeedbackType;
use App\Enums\IdentityVerificationLevel;
use App\Enums\MatchType;
use App\Models\MatchFeedback;
use App\Models\MatchModel;
use App\Models\Project;
use App\Models\User;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * MatchDatasetGenerator
 *
 * Owns all synthetic training data generation logic.
 * Both MLMatchDatasetSeeder (CLI) and MatchService::generateDataset() (HTTP API)
 * delegate here — neither depends on the other.
 */
class MatchDatasetGenerator
{
    private Generator $faker;

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

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    /**
     * @return array{users: int, projects: int, collaborator_matches: int, project_matches: int}
     */
    public function generate(
        int  $users             = 100,
        int  $projects          = 40,
        int  $collaboratorPairs = 400,
        int  $projectPairs      = 300,
        bool $fresh             = false,
    ): array {
        if ($fresh) {
            DB::table('match_feedback')->delete();
            DB::table('matches')->delete();
        }

        $userCollection    = $this->seedProfiledUsers($users);
        $projectCollection = $this->seedProjectsWithRoles($projects);
        $collabCount       = $this->generateCollaboratorMatches($userCollection, $collaboratorPairs);
        $projectCount      = $this->generateProjectMatches($userCollection, $projectCollection, $projectPairs);

        return [
            'users'                => $userCollection->count(),
            'projects'             => $projectCollection->count(),
            'collaborator_matches' => $collabCount,
            'project_matches'      => $projectCount,
        ];
    }

    private function seedProfiledUsers(int $count): Collection
    {
        return collect(range(1, $count))->map(function () {
            $user = User::factory()->create([
                'location'                    => $this->faker->randomElement(self::LOCATIONS),
                'identity_verified'           => $this->faker->boolean(60),
                'identity_verification_level' => $this->faker->boolean(60)
                    ? IdentityVerificationLevel::Advanced->value
                    : IdentityVerificationLevel::None->value,
            ]);

            foreach (collect(self::SKILL_POOL)->shuffle()->take(rand(3, 8)) as $skill) {
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

    private function seedProjectsWithRoles(int $count): Collection
    {
        return collect(range(1, $count))->map(function () {
            $project = Project::factory()->create(['owner_id' => User::inRandomOrder()->first()->id]);

            foreach (collect(self::SKILL_POOL)->shuffle()->take(rand(2, 4)) as $skill) {
                DB::table('project_skills')->insertOrIgnore([
                    'id'          => (string) Str::uuid(),
                    'project_id'  => $project->id,
                    'skill_name'  => $skill,
                    'is_required' => true
                ]);
            }

            return $project;
        });
    }

    private function generateCollaboratorMatches(Collection $users, int $target): int
    {
        $created = 0;
        $pairs   = [];

        while ($created < $target) {
            [$userA, $userB] = $users->random(2)->values();
            $key = implode('_', [$userA->id, $userB->id]);
            if (isset($pairs[$key])) continue;
            $pairs[$key] = true;

            $features  = $this->computeUserFeatures($userA, $userB);
            $score     = $this->computeCollaboratorScore($features);
            $matchId   = (string) Str::uuid();
            $createdAt = now()->subDays(rand(0, 90));

            DB::table('matches')->insert([
                'id'                  => $matchId,
                'user_id'             => $userA->id,
                'matched_user_id'     => $userB->id,
                'matched_project_id'  => null,
                'match_type'          => MatchType::Collaborator->value,
                'compatibility_score' => $score,
                'match_reasons'       => json_encode($features),
                'viewed'              => $this->faker->boolean(70),
                'viewed_at'           => $this->faker->boolean(70) ? now()->subHours(rand(1, 720)) : null,
                'saved'               => $this->faker->boolean(20),
                'action_taken'        => $this->faker->boolean(30),
                'expires_at'          => now()->addDays(rand(7, 60)),
                'created_at'          => $createdAt,
                'updated_at'          => $createdAt,
            ]);

            $this->simulateFeedback(MatchModel::find($matchId), $userA, $score);
            $created++;
        }

        return $created;
    }

    private function generateProjectMatches(Collection $users, Collection $projects, int $target): int
    {
        $created = 0;
        $pairs   = [];

        while ($created < $target) {
            $user    = $users->random();
            $project = $projects->random();
            if ($project->owner_id === $user->id) continue;

            $key = "{$user->id}_$project->id";
            if (isset($pairs[$key])) continue;
            $pairs[$key] = true;

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
                'viewed'              => $this->faker->boolean(65),
                'viewed_at'           => $this->faker->boolean(65) ? now()->subHours(rand(1, 720)) : null,
                'saved'               => $this->faker->boolean(25),
                'action_taken'        => $this->faker->boolean(35),
                'expires_at'          => now()->addDays(rand(7, 30)),
                'created_at'          => $createdAt,
                'updated_at'          => $createdAt,
            ]);

            $this->simulateFeedback(MatchModel::find($matchId), $user, $score);
            $created++;
        }

        return $created;
    }

    private function computeUserFeatures(User $userA, User $userB): array
    {
        $skillsA      = DB::table('user_skills')->where('user_id', $userA->id)->pluck('skill_name')->toArray();
        $skillsB      = DB::table('user_skills')->where('user_id', $userB->id)->pluck('skill_name')->toArray();
        $intersection = array_intersect($skillsA, $skillsB);
        $union        = array_unique(array_merge($skillsA, $skillsB));

        return [
            'skill_overlap'          => count($union) > 0 ? round(count($intersection) / count($union), 3) : 0.0,
            'complementarity'        => count($union) > 0 ? round((count($union) - count($intersection)) / count($union), 3) : 0.0,
            'overlapping_skills'     => array_values($intersection),
            'unique_skills_a'        => count($skillsA),
            'unique_skills_b'        => count($skillsB),
            'location_match'         => (int) ($userA->location && $userB->location && $userA->location === $userB->location),
            'both_identity_verified' => (int) ($userA->identity_verified && $userB->identity_verified),
            'user_a_verified'        => (int) $userA->identity_verified,
            'user_b_verified'        => (int) $userB->identity_verified,
            'skills_count_a'         => count($skillsA),
            'skills_count_b'         => count($skillsB),
        ];
    }

    private function computeProjectFeatures(User $user, Project $project): array
    {
        $userSkills    = DB::table('user_skills')->where('user_id', $user->id)->pluck('skill_name')->toArray();
        $projectSkills = DB::table('project_skills')->where('project_id', $project->id)->pluck('skill_name')->toArray();
        $covered       = array_intersect($userSkills, $projectSkills);
        $teamSize      = (int) $project->current_team_size;
        $maxSize       = (int) $project->team_size_max;

        return [
            'skill_coverage'         => count($projectSkills) > 0 ? round(count($covered) / count($projectSkills), 3) : 0.0,
            'covered_skills'         => array_values($covered),
            'required_skills_count'  => count($projectSkills),
            'user_skills_count'      => count($userSkills),
            'team_openness'          => $maxSize > 0 ? round(($maxSize - $teamSize) / $maxSize, 3) : 0.0,
            'project_accepting'      => (int) (bool) $project->is_accepting_applications,
            'user_identity_verified' => (int) $user->identity_verified,
            'location_match'         => 0,
        ];
    }

    private function computeCollaboratorScore(array $f): float
    {
        return round(min(max(
            ($f['complementarity']        * 0.35) +
            ($f['skill_overlap']          * 0.25) +
            ($f['location_match']         * 0.15) +
            ($f['both_identity_verified'] * 0.15) +
            (min($f['skills_count_a'] / 8, 1.0) * 0.05) +
            (min($f['skills_count_b'] / 8, 1.0) * 0.05),
            0.01), 1.0), 4);
    }

    private function computeProjectScore(array $f): float
    {
        return round(min(max(
            ($f['skill_coverage']         * 0.50) +
            ($f['team_openness']          * 0.20) +
            ($f['project_accepting']      * 0.15) +
            ($f['user_identity_verified'] * 0.15),
            0.01), 1.0), 4);
    }

    private function simulateFeedback(MatchModel $match, User $user, float $score): void
    {
        if (! $this->faker->boolean(60)) return;

        $rand = $this->faker->numberBetween(1, 100);

        if ($score >= 0.80) {
            $type = $rand <= 70 ? FeedbackType::Relevant : ($rand <= 85 ? FeedbackType::AlreadyConnected : FeedbackType::NotInterested);
        } elseif ($score >= 0.60) {
            $type = $rand <= 50 ? FeedbackType::Relevant : ($rand <= 70 ? FeedbackType::NotRelevant : ($rand <= 85 ? FeedbackType::NotInterested : FeedbackType::AlreadyConnected));
        } else {
            $type = $rand <= 20 ? FeedbackType::Relevant : ($rand <= 70 ? FeedbackType::NotRelevant : FeedbackType::NotInterested);
        }

        MatchFeedback::create(['match_id' => $match->id, 'user_id' => $user->id, 'feedback_type' => $type->value]);

        if (! $match->action_taken) {
            $match->update(['action_taken' => true]);
        }
    }
}
