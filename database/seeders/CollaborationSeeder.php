<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserConnection;
use App\Models\CollaborationInvitation;
use App\Models\CollaborationRating;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CollaborationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 'regular_user')->inRandomOrder()->get();

        // Use DB::table to avoid any SoftDeletes / global scope on the Project model
        $projects = \DB::table('projects')->inRandomOrder()->take(20)->get();

        $this->seedConnections($users);
        $this->seedMatches($users, $projects);
        $this->seedInvitations($users, $projects);
        $this->seedRatings($users, $projects);

        $this->command->info('CollaborationSeeder: connections, matches, invitations, and ratings seeded.');
    }

    private function seedConnections($users): void
    {
        $pairs = collect();

        for ($i = 0; $i < 60; $i++) {
            $a   = $users->random();
            $b   = $users->where('id', '!=', $a->id)->random();
            $key = collect([$a->id, $b->id])->sort()->implode('-');
            if ($pairs->contains($key)) continue;
            $pairs->push($key);

            \DB::table('user_connections')->insertOrIgnore([
                'id'              => Str::uuid(),
                'requester_id'    => $a->id,
                'recipient_id'    => $b->id,
                'status'          => fake()->randomElement(['pending', 'accepted', 'accepted', 'accepted']),
                'connection_type' => fake()->randomElement(['co_founder', 'collaborator', 'mentor', null]),
                'created_at'      => now()->subDays(rand(1, 60)),
                'updated_at'      => now(),
            ]);
        }
    }

    private function seedMatches($users, $projects): void
    {
        // User-to-user matches
        for ($i = 0; $i < 40; $i++) {
            $user    = $users->random();
            $matched = $users->where('id', '!=', $user->id)->random();

            \DB::table('matches')->insertOrIgnore([
                'id'                  => Str::uuid(),
                'user_id'             => $user->id,
                'matched_user_id'     => $matched->id,
                'matched_project_id'  => null,
                'match_type'          => 'user_to_user',
                'compatibility_score' => round(rand(50, 100) / 100, 2),
                'match_reasons'       => json_encode([
                    'skills_overlap'   => round(rand(30, 100) / 100, 2),
                    'location_match'   => (bool) rand(0, 1),
                    'availability_fit' => (bool) rand(0, 1),
                ]),
                'viewed'       => (bool) rand(0, 1),
                'viewed_at'    => rand(0, 1) ? now()->subDays(rand(1, 7)) : null,
                'saved'        => (bool) (rand(0, 4) === 0),
                'action_taken' => (bool) (rand(0, 3) === 0),
                'created_at'   => now()->subDays(rand(1, 30)),
                'expires_at'   => now()->addDays(rand(7, 30)),
            ]);
        }

        // User-to-project matches
        foreach ($users->take(30) as $user) {
            $project = $projects->random();
            \DB::table('matches')->insertOrIgnore([
                'id'                  => Str::uuid(),
                'user_id'             => $user->id,
                'matched_user_id'     => null,
                'matched_project_id'  => $project->id,
                'match_type'          => 'user_to_project',
                'compatibility_score' => round(rand(50, 100) / 100, 2),
                'match_reasons'       => json_encode([
                    'skills_overlap' => round(rand(40, 100) / 100, 2),
                    'category_fit'   => (bool) rand(0, 1),
                ]),
                'viewed'       => (bool) rand(0, 1),
                'viewed_at'    => rand(0, 1) ? now()->subDays(rand(1, 7)) : null,
                'saved'        => false,
                'action_taken' => false,
                'created_at'   => now()->subDays(rand(1, 14)),
                'expires_at'   => now()->addDays(rand(7, 21)),
            ]);
        }
    }

    private function seedInvitations($users, $projects): void
    {
        for ($i = 0; $i < 30; $i++) {
            $sender    = $users->random();
            $recipient = $users->where('id', '!=', $sender->id)->random();
            $status    = fake()->randomElement(['pending', 'accepted', 'declined', 'expired', 'withdrawn']);

            \DB::table('collaboration_invitations')->insert([
                'id'               => Str::uuid(),
                'sender_id'        => $sender->id,
                'recipient_id'     => $recipient->id,
                'project_id'       => $projects->random()->id,
                'invitation_type'  => fake()->randomElement(['project_join', 'team_invite', 'collaboration_request', 'mentorship']),
                'role'             => fake()->randomElement(['Developer', 'Designer', 'CTO', 'Advisor', null]),
                'message'          => fake()->paragraph(),
                'status'           => $status,
                'expires_at'       => now()->addDays(rand(1, 30)),
                'responded_at'     => in_array($status, ['accepted', 'rejected']) ? now()->subDays(rand(1, 14)) : null,
                'response_message' => in_array($status, ['accepted', 'rejected']) ? fake()->sentence() : null,
                'created_at'       => now()->subDays(rand(1, 30)),
            ]);
        }
    }

    private function seedRatings($users, $projects): void
    {
        $pairs = collect();

        for ($i = 0; $i < 35; $i++) {
            $rater   = $users->random();
            $rated   = $users->where('id', '!=', $rater->id)->random();
            $project = $projects->random();
            $key     = "{$rater->id}-{$rated->id}-{$project->id}";
            if ($pairs->contains($key)) continue;
            $pairs->push($key);

            $ratings = [rand(1, 5), rand(1, 5), rand(1, 5), rand(1, 5), rand(1, 5)];

            \DB::table('collaboration_ratings')->insert([
                'id'                     => Str::uuid(),
                'rater_id'               => $rater->id,
                'rated_user_id'          => $rated->id,
                'project_id'             => $project->id,
                'communication_rating'   => $ratings[0],
                'reliability_rating'     => $ratings[1],
                'skill_rating'           => $ratings[2],
                'problem_solving_rating' => $ratings[3],
                'teamwork_rating'        => $ratings[4],
                'overall_rating'         => round(array_sum($ratings) / 5, 2),
                'written_feedback'       => fake()->paragraph(),
                'visibility'             => fake()->randomElement(['private', 'public', 'anonymous']),
                'created_at'             => now()->subDays(rand(1, 60)),
            ]);
        }
    }
}
