<?php

namespace Database\Seeders;

use App\Models\ProjectApplication;
use App\Models\ApplicationSkill;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::where('is_accepting_applications', true)->get();
        $users = User::where('role', 'regular_user')->get();

        foreach ($projects as $project) {
            $applicationCount = rand(0, 15);
            $applicants = $users->random(min($applicationCount, $users->count()));

            foreach ($applicants as $applicant) {
                // Skip if already applied
                if ($project->applications()->where('applicant_id', $applicant->id)->exists()) {
                    continue;
                }

                $status = $this->getRandomStatus();
                $role = $project->roles->random();

                $application = ProjectApplication::factory()
                    ->$status()
                    ->highMatch()
                    ->create([
                        'project_id' => $project->id,
                        'applicant_id' => $applicant->id,
                        'role_id' => $role->id
                    ]);

                ApplicationSkill::factory()
                    ->count(rand(2, 5))
                    ->forApplication($application->id)
                    ->create();
            }

            $project->update(['application_count' => $project->applications()->count()]);
        }
    }

    private function getRandomStatus()
    {
        $statuses = [
            'pending' => 50,
            'reviewing' => 20,
            'accepted' => 15,
            'rejected' => 10,
            'withdrawn' => 3,
            'expired' => 2
        ];

        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($statuses as $status => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                return $status;
            }
        }

        return 'pending';
    }
}
