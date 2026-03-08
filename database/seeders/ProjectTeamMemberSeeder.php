<?php

namespace Database\Seeders;

use App\Models\ProjectTeamMember;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectTeamMemberSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::all();
        $users = User::where('role', '!=', 'guest')->get();

        foreach ($projects as $project) {
            // Skip if already has team members
            if ($project->teamMembers()->count() > 0) {
                continue;
            }

            // Add owner
            ProjectTeamMember::factory()
                ->owner()
                ->active()
                ->create([
                    'project_id' => $project->id,
                    'user_id' => $project->owner_id,
                    'role_id' => $project->roles->first()?->id
                ]);

            // Add random members
            $memberCount = rand(1, $project->team_size_max - 1);
            $selectedUsers = $users->where('id', '!=', $project->owner_id)
                ->random(min($memberCount, $users->count()));

            foreach ($selectedUsers as $user) {
                ProjectTeamMember::factory()
                    ->active()
                    ->create([
                        'project_id' => $project->id,
                        'user_id' => $user->id,
                        'role_id' => $project->roles->random()->id
                    ]);
            }

            $project->update(['current_team_size' => $project->teamMembers()->count()]);
        }
    }
}
