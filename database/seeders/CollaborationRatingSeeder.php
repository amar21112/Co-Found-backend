<?php

namespace Database\Seeders;

use App\Models\CollaborationRating;
use App\Models\ProjectTeamMember;
use App\Models\User;
use Illuminate\Database\Seeder;

class CollaborationRatingSeeder extends Seeder
{
    public function run(): void
    {
        $teamMembers = ProjectTeamMember::where('is_active', true)
            ->orWhereNotNull('left_at')
            ->get()
            ->groupBy('project_id');

        foreach ($teamMembers as $projectId => $members) {
            $members = $members->toArray();

            for ($i = 0; $i < count($members); $i++) {
                for ($j = $i + 1; $j < count($members); $j++) {
                    if (rand(0, 2) === 0) { // 33% chance of rating existing
                        $rater = User::find($members[$i]['user_id']);
                        $rated = User::find($members[$j]['user_id']);

                        if ($rater && $rated) {
                            $visibility = rand(0, 2) === 0 ? 'public' : (rand(0, 1) ? 'private' : 'anonymous');

                            CollaborationRating::factory()
                                ->$visibility()
                                ->withFeedback()
                                ->create([
                                    'rater_id' => $rater->id,
                                    'rated_user_id' => $rated->id,
                                    'project_id' => $projectId
                                ]);
                        }
                    }
                }
            }
        }

        // Create some ratings without project context
        $users = User::where('account_status', 'active')->get();
        for ($i = 0; $i < 50; $i++) {
            $rater = $users->random();
            $rated = $users->where('id', '!=', $rater->id)->random();

            CollaborationRating::factory()
                ->create([
                    'rater_id' => $rater->id,
                    'rated_user_id' => $rated->id,
                    'project_id' => null
                ]);
        }
    }
}
