<?php

namespace Database\Seeders;

use App\Models\ProjectRole;
use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectRoleSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::all();

        foreach ($projects as $project) {
            // Skip if already has roles
            if ($project->roles()->count() > 0) {
                continue;
            }

            ProjectRole::factory()
                ->count(rand(2, 5))
                ->forProject($project->id)
                ->create();
        }
    }
}
