<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectSkill;
use App\Models\ProjectRole;
use App\Models\ProjectMilestone;
use App\Models\ProjectTeamMember;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $projectOwners = User::where('role', 'project_owner')->get();

        // Create active projects
        foreach ($projectOwners as $owner) {
            $projectCount = rand(1, 3);

            for ($i = 0; $i < $projectCount; $i++) {
                $project = Project::factory()
                    ->active()
                    ->public()
                    ->acceptingApplications()
                    ->create(['owner_id' => $owner->id]);

                $this->createProjectSkills($project, rand(3, 6));
                $this->createProjectRoles($project, rand(2, 4));
                $this->createProjectMilestones($project, rand(3, 6));
                $this->createTeamMembers($project);
            }
        }

        // Create planning projects
        foreach ($projectOwners->random(min(10, $projectOwners->count())) as $owner) {
            $project = Project::factory()
                ->planning()
                ->public()
                ->create(['owner_id' => $owner->id]);

            $this->createProjectSkills($project, rand(2, 4));
            $this->createProjectRoles($project, rand(2, 3));
            $this->createProjectMilestones($project, rand(2, 4));
        }

        // Create completed projects
        foreach ($projectOwners->random(min(15, $projectOwners->count())) as $owner) {
            $project = Project::factory()
                ->completed()
                ->public()
                ->create(['owner_id' => $owner->id]);

            $this->createProjectSkills($project, rand(3, 5));
            $this->createProjectRoles($project, rand(2, 4));
            $this->createProjectMilestones($project, rand(4, 8), true);
            $this->createTeamMembers($project, true);
        }

        // Create private projects
        foreach ($projectOwners->random(min(8, $projectOwners->count())) as $owner) {
            $project = Project::factory()
                ->active()
                ->private()
                ->create(['owner_id' => $owner->id]);

            $this->createProjectSkills($project, rand(2, 4));
            $this->createProjectRoles($project, rand(1, 3));
            $this->createTeamMembers($project);
        }

        // Create specific test projects
        $john = User::where('email', 'john.doe@example.com')->first();

        if ($john) {
            $project = Project::factory()
                ->active()
                ->public()
                ->acceptingApplications()
                ->create([
                    'owner_id' => $john->id,
                    'title' => 'AI-Powered Task Manager',
                    'slug' => 'ai-powered-task-manager',
                    'short_description' => 'Building an intelligent task management system',
                    'full_description' => 'We\'re building a revolutionary task management app that uses machine learning to automatically prioritize tasks based on deadlines, importance, and user behavior patterns.',
                    'category' => 'Mobile App',
                    'team_size_max' => 6,
                    'current_team_size' => 3
                ]);

            $this->createProjectSkills($project, ['React', 'Node.js', 'Python', 'TensorFlow', 'PostgreSQL']);
            $this->createProjectRoles($project, ['Frontend Developer', 'Backend Developer', 'ML Engineer']);
            $this->createProjectMilestones($project, 5);
            $this->createTeamMembers($project, true);
        }
    }

    private function createProjectSkills($project, $skills)
    {
        if (is_array($skills)) {
            foreach ($skills as $skill) {
                ProjectSkill::factory()
                    ->required()
                    ->create([
                        'project_id' => $project->id,
                        'skill_name' => $skill
                    ]);
            }
        } else {
            ProjectSkill::factory()
                ->count($skills)
                ->forProject($project->id)
                ->create();
        }
    }

    private function createProjectRoles($project, $roles)
    {
        if (is_array($roles)) {
            foreach ($roles as $role) {
                ProjectRole::factory()
                    ->create([
                        'project_id' => $project->id,
                        'role_name' => $role
                    ]);
            }
        } else {
            ProjectRole::factory()
                ->count($roles)
                ->forProject($project->id)
                ->create();
        }
    }

    private function createProjectMilestones($project, $count, $allCompleted = false)
    {
        for ($i = 0; $i < $count; $i++) {
            if ($allCompleted) {
                ProjectMilestone::factory()
                    ->completed()
                    ->create([
                        'project_id' => $project->id,
                        'order_index' => $i + 1
                    ]);
            } elseif ($i < $count / 2) {
                ProjectMilestone::factory()
                    ->completed()
                    ->create([
                        'project_id' => $project->id,
                        'order_index' => $i + 1
                    ]);
            } elseif ($i == floor($count / 2)) {
                ProjectMilestone::factory()
                    ->inProgress()
                    ->create([
                        'project_id' => $project->id,
                        'order_index' => $i + 1
                    ]);
            } else {
                ProjectMilestone::factory()
                    ->pending()
                    ->create([
                        'project_id' => $project->id,
                        'order_index' => $i + 1
                    ]);
            }
        }
    }

    private function createTeamMembers($project, $includeOwner = true)
    {
        if ($includeOwner) {
            ProjectTeamMember::factory()
                ->owner()
                ->active()
                ->create([
                    'project_id' => $project->id,
                    'user_id' => $project->owner_id
                ]);
        }

        $memberCount = rand(1, $project->team_size_max - ($includeOwner ? 1 : 0));
        $users = User::where('role', '!=', 'guest')
            ->where('id', '!=', $project->owner_id)
            ->inRandomOrder()
            ->limit($memberCount)
            ->get();

        foreach ($users as $user) {
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
