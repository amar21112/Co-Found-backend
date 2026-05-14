<?php

namespace Tests\Feature\Project;

use App\Models\Project;
use App\Models\User;
use App\Models\UserRestriction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectTest extends TestCase
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
        return Project::factory()->create(array_merge(
            ['owner_id' => $owner->id, 'visibility' => 'public'],
            $overrides
        ));
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title'             => 'Co-Found Platform',
            'short_description' => 'A platform to find co-founders.',
            'full_description'  => 'This is the full description of the project.',
            'category'          => 'Technology',
            'visibility'        => 'public',
            'status'            => 'planning',
        ], $overrides);
    }

    // =========================================================================
    // GET /api/v1/projects
    // =========================================================================

    /** @test */
    public function guest_can_list_public_projects(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs(User::factory()->guest()->create());

        $this->makeProject($user);
        $this->makeProject($user);

        $this->getJson('/api/v1/projects')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    /** @test */
    public function private_projects_are_excluded_from_public_listing(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($this->makeUser());

        $this->makeProject($user, ['visibility' => 'public']);
        $this->makeProject($user, ['visibility' => 'private']);
        $this->makeProject($user, ['visibility' => 'unlisted']);

        $this->getJson('/api/v1/projects')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function projects_can_be_filtered_by_category(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($this->makeUser());

        $this->makeProject($user, ['category' => 'Technology']);
        $this->makeProject($user, ['category' => 'Healthcare']);

        $this->getJson('/api/v1/projects?category=Technology')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'Technology');
    }

    /** @test */
    public function projects_can_be_searched_by_title(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($this->makeUser());

        $this->makeProject($user, ['title' => 'Unique Project Alpha']);
        $this->makeProject($user, ['title' => 'Other Project Beta']);

        $this->getJson('/api/v1/projects?search=Alpha')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function unauthenticated_user_cannot_list_projects(): void
    {
        $this->getJson('/api/v1/projects')->assertStatus(401);
    }

    // =========================================================================
    // GET /api/v1/projects/{id}
    // =========================================================================

    /** @test */
    public function authenticated_user_can_view_a_project(): void
    {
        $owner   = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs($this->makeUser());

        $this->getJson("/api/v1/projects/$project->id")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $project->id)
            ->assertJsonPath('data.title', $project->title);
    }

    /** @test */
    public function show_increments_view_count(): void
    {
        $owner   = $this->makeUser();
        $project = $this->makeProject($owner, ['view_count' => 0]);
        Sanctum::actingAs($this->makeUser());

        $this->getJson("/api/v1/projects/$project->id")->assertStatus(200);

        $this->assertDatabaseHas('projects', [
            'id'         => $project->id,
            'view_count' => 1,
        ]);
    }

    /** @test */
    public function guest_sees_stripped_project_detail(): void
    {
        $owner   = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs(User::factory()->guest()->create());

        $response = $this->getJson("/api/v1/projects/$project->id")
            ->assertStatus(200);

        $this->assertNull($response->json('data.full_description'));
        $this->assertNull($response->json('data.start_date'));
    }

    /** @test */
    public function verified_user_sees_full_project_detail(): void
    {
        $owner   = $this->makeUser();
        $project = $this->makeProject($owner, ['full_description' => 'Full detail here.']);
        Sanctum::actingAs($this->makeUser());

        $response = $this->getJson("/api/v1/projects/$project->id")
            ->assertStatus(200);

        $this->assertNotNull($response->json('data.full_description'));
    }

    /** @test */
    public function show_returns_404_for_unknown_project(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->getJson('/api/v1/projects/00000000-0000-0000-0000-000000000001')
            ->assertStatus(404);
    }

    // =========================================================================
    // POST /api/v1/projects
    // =========================================================================

    /** @test */
    public function verified_user_can_create_a_project(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/projects', $this->validPayload())
            ->assertStatus(201)
            ->assertJsonPath('data.title', 'Co-Found Platform')
            ->assertJsonPath('data.owner.id', $user->id);

        $this->assertDatabaseHas('projects', [
            'owner_id' => $user->id,
            'title'    => 'Co-Found Platform',
        ]);
    }

    /** @test */
    public function project_slug_is_auto_generated_from_title(): void
    {
        Sanctum::actingAs($this->makeUser());

        $response = $this->postJson('/api/v1/projects', $this->validPayload([
            'title' => 'My Awesome Project',
        ]))->assertStatus(201);

        $this->assertStringContainsString('my-awesome-project', $response->json('data.slug'));
    }

    /** @test */
    public function project_can_be_created_with_skills_and_roles(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $payload = $this->validPayload([
            'skills' => [
                ['skill_name' => 'PHP', 'proficiency_required' => 3, 'is_required' => true],
            ],
            'roles'  => [
                ['role_name' => 'Backend Developer', 'description' => 'Laravel expert needed'],
            ],
        ]);

        $this->postJson('/api/v1/projects', $payload)->assertStatus(201);

        $project = Project::where('owner_id', $user->id)->first();
        $this->assertDatabaseHas('project_skills', ['project_id' => $project->id, 'skill_name' => 'PHP']);
        $this->assertDatabaseHas('project_roles',  ['project_id' => $project->id, 'role_name' => 'Backend Developer']);
    }

    /** @test */
    public function title_is_required_to_create_project(): void
    {
        Sanctum::actingAs($this->makeUser());

        $payload = $this->validPayload();
        unset($payload['title']);

        $this->postJson('/api/v1/projects', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function team_size_max_must_be_gte_min(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/v1/projects', $this->validPayload([
            'team_size_min' => 5,
            'team_size_max' => 2,
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['team_size_max']);
    }

    /** @test */
    public function user_with_posting_ban_cannot_create_project(): void
    {
        $user = $this->makeUser();
        $mod  = User::factory()->moderator()->create();
        Sanctum::actingAs($user);

        UserRestriction::factory()->create([
            'user_id'          => $user->id,
            'restricted_by'    => $mod->id,
            'restriction_type' => 'posting_ban',
            'is_active'        => true,
            'expires_at'       => now()->addDay(),
        ]);

        $this->postJson('/api/v1/projects', $this->validPayload())
            ->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_create_project(): void
    {
        Sanctum::actingAs(User::factory()->guest()->create());

        $this->postJson('/api/v1/projects', $this->validPayload())
            ->assertStatus(403);
    }

    /** @test */
    public function unverified_user_cannot_create_project(): void
    {
        Sanctum::actingAs(User::factory()->unverified()->create());

        $this->postJson('/api/v1/projects', $this->validPayload())
            ->assertStatus(403);
    }

    // =========================================================================
    // PUT /api/v1/projects/{id}
    // =========================================================================

    /** @test */
    public function owner_can_update_their_project(): void
    {
        $owner   = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs($owner);

        $this->putJson("/api/v1/projects/$project->id", [
            'title'             => 'Updated Title',
            'short_description' => 'Updated short description.',
            'full_description'  => 'Updated full description.',
            'category'          => 'Healthcare',
        ])->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('projects', [
            'id'    => $project->id,
            'title' => 'Updated Title',
        ]);
    }

    /** @test */
    public function non_owner_cannot_update_project(): void
    {
        $owner   = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs($this->makeUser());

        $this->putJson("/api/v1/projects/$project->id", [
            'title'             => 'Hacked',
            'short_description' => 'x',
            'full_description'  => 'x',
            'category'          => 'x',
        ])->assertStatus(403);
    }

    // =========================================================================
    // DELETE /api/v1/projects/{id}
    // =========================================================================

    /** @test */
    public function owner_can_delete_their_project(): void
    {
        $owner   = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs($owner);

        $this->deleteJson("/api/v1/projects/$project->id")
            ->assertStatus(200);
    }

    /** @test */
    public function non_owner_cannot_delete_project(): void
    {
        $owner   = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs($this->makeUser());

        $this->deleteJson("/api/v1/projects/$project->id")
            ->assertStatus(403);
    }

    // =========================================================================
    // GET /api/v1/projects/{id}/milestones
    // =========================================================================

    /** @test */
    public function authenticated_user_can_view_project_milestones(): void
    {
        $owner   = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs($this->makeUser());

        $this->getJson("/api/v1/projects/$project->id/milestones")
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function guest_cannot_view_milestones(): void
    {
        $owner   = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs(User::factory()->guest()->create());

        $this->getJson("/api/v1/projects/$project->id/milestones")
            ->assertStatus(403);
    }

    // =========================================================================
    // GET /api/v1/projects/{id}/team
    // =========================================================================

    /** @test */
    public function authenticated_user_can_view_project_team(): void
    {
        $owner   = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs($this->makeUser());

        $this->getJson("/api/v1/projects/$project->id/team")
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function guest_cannot_view_team(): void
    {
        $owner   = $this->makeUser();
        $project = $this->makeProject($owner);
        Sanctum::actingAs(User::factory()->guest()->create());

        $this->getJson("/api/v1/projects/$project->id/team")
            ->assertStatus(403);
    }
}
