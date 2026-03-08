<?php

namespace Database\Seeders;

use App\Models\ApplicationSkill;
use App\Models\ProjectApplication;
use Illuminate\Database\Seeder;

class ApplicationSkillSeeder extends Seeder
{
    public function run(): void
    {
        $applications = ProjectApplication::all();

        foreach ($applications as $application) {
            // Skip if already has skills
            if ($application->skills()->count() > 0) {
                continue;
            }

            ApplicationSkill::factory()
                ->count(rand(2, 5))
                ->forApplication($application->id)
                ->create();
        }
    }
}
