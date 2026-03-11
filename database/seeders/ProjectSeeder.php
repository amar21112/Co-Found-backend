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
        // Get all regular users who can potentially become project owners
        $potentialOwners = User::where('role', 'regular_user')->get();

        // Randomly select a subset to become project owners
        $projectOwnerCount = min(30, $potentialOwners->count()); // We want about 30 project owners
        $projectOwners = $potentialOwners->random($projectOwnerCount);

        // Create active projects for selected project owners
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

        // Create planning projects for some project owners
        foreach ($projectOwners->random(min(10, $projectOwners->count())) as $owner) {
            $project = Project::factory()
                ->planning()
                ->public()
                ->create(['owner_id' => $owner->id]);

            $this->createProjectSkills($project, rand(2, 4));
            $this->createProjectRoles($project, rand(2, 3));
            $this->createProjectMilestones($project, rand(2, 4));
        }

        // Create completed projects for some project owners
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

        // Create private projects for some project owners
        foreach ($projectOwners->random(min(8, $projectOwners->count())) as $owner) {
            $project = Project::factory()
                ->active()
                ->private()
                ->create(['owner_id' => $owner->id]);

            $this->createProjectSkills($project, rand(2, 4));
            $this->createProjectRoles($project, rand(1, 3));
            $this->createTeamMembers($project);
        }

        // Create specific test projects for John Doe (who should be a project owner)
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

        // Create a project for Maria Garcia
        $maria = User::where('email', 'maria.garcia@example.com')->first();

        if ($maria) {
            $project = Project::factory()
                ->active()
                ->public()
                ->acceptingApplications()
                ->create([
                    'owner_id' => $maria->id,
                    'title' => 'E-Learning Platform for Developers',
                    'slug' => 'e-learning-platform',
                    'short_description' => 'Interactive learning platform with coding challenges',
                    'category' => 'Web Development',
                    'team_size_max' => 5,
                    'current_team_size' => 2
                ]);

            $this->createProjectSkills($project, ['Vue.js', 'Laravel', 'MySQL', 'Redis']);
            $this->createProjectRoles($project, ['Frontend Developer', 'Backend Developer', 'Content Creator']);
            $this->createProjectMilestones($project, 4);
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
                        'skill_name' => $skill,
                        'proficiency_required' => rand(3, 5),
                        'positions_needed' => rand(1, 2)
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
                        'role_name' => $role,
                        'positions_needed' => rand(1, 2)
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
                        'order_index' => $i + 1,
                        'title' => $this->getMilestoneTitle($i)
                    ]);
            } elseif ($i < $count / 2) {
                ProjectMilestone::factory()
                    ->completed()
                    ->create([
                        'project_id' => $project->id,
                        'order_index' => $i + 1,
                        'title' => $this->getMilestoneTitle($i)
                    ]);
            } elseif ($i == floor($count / 2)) {
                ProjectMilestone::factory()
                    ->inProgress()
                    ->create([
                        'project_id' => $project->id,
                        'order_index' => $i + 1,
                        'title' => $this->getMilestoneTitle($i)
                    ]);
            } else {
                ProjectMilestone::factory()
                    ->pending()
                    ->create([
                        'project_id' => $project->id,
                        'order_index' => $i + 1,
                        'title' => $this->getMilestoneTitle($i)
                    ]);
            }
        }
    }

    private function getMilestoneTitle($index)
    {
        $titles = [
            'Project Kickoff',
            'Requirements Gathering',
            'Design Phase',
            'MVP Development',
            'Alpha Testing',
            'Beta Release',
            'User Testing',
            'Bug Fixes',
            'Performance Optimization',
            'Documentation',
            'Launch Preparation',
            'Public Release',
            'Post-Launch Review',
            'Maintenance Phase'
        ];

        return $titles[$index % count($titles)];
    }

    private function createTeamMembers($project, $includeOwner = true)
    {
        if ($includeOwner) {
            ProjectTeamMember::factory()
                ->owner()
                ->active()
                ->create([
                    'project_id' => $project->id,
                    'user_id' => $project->owner_id,
                    'role_id' => $project->roles->first()?->id
                ]);
        }

        $memberCount = rand(1, $project->team_size_max - ($includeOwner ? 1 : 0));

        // Get users who are NOT the project owner
        $users = User::where('role', 'regular_user')
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
                    'role_id' => $project->roles->random()->id,
                    'position' => 'Team Member',
                    'permissions' => 'member'
                ]);
        }

        $project->update(['current_team_size' => $project->teamMembers()->count()]);
    }
}
