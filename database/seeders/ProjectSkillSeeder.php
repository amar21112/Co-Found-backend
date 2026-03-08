<?php

namespace Database\Seeders;

use App\Models\ProjectSkill;
use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSkillSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::all();

        foreach ($projects as $project) {
            // Skip if already has skills
            if ($project->skills()->count() > 0) {
                continue;
            }

            ProjectSkill::factory()
                ->count(rand(3, 7))
                ->forProject($project->id)
                ->create();
        }
    }
}
