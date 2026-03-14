<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\ProjectSkill;
use App\Models\ProjectRole;
use App\Models\ProjectMilestone;
use App\Models\ProjectTeamMember;
use App\Models\ProjectApplication;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    protected array $techSkills = [
        'PHP','Laravel','JavaScript','TypeScript','React','Vue.js',
        'Node.js','Python','MySQL','PostgreSQL','Docker','AWS',
        'UI/UX Design','Figma','Product Management','DevOps',
    ];

    protected array $roles = [
        'Lead Developer','Frontend Developer','Backend Developer',
        'UI/UX Designer','Product Manager','DevOps Engineer',
        'Mobile Developer','Data Engineer','QA Engineer',
    ];

    public function run(): void
    {
        $owners     = User::where('role', 'regular_user')->inRandomOrder()->take(15)->get();
        $allUsers   = User::where('role', 'regular_user')->get();

        // ── 5 active well-populated projects ────────────────────────────
        $owners->take(5)->each(function (User $owner) use ($allUsers) {
            $project = Project::factory()->active()->create(['owner_id' => $owner->id]);
            $this->attachSkillsAndRoles($project);
            $this->addMilestones($project);
            $this->addTeamMembers($project, $owner, $allUsers, 3, 5);
            $this->addApplications($project, $allUsers, 5, 10);
        });

        // ── 5 planning projects ──────────────────────────────────────────
        $owners->slice(5, 5)->each(function (User $owner) use ($allUsers) {
            $project = Project::factory()->create([
                'owner_id' => $owner->id,
                'status'   => 'planning',
            ]);
            $this->attachSkillsAndRoles($project);
            $this->addMilestones($project, 2);
            $this->addApplications($project, $allUsers, 2, 5);
        });

        // ── 5 completed projects ─────────────────────────────────────────
        $owners->slice(10, 5)->each(function (User $owner) use ($allUsers) {
            $project = Project::factory()->completed()->create(['owner_id' => $owner->id]);
            $this->attachSkillsAndRoles($project);
            $this->addMilestones($project, 5, 'completed');
            $this->addTeamMembers($project, $owner, $allUsers, 2, 4);
        });

        // ── 10 more random projects ──────────────────────────────────────
        Project::factory(10)->create()->each(function (Project $project) use ($allUsers) {
            $this->attachSkillsAndRoles($project);
            $this->addMilestones($project);
        });

        $this->command->info('ProjectSeeder: 25 projects seeded with skills, roles, milestones, team members, and applications.');
    }

    private function attachSkillsAndRoles(Project $project): void
    {
        $skills = collect($this->techSkills)->shuffle()->take(rand(3, 6));
        foreach ($skills as $skill) {
            \DB::table('project_skills')->insert([
                'id'                   => \Str::uuid(),
                'project_id'           => $project->id,
                'skill_name'           => $skill,
                'proficiency_required' => rand(2, 5),
                'positions_needed'     => rand(1, 3),
                'positions_filled'     => rand(0, 1),
                'is_required'          => (bool) rand(0, 1),
            ]);
        }

        $selectedRoles = collect($this->roles)->shuffle()->take(rand(2, 4));
        foreach ($selectedRoles as $role) {
            \DB::table('project_roles')->insert([
                'id'               => \Str::uuid(),
                'project_id'       => $project->id,
                'role_name'        => $role,
                'description'      => fake()->sentence(),
                'positions_needed' => rand(1, 2),
                'positions_filled' => 0,
                'created_at'       => now(),
            ]);
        }
    }

    private function addMilestones(Project $project, int $count = 4, string $status = null): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $s = $status ?? fake()->randomElement(['pending', 'in_progress', 'completed']);
            ProjectMilestone::factory()->create([
                'project_id'  => $project->id,
                'order_index' => $i,
                'status'      => $s,
            ]);
        }
    }

    private function addTeamMembers(Project $project, User $owner, $allUsers, int $min, int $max): void
    {
        // Owner is always a member
        \DB::table('project_team_members')->insertOrIgnore([
            'id'          => \Str::uuid(),
            'project_id'  => $project->id,
            'user_id'     => $owner->id,
            'position'    => 'Founder',
            'permissions' => 'owner',
            'joined_at'   => now()->subDays(rand(30, 90)),
            'is_active'   => true,
        ]);

        $members = $allUsers->where('id', '!=', $owner->id)->shuffle()->take(rand($min, $max));
        foreach ($members as $member) {
            \DB::table('project_team_members')->insertOrIgnore([
                'id'          => \Str::uuid(),
                'project_id'  => $project->id,
                'user_id'     => $member->id,
                'position'    => fake()->randomElement($this->roles),
                'permissions' => 'member',
                'joined_at'   => now()->subDays(rand(1, 30)),
                'is_active'   => true,
            ]);
        }
    }

    private function addApplications(Project $project, $allUsers, int $min, int $max): void
    {
        $applicants = $allUsers->shuffle()->take(rand($min, $max));
        foreach ($applicants as $applicant) {
            \DB::table('project_applications')->insertOrIgnore([
                'id'            => \Str::uuid(),
                'project_id'    => $project->id,
                'applicant_id'  => $applicant->id,
                'cover_message' => fake()->paragraph(),
                'proposed_role' => fake()->randomElement($this->roles),
                'availability'  => fake()->randomElement(['full_time', 'part_time', 'weekends']),
                'status'        => fake()->randomElement(['pending', 'pending', 'reviewing', 'accepted', 'rejected']),
                'match_score'   => round(rand(40, 100) / 100, 2),
                'applied_at'    => now()->subDays(rand(1, 30)),
                'updated_at'    => now(),
            ]);
        }
    }
}
