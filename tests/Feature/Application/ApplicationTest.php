<?php

namespace Tests\Feature\Application;

use App\Enums\ApplicationStatus;
use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\ProjectTeamMember;
use App\Models\User;
use App\Models\UserRestriction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge(
            ['password' => Hash::make('Secret123')],
            $overrides
        ));
    }

    private function makeProject(User $owner, array $overrides = []): Project
    {
        return Project::factory()->create(array_merge([
            'owner_id'                  => $owner->id,
            'visibility'                => 'public',
            'is_accepting_applications' => true,
        ], $overrides));
    }

    private function makeApplication(
        Project $project,
        User    $applicant,
        string  $status = 'pending'
    ): ProjectApplication {
        return ProjectApplication::factory()->create([
            'project_id'   => $project->id,
            'applicant_id' => $applicant->id,
            'status'       => $status,
        ]);
    }

    private function restrictUser(User $user, User $admin, string $type): void
    {
        UserRestriction::factory()->create([
            'user_id'          => $user->id,
            'restricted_by'    => $admin->id,
            'restriction_type' => $type,
            'is_active'        => true,
            'expires_at'       => now()->addDay(),
        ]);
    }

    private function validApplicationPayload(array $overrides = []): array
    {
        return array_merge([
            'proposed_role' => 'Backend Developer',
            'cover_message' => 'I am very interested in this project.',
            'availability'  => 'full_time',
        ], $overrides);
    }

    // =========================================================================
    // GET /api/v1/projects/{id}/applications  (list for project)
    // =========================================================================

    /** @test */
    public function project_owner_can_list_applications(): void
    {
        $owner   = $this->makeUser();
        $project = $this->makeProject($owner);
        $this->makeApplication($project, $this->makeUser());
        $this->makeApplication($project, $this->makeUser());
        Sanctum::actingAs($owner);

        $this->getJson("/api/v1/projects/$project->id/applications")
            ->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function non_owner_cannot_list_project_applications(): void
    {
        $owner   = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs($this->makeUser());

        $this->getJson("/api/v1/projects/$project->id/applications")
            ->assertStatus(403);
    }

    /** @test */
    public function applications_can_be_filtered_by_status(): void
    {
        $owner   = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs($owner);

        $this->makeApplication($project, $this->makeUser());
        $this->makeApplication($project, $this->makeUser(), 'accepted');
        $this->makeApplication($project, $this->makeUser(), 'rejected');

        $this->getJson("/api/v1/projects/$project->id/applications?status=pending")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function unauthenticated_user_cannot_list_applications(): void
    {
        $owner   = $this->makeUser();
        $project = $this->makeProject($owner);

        $this->getJson("/api/v1/projects/$project->id/applications")
            ->assertStatus(401);
    }

    // =========================================================================
    // GET /api/v1/projects/{id}/applications/{applicationId}  (show)
    // =========================================================================

    /** @test */
    public function owner_can_view_a_specific_application(): void
    {
        $owner       = $this->makeUser();
        $project     = $this->makeProject($owner);
        $application = $this->makeApplication($project, $this->makeUser());
        Sanctum::actingAs($owner);

        $this->getJson("/api/v1/projects/$project->id/applications/$application->id")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $application->id);
    }

    /** @test */
    public function applicant_can_view_their_own_application(): void
    {
        $owner       = $this->makeUser();
        $applicant   = $this->makeUser();
        $project     = $this->makeProject($owner);
        $application = $this->makeApplication($project, $applicant);
        Sanctum::actingAs($applicant);

        $this->getJson("/api/v1/projects/$project->id/applications/$application->id")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $application->id);
    }

    /** @test */
    public function unrelated_user_cannot_view_application(): void
    {
        $owner       = $this->makeUser();
        $project     = $this->makeProject($owner);
        $application = $this->makeApplication($project, $this->makeUser());
        Sanctum::actingAs($this->makeUser());

        $this->getJson("/api/v1/projects/$project->id/applications/$application->id")
            ->assertStatus(403);
    }

    // =========================================================================
    // POST /api/v1/projects/{id}/applications  (apply)
    // =========================================================================

    /** @test */
    public function verified_user_can_apply_to_a_project(): void
    {
        $owner     = $this->makeUser();
        $applicant = $this->makeUser();
        $project   = $this->makeProject($owner);
        Sanctum::actingAs($applicant);

        $this->postJson(
            "/api/v1/projects/$project->id/applications",
            $this->validApplicationPayload()
        )->assertStatus(201)
            ->assertJsonPath('data.status', ApplicationStatus::Pending->value);

        $this->assertDatabaseHas('project_applications', [
            'project_id'   => $project->id,
            'applicant_id' => $applicant->id,
            'status'       => 'pending',
        ]);
    }

    /** @test */
    public function cannot_apply_to_project_not_accepting_applications(): void
    {
        $owner     = $this->makeUser();
        $project   = $this->makeProject($owner, ['is_accepting_applications' => false]);
        Sanctum::actingAs($this->makeUser());

        $this->postJson(
            "/api/v1/projects/$project->id/applications",
            $this->validApplicationPayload()
        )->assertStatus(422);
    }

    /** @test */
    public function cannot_apply_to_same_project_twice(): void
    {
        $owner     = $this->makeUser();
        $applicant = $this->makeUser();
        $project   = $this->makeProject($owner);
        Sanctum::actingAs($applicant);

        $this->makeApplication($project, $applicant);

        $this->postJson(
            "/api/v1/projects/$project->id/applications",
            $this->validApplicationPayload()
        )->assertStatus(409);
    }

    /** @test */
    public function project_owner_cannot_apply_to_own_project(): void
    {
        $owner   = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs($owner);

        $this->postJson(
            "/api/v1/projects/$project->id/applications",
            $this->validApplicationPayload()
        )->assertStatus(403);
    }

    /** @test */
    public function team_member_cannot_apply_to_their_project(): void
    {
        $owner   = $this->makeUser();
        $member  = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs($member);

        // Add member to team
        ProjectTeamMember::factory()->create([
            'project_id' => $project->id,
            'user_id'    => $member->id,
        ]);

        $this->postJson(
            "/api/v1/projects/$project->id/applications",
            $this->validApplicationPayload()
        )->assertStatus(403);
    }

    /** @test */
    public function user_with_application_ban_cannot_apply(): void
    {
        $owner     = $this->makeUser();
        $applicant = $this->makeUser();
        $project   = $this->makeProject($owner);
        $mod       = User::factory()->moderator()->create();
        Sanctum::actingAs($applicant);

        $this->restrictUser($applicant, $mod, 'application_ban');

        $this->postJson(
            "/api/v1/projects/$project->id/applications",
            $this->validApplicationPayload()
        )->assertStatus(403);
    }

    /** @test */
    public function proposed_role_is_required_when_no_role_id(): void
    {
        $owner   = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs($this->makeUser());

        $this->postJson("/api/v1/projects/$project->id/applications", [
            'cover_message' => 'I am interested.',
            'availability'  => 'full_time',
            // no proposed_role and no role_id
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['proposed_role']);
    }

    /** @test */
    public function guest_cannot_apply_to_project(): void
    {
        $owner   = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs(User::factory()->guest()->create());

        $this->postJson(
            "/api/v1/projects/$project->id/applications",
            $this->validApplicationPayload()
        )->assertStatus(403);
    }

    // =========================================================================
    // PATCH /api/v1/projects/{id}/applications/{applicationId}/review
    // =========================================================================

    /** @test */
    public function owner_can_accept_a_pending_application(): void
    {
        $owner       = $this->makeUser();
        $applicant   = $this->makeUser();
        $project     = $this->makeProject($owner);
        $application = $this->makeApplication($project, $applicant);
        Sanctum::actingAs($owner);

        $this->patchJson(
            "/api/v1/projects/$project->id/applications/$application->id/review",
            ['status' => 'accepted']
        )->assertStatus(200)
            ->assertJsonPath('data.status', ApplicationStatus::Accepted->value);

        $this->assertDatabaseHas('project_applications', [
            'id'          => $application->id,
            'status'      => 'accepted',
            'reviewed_by' => $owner->id,
        ]);
    }

    /** @test */
    public function owner_can_reject_a_pending_application(): void
    {
        $owner       = $this->makeUser();
        $applicant   = $this->makeUser();
        $project     = $this->makeProject($owner);
        $application = $this->makeApplication($project, $applicant);
        Sanctum::actingAs($owner);

        $this->patchJson(
            "/api/v1/projects/$project->id/applications/$application->id/review",
            ['status' => 'rejected']
        )->assertStatus(200)
            ->assertJsonPath('data.status', ApplicationStatus::Rejected->value);
    }

    /** @test */
    public function cannot_review_an_already_accepted_application(): void
    {
        $owner       = $this->makeUser();
        $project     = $this->makeProject($owner);
        $application = $this->makeApplication($project, $this->makeUser(), 'accepted');
        Sanctum::actingAs($owner);

        $this->patchJson(
            "/api/v1/projects/$project->id/applications/$application->id/review",
            ['status' => 'rejected']
        )->assertStatus(422);
    }

    /** @test */
    public function cannot_review_a_withdrawn_application(): void
    {
        $owner       = $this->makeUser();
        $project     = $this->makeProject($owner);
        $application = $this->makeApplication($project, $this->makeUser(), 'withdrawn');
        Sanctum::actingAs($owner);

        $this->patchJson(
            "/api/v1/projects/$project->id/applications/$application->id/review",
            ['status' => 'accepted']
        )->assertStatus(422);
    }

    /** @test */
    public function non_owner_cannot_review_application(): void
    {
        $owner       = $this->makeUser();
        $project     = $this->makeProject($owner);
        $application = $this->makeApplication($project, $this->makeUser());
        Sanctum::actingAs($this->makeUser());

        $this->patchJson(
            "/api/v1/projects/$project->id/applications/$application->id/review",
            ['status' => 'accepted']
        )->assertStatus(403);
    }

    /** @test */
    public function review_status_must_be_valid(): void
    {
        $owner       = $this->makeUser();
        $project     = $this->makeProject($owner);
        $application = $this->makeApplication($project, $this->makeUser());
        Sanctum::actingAs($owner);

        $this->patchJson(
            "/api/v1/projects/$project->id/applications/$application->id/review",
            ['status' => 'maybe']
        )->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    // =========================================================================
    // GET /api/v1/applications/mine  (my applications)
    // =========================================================================

    /** @test */
    public function user_can_list_their_own_applications(): void
    {
        $user  = $this->makeUser();
        $owner = $this->makeUser();
        Sanctum::actingAs($user);

        $this->makeApplication($this->makeProject($owner), $user);
        $this->makeApplication($this->makeProject($owner), $user, 'accepted');

        // Another user's application — must not appear
        $this->makeApplication($this->makeProject($owner), $this->makeUser());

        $this->getJson('/api/v1/applications/mine')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function my_applications_can_be_filtered_by_status(): void
    {
        $user  = $this->makeUser();
        $owner = $this->makeUser();
        Sanctum::actingAs($user);

        $this->makeApplication($this->makeProject($owner), $user);
        $this->makeApplication($this->makeProject($owner), $user, 'accepted');
        $this->makeApplication($this->makeProject($owner), $user, 'rejected');

        $this->getJson('/api/v1/applications/mine?status=pending')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');
    }

    // =========================================================================
    // PATCH /api/v1/applications/{id}/withdraw  (withdraw)
    // =========================================================================

    /** @test */
    public function applicant_can_withdraw_pending_application(): void
    {
        $owner       = $this->makeUser();
        $applicant   = $this->makeUser();
        $project     = $this->makeProject($owner);
        $application = $this->makeApplication($project, $applicant);
        Sanctum::actingAs($applicant);

        $this->patchJson("/api/v1/applications/$application->id/withdraw")
            ->assertStatus(200)
            ->assertJsonPath('data.status', ApplicationStatus::Withdrawn->value);

        $this->assertDatabaseHas('project_applications', [
            'id'     => $application->id,
            'status' => 'withdrawn',
        ]);
    }

    /** @test */
    public function applicant_can_withdraw_reviewing_application(): void
    {
        $owner       = $this->makeUser();
        $applicant   = $this->makeUser();
        $project     = $this->makeProject($owner);
        $application = $this->makeApplication($project, $applicant, 'reviewing');
        Sanctum::actingAs($applicant);

        $this->patchJson("/api/v1/applications/$application->id/withdraw")
            ->assertStatus(200)
            ->assertJsonPath('data.status', ApplicationStatus::Withdrawn->value);
    }

    /** @test */
    public function cannot_withdraw_an_already_accepted_application(): void
    {
        $owner       = $this->makeUser();
        $applicant   = $this->makeUser();
        $project     = $this->makeProject($owner);
        $application = $this->makeApplication($project, $applicant, 'accepted');
        Sanctum::actingAs($applicant);

        $this->patchJson("/api/v1/applications/$application->id/withdraw")
            ->assertStatus(422);
    }

    /** @test */
    public function cannot_withdraw_a_rejected_application(): void
    {
        $owner       = $this->makeUser();
        $applicant   = $this->makeUser();
        $project     = $this->makeProject($owner);
        $application = $this->makeApplication($project, $applicant, 'rejected');
        Sanctum::actingAs($applicant);

        $this->patchJson("/api/v1/applications/$application->id/withdraw")
            ->assertStatus(422);
    }

    /** @test */
    public function cannot_withdraw_already_withdrawn_application(): void
    {
        $owner       = $this->makeUser();
        $applicant   = $this->makeUser();
        $project     = $this->makeProject($owner);
        $application = $this->makeApplication($project, $applicant, 'withdrawn');
        Sanctum::actingAs($applicant);

        $this->patchJson("/api/v1/applications/$application->id/withdraw")
            ->assertStatus(422);
    }

    /** @test */
    public function user_cannot_withdraw_another_users_application(): void
    {
        $owner       = $this->makeUser();
        $applicant   = $this->makeUser();
        $project     = $this->makeProject($owner);
        $application = $this->makeApplication($project, $applicant);
        Sanctum::actingAs($this->makeUser());

        $this->patchJson("/api/v1/applications/$application->id/withdraw")
            ->assertStatus(404);
    }

    /** @test */
    public function withdraw_returns_404_for_unknown_application(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->patchJson('/api/v1/applications/00000000-0000-0000-0000-000000000001/withdraw')
            ->assertStatus(404);
    }

    /** @test */
    public function unauthenticated_user_cannot_withdraw(): void
    {
        $owner       = $this->makeUser();
        $applicant   = $this->makeUser();
        $project     = $this->makeProject($owner);
        $application = $this->makeApplication($project, $applicant);

        $this->patchJson("/api/v1/applications/$application->id/withdraw")
            ->assertStatus(401);
    }
}
