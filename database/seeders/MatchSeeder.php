<?php

namespace Database\Seeders;

use App\Models\MatchModel;
use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Seeder;

class MatchSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('account_status', 'active')->get();
        $projects = Project::where('status', 'active')->get();

        foreach ($users as $user) {
            // Collaborator matches
            $collaboratorCount = rand(0, 10);
            $potentialMatches = $users->where('id', '!=', $user->id)
                ->random(min($collaboratorCount, $users->count() - 1));

            foreach ($potentialMatches as $match) {
                $score = rand(60, 99) / 100;
                $viewed = rand(0, 1);

                $factory = MatchModel::factory()
                    ->collaborator()
                    ->highScore();

                if (!$viewed) {
                    $factory->unviewed();
                }

                $factory->create([
                    'user_id' => $user->id,
                    'matched_user_id' => $match->id,
                    'compatibility_score' => $score,
                    'viewed' => $viewed,
                    'viewed_at' => $viewed ? now()->subDays(rand(1, 10)) : null,
                    'saved' => $viewed && rand(0, 1)
                ]);
            }

            // Project matches
            if ($projects->count() > 0) {
                $projectCount = rand(0, 5);
                $projectMatches = $projects->random(min($projectCount, $projects->count()));

                foreach ($projectMatches as $project) {
                    $score = rand(50, 95) / 100;
                    $viewed = rand(0, 1);

                    $factory = MatchModel::factory()
                        ->project();

                    if (!$viewed) {
                        $factory->unviewed();
                    }

                    $factory->create([
                        'user_id' => $user->id,
                        'matched_project_id' => $project->id,
                        'compatibility_score' => $score,
                        'viewed' => $viewed,
                        'viewed_at' => $viewed ? now()->subDays(rand(1, 10)) : null,
                        'saved' => $viewed && rand(0, 1)
                    ]);
                }
            }
        }
    }
}
