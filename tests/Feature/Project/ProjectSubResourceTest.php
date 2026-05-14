<?php

namespace Tests\Feature\Project;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectRole;
use App\Models\ProjectSkill;
use App\Models\ProjectTeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectSubResourceTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['email_verified' => true], $overrides));
    }

    private function makeGuest(): User
    {
        return User::factory()->guest()->create();
    }

    private function makeProject(User $owner, array $overrides = []): Project
    {
        return Project::factory()->create(array_merge(['owner_id' => $owner->id], $overrides));
    }

    private function makeTeamMember(Project $project, User $user, string $permissions = 'member'): ProjectTeamMember
    {
        return ProjectTeamMember::factory()->create([
            'project_id'  => $project->id,
            'user_id'     => $user->id,
            'permissions' => $permissions,
            'is_active'   => true,
        ]);
    }

    private function makeMilestone(Project $project, array $overrides = []): ProjectMilestone
    {
        return ProjectMilestone::factory()->create(array_merge(
            ['project_id' => $project->id],
            $overrides
        ));
    }

    private function makeRole(Project $project, array $overrides = []): ProjectRole
    {
        return ProjectRole::factory()->create(array_merge(
            ['project_id' => $project->id],
            $overrides
        ));
    }

    private function makeSkill(Project $project, array $overrides = []): ProjectSkill
    {
        return ProjectSkill::factory()->create(array_merge(
            ['project_id' => $project->id],
            $overrides
        ));
    }

    private function validMilestonePayload(array $overrides = []): array
    {
        return array_merge([
            'title'       => 'MVP Launch',
            'description' => 'Ship the first version.',
            'due_date'    => now()->addMonths(2)->toDateString(),
            'order_index' => 1,
            'status'      => 'pending',
        ], $overrides);
    }

    private function validRolePayload(array $overrides = []): array
    {
        return array_merge([
            'role_name'        => 'Backend Engineer',
            'description'      => 'Builds the API.',
            'positions_needed' => 2,
        ], $overrides);
    }

    private function validSkillPayload(array $overrides = []): array
    {
        return array_merge([
            'skill_name'           => 'Laravel',
            'proficiency_required' => 3,
            'positions_needed'     => 1,
            'is_required'          => true,
        ], $overrides);
    }

    // =========================================================================
    // MILESTONES
    // =========================================================================

    /** @test */
    public function owner_can_create_a_milestone(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/projects/$project->id/milestones", $this->validMilestonePayload())
            ->assertStatus(201)
            ->assertJsonPath('data.title', 'MVP Launch');

        $this->assertDatabaseHas('project_milestones', [
            'project_id' => $project->id,
            'title'      => 'MVP Launch',
        ]);
    }

    /** @test */
    public function milestone_title_is_required(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/projects/$project->id/milestones", ['order_index' => 1])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function non_owner_cannot_create_milestone(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs($this->makeUser());

        $this->postJson("/api/v1/projects/$project->id/milestones", $this->validMilestonePayload())
            ->assertStatus(403);
    }

    /** @test */
    public function team_admin_can_create_milestone(): void
    {
        $owner = $this->makeUser();
        $admin = $this->makeUser();
        $project = $this->makeProject($owner);
        $this->makeTeamMember($project, $admin, 'admin');
        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/projects/$project->id/milestones", $this->validMilestonePayload())
            ->assertStatus(201);
    }

    /** @test */
    public function owner_can_update_a_milestone(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        $milestone = $this->makeMilestone($project);
        Sanctum::actingAs($owner);

        $this->putJson("/api/v1/projects/$project->id/milestones/$milestone->id", [
            'title'  => 'Updated Title',
            'status' => 'in_progress',
        ])->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title');
    }

    /** @test */
    public function non_owner_cannot_update_milestone(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        $milestone = $this->makeMilestone($project);
        Sanctum::actingAs($this->makeUser());

        $this->putJson("/api/v1/projects/$project->id/milestones/$milestone->id", [
            'title' => 'Hacked',
        ])->assertStatus(403);
    }

    /** @test */
    public function updating_milestone_from_different_project_returns_404(): void
    {
        $owner = $this->makeUser();
        $projectA = $this->makeProject($owner);
        $projectB = $this->makeProject($owner);
        $milestoneB = $this->makeMilestone($projectB);
        Sanctum::actingAs($owner);

        // Milestone belongs to projectB, but URL says projectA
        $this->putJson("/api/v1/projects/$projectA->id/milestones/$milestoneB->id", [
            'title' => 'Cross-project edit',
        ])->assertStatus(404);
    }

    /** @test */
    public function owner_can_delete_a_milestone(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        $milestone = $this->makeMilestone($project);
        Sanctum::actingAs($owner);

        $this->deleteJson("/api/v1/projects/$project->id/milestones/$milestone->id")
            ->assertStatus(200);

        $this->assertDatabaseMissing('project_milestones', ['id' => $milestone->id]);
    }

    /** @test */
    public function non_owner_cannot_delete_milestone(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        $milestone = $this->makeMilestone($project);
        Sanctum::actingAs($this->makeUser());

        $this->deleteJson("/api/v1/projects/$project->id/milestones/$milestone->id")
            ->assertStatus(403);
    }

    /** @test */
    public function deleting_milestone_from_different_project_returns_404(): void
    {
        $owner = $this->makeUser();
        $projectA = $this->makeProject($owner);
        $projectB = $this->makeProject($owner);
        $milestoneB = $this->makeMilestone($projectB);
        Sanctum::actingAs($owner);

        $this->deleteJson("/api/v1/projects/$projectA->id/milestones/$milestoneB->id")
            ->assertStatus(404);
    }

    // =========================================================================
    // ROLES
    // =========================================================================

    /** @test */
    public function guest_can_list_project_roles(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        $this->makeRole($project);
        $this->makeRole($project);
        Sanctum::actingAs($this->makeGuest());

        $this->getJson("/api/v1/projects/$project->id/roles")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function owner_can_create_a_role(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/projects/$project->id/roles", $this->validRolePayload())
            ->assertStatus(201)
            ->assertJsonPath('data.role_name', 'Backend Engineer');

        $this->assertDatabaseHas('project_roles', [
            'project_id' => $project->id,
            'role_name'  => 'Backend Engineer',
        ]);
    }

    /** @test */
    public function creating_duplicate_role_name_returns_409(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        $this->makeRole($project, ['role_name' => 'Backend Engineer']);
        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/projects/$project->id/roles", $this->validRolePayload([
            'role_name' => 'Backend Engineer',
        ]))->assertStatus(409);
    }

    /** @test */
    public function non_owner_cannot_create_role(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs($this->makeUser());

        $this->postJson("/api/v1/projects/$project->id/roles", $this->validRolePayload())
            ->assertStatus(403);
    }

    /** @test */
    public function owner_can_update_a_role(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        $role = $this->makeRole($project);
        Sanctum::actingAs($owner);

        $this->putJson("/api/v1/projects/$project->id/roles/$role->id", [
            'role_name'        => 'Senior Backend Engineer',
            'positions_needed' => 3,
        ])->assertStatus(200)
            ->assertJsonPath('data.role_name', 'Senior Backend Engineer');
    }

    /** @test */
    public function renaming_role_to_existing_name_returns_409(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        $roleA = $this->makeRole($project, ['role_name' => 'Designer']);
        $this->makeRole($project, ['role_name' => 'Developer']);
        Sanctum::actingAs($owner);

        $this->putJson("/api/v1/projects/$project->id/roles/$roleA->id", [
            'role_name' => 'Developer',
        ])->assertStatus(409);
    }

    /** @test */
    public function owner_can_delete_a_role(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        $role = $this->makeRole($project, ['positions_filled' => 0]);
        Sanctum::actingAs($owner);

        $this->deleteJson("/api/v1/projects/$project->id/roles/$role->id")
            ->assertStatus(200);

        $this->assertDatabaseMissing('project_roles', ['id' => $role->id]);
    }

    /** @test */
    public function cannot_delete_role_with_filled_positions(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        $role = $this->makeRole($project, ['positions_needed' => 2, 'positions_filled' => 1]);
        Sanctum::actingAs($owner);

        $this->deleteJson("/api/v1/projects/$project->id/roles/$role->id")
            ->assertStatus(422);
    }

    /** @test */
    public function non_owner_cannot_delete_role(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        $role = $this->makeRole($project);
        Sanctum::actingAs($this->makeUser());

        $this->deleteJson("/api/v1/projects/$project->id/roles/$role->id")
            ->assertStatus(403);
    }

    /** @test */
    public function deleting_role_from_different_project_returns_404(): void
    {
        $owner = $this->makeUser();
        $projectA = $this->makeProject($owner);
        $projectB = $this->makeProject($owner);
        $roleB = $this->makeRole($projectB);
        Sanctum::actingAs($owner);

        $this->deleteJson("/api/v1/projects/$projectA->id/roles/$roleB->id")
            ->assertStatus(404);
    }

    // =========================================================================
    // SKILLS
    // =========================================================================

    /** @test */
    public function owner_can_add_a_skill_requirement(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/projects/$project->id/skills", $this->validSkillPayload())
            ->assertStatus(201)
            ->assertJsonPath('data.skill_name', 'Laravel');

        $this->assertDatabaseHas('project_skills', [
            'project_id' => $project->id,
            'skill_name' => 'Laravel',
        ]);
    }

    /** @test */
    public function adding_duplicate_skill_returns_409(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        $this->makeSkill($project, ['skill_name' => 'Laravel']);
        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/projects/$project->id/skills", $this->validSkillPayload([
            'skill_name' => 'Laravel',
        ]))->assertStatus(409);
    }

    /** @test */
    public function owner_can_update_a_skill_requirement(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        $skill = $this->makeSkill($project);
        Sanctum::actingAs($owner);

        $this->putJson("/api/v1/projects/$project->id/skills/$skill->id", [
            'proficiency_required' => 5,
        ])->assertStatus(200)
            ->assertJsonPath('data.proficiency_required', 5);
    }

    /** @test */
    public function owner_can_remove_a_skill_requirement(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        $skill = $this->makeSkill($project);
        Sanctum::actingAs($owner);

        $this->deleteJson("/api/v1/projects/$project->id/skills/$skill->id")
            ->assertStatus(200);

        $this->assertDatabaseMissing('project_skills', ['id' => $skill->id]);
    }

    /** @test */
    public function removing_skill_from_different_project_returns_404(): void
    {
        $owner = $this->makeUser();
        $projectA = $this->makeProject($owner);
        $projectB = $this->makeProject($owner);
        $skillB = $this->makeSkill($projectB);
        Sanctum::actingAs($owner);

        $this->deleteJson("/api/v1/projects/$projectA->id/skills/$skillB->id")
            ->assertStatus(404);
    }

    // =========================================================================
    // TEAM
    // =========================================================================

    /** @test */
    public function owner_can_update_a_team_members_permissions(): void
    {
        $owner = $this->makeUser();
        $member = $this->makeUser();
        $project = $this->makeProject($owner);
        $this->makeTeamMember($project, $member);
        Sanctum::actingAs($owner);

        $this->putJson("/api/v1/projects/$project->id/team/$member->id", [
            'permissions' => 'admin',
        ])->assertStatus(200)
            ->assertJsonPath('data.permissions', 'admin');
    }

    /** @test */
    public function cannot_demote_owner_permissions(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        $this->makeTeamMember($project, $owner, 'owner');
        Sanctum::actingAs($owner);

        $this->putJson("/api/v1/projects/$project->id/team/$owner->id", [
            'permissions' => 'member',
        ])->assertStatus(422);
    }

    /** @test */
    public function non_owner_cannot_update_team_member(): void
    {
        $owner = $this->makeUser();
        $memberA = $this->makeUser();
        $memberB = $this->makeUser();
        $project = $this->makeProject($owner);
        $this->makeTeamMember($project, $memberA);
        $this->makeTeamMember($project, $memberB);
        Sanctum::actingAs($memberA);

        $this->putJson("/api/v1/projects/$project->id/team/$memberB->id", [
            'permissions' => 'admin',
        ])->assertStatus(403);
    }

    /** @test */
    public function owner_can_remove_a_team_member(): void
    {
        $owner = $this->makeUser();
        $member = $this->makeUser();
        $project = $this->makeProject($owner);
        $teamMember = $this->makeTeamMember($project, $member);
        Sanctum::actingAs($owner);

        $this->deleteJson("/api/v1/projects/$project->id/team/$member->id")
            ->assertStatus(200);

        $this->assertDatabaseHas('project_team_members', [
            'id'        => $teamMember->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function owner_cannot_be_removed_from_team(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        $this->makeTeamMember($project, $owner, 'owner');
        Sanctum::actingAs($owner);

        $this->deleteJson("/api/v1/projects/$project->id/team/$owner->id")
            ->assertStatus(422);
    }

    /** @test */
    public function non_owner_cannot_remove_team_members(): void
    {
        $owner = $this->makeUser();
        $memberA = $this->makeUser();
        $memberB = $this->makeUser();
        $project = $this->makeProject($owner);
        $this->makeTeamMember($project, $memberA);
        $this->makeTeamMember($project, $memberB);
        Sanctum::actingAs($memberA);

        $this->deleteJson("/api/v1/projects/$project->id/team/$memberB->id")
            ->assertStatus(403);
    }

    /** @test */
    public function active_team_member_can_leave_project(): void
    {
        $owner = $this->makeUser();
        $member = $this->makeUser();
        $project = $this->makeProject($owner);
        $teamMember = $this->makeTeamMember($project, $member);
        Sanctum::actingAs($member);

        $this->postJson("/api/v1/projects/$project->id/team/leave")
            ->assertStatus(200);

        $this->assertDatabaseHas('project_team_members', [
            'id'        => $teamMember->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function owner_cannot_leave_project(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        $this->makeTeamMember($project, $owner, 'owner');
        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/projects/$project->id/team/leave")
            ->assertStatus(422);
    }

    /** @test */
    public function non_member_cannot_leave_project(): void
    {
        $owner = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs($this->makeUser());

        $this->postJson("/api/v1/projects/$project->id/team/leave")
            ->assertStatus(422);
    }
}
