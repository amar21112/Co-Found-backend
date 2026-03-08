<?php

namespace Database\Seeders;

use App\Models\ProjectMilestone;
use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectMilestoneSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::all();

        foreach ($projects as $project) {
            // Skip if already has milestones
            if ($project->milestones()->count() > 0) {
                continue;
            }

            $milestoneCount = rand(3, 8);
            $startDate = $project->start_date ?? now();

            for ($i = 0; $i < $milestoneCount; $i++) {
                $dueDate = $startDate->copy()->addWeeks(($i + 1) * 2);
                $completed = $dueDate < now() && rand(0, 1);
                $inProgress = !$completed && $dueDate > now()->subWeeks(2) && $dueDate < now()->addWeeks(2);

                if ($completed) {
                    ProjectMilestone::factory()
                        ->completed()
                        ->create([
                            'project_id' => $project->id,
                            'order_index' => $i + 1,
                            'due_date' => $dueDate
                        ]);
                } elseif ($inProgress) {
                    ProjectMilestone::factory()
                        ->inProgress()
                        ->create([
                            'project_id' => $project->id,
                            'order_index' => $i + 1,
                            'due_date' => $dueDate
                        ]);
                } else {
                    ProjectMilestone::factory()
                        ->pending()
                        ->create([
                            'project_id' => $project->id,
                            'order_index' => $i + 1,
                            'due_date' => $dueDate
                        ]);
                }
            }
        }
    }
}
