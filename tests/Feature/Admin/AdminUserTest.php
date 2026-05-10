<?php

namespace Tests\Feature\Admin;

use App\Models\Report;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class AdminUserTest extends AdminTestCase
{
    // =========================================================================
    // GET /api/v1/admin/users
    // =========================================================================

    /** @test */
    public function admin_can_list_users(): void
    {
        $admin = $this->makeAdmin();
        Sanctum::actingAs($admin);

        $this->makeRegularUser();
        $this->makeRegularUser();

        $this->getJson('/api/v1/admin/users')
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    /** @test */
    public function user_list_can_be_filtered_by_role(): void
    {
        $admin = $this->makeAdmin();
        Sanctum::actingAs($admin);

        $this->makeRegularUser();
        $this->makeRegularUser();
        $this->makeModerator();

        $this->getJson('/api/v1/admin/users?role=moderator')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function user_list_can_be_searched_by_name(): void
    {
        $admin = $this->makeAdmin();
        Sanctum::actingAs($admin);

        User::factory()->create(['full_name' => 'Findable Person', 'email_verified' => true]);
        $this->makeRegularUser();

        $this->getJson('/api/v1/admin/users?search=Findable')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function moderator_cannot_list_users(): void
    {
        Sanctum::actingAs($this->makeModerator());

        $this->getJson('/api/v1/admin/users')
            ->assertStatus(403);
    }

    /** @test */
    public function regular_user_cannot_list_users(): void
    {
        Sanctum::actingAs($this->makeRegularUser());

        $this->getJson('/api/v1/admin/users')
            ->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/admin/users')
            ->assertStatus(401);
    }

    // =========================================================================
    // GET /api/v1/admin/users/{userId}
    // =========================================================================

    /** @test */
    public function admin_can_view_a_single_user(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeRegularUser();
        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/admin/users/$user->id")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $user->id);
    }

    /** @test */
    public function show_returns_404_for_unknown_user(): void
    {
        Sanctum::actingAs($this->makeAdmin());

        $this->getJson('/api/v1/admin/users/' . fake()->uuid())
            ->assertStatus(404);
    }

    /** @test */
    public function moderator_cannot_view_user(): void
    {
        $user = $this->makeRegularUser();
        Sanctum::actingAs($this->makeModerator());

        $this->getJson("/api/v1/admin/users/$user->id")
            ->assertStatus(403);
    }

    // =========================================================================
    // PATCH /api/v1/admin/users/{userId}
    // =========================================================================

    /** @test */
    public function admin_can_update_user_role(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeRegularUser();
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/admin/users/$user->id", [
            'role' => 'moderator',
        ])->assertStatus(200)
            ->assertJsonPath('data.role', 'moderator');

        $this->assertDatabaseHas('users', [
            'id'   => $user->id,
            'role' => 'moderator',
        ]);
    }

    /** @test */
    public function admin_can_update_user_account_status(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeRegularUser();
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/admin/users/$user->id", [
            'account_status' => 'suspended',
        ])->assertStatus(200)
            ->assertJsonPath('data.account_status', 'suspended');
    }

    /** @test */
    public function updating_user_writes_admin_action_log(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeRegularUser();
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/admin/users/$user->id", [
            'account_status' => 'banned',
        ])->assertStatus(200);

        $this->assertDatabaseHas('admin_actions', [
            'admin_id'    => $admin->id,
            'action_type' => 'user_updated',
            'target_type' => 'user',
            'target_id'   => $user->id,
        ]);
    }

    /** @test */
    public function moderator_cannot_update_users(): void
    {
        $user = $this->makeRegularUser();
        Sanctum::actingAs($this->makeModerator());

        $this->patchJson("/api/v1/admin/users/$user->id", [
            'role' => 'moderator',
        ])->assertStatus(403);
    }

    // =========================================================================
    // DELETE /api/v1/admin/users/{userId}
    // =========================================================================

    /** @test */
    public function admin_can_soft_delete_a_user(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeRegularUser();
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/v1/admin/users/$user->id")
            ->assertStatus(200);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('users', [
            'id'             => $user->id,
            'account_status' => 'deleted',
        ]);
    }

    /** @test */
    public function deleting_user_revokes_all_their_tokens(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeRegularUser();
        $user->createToken('test')->plainTextToken;
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/v1/admin/users/$user->id")
            ->assertStatus(200);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id'   => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    /** @test */
    public function admin_cannot_delete_themselves(): void
    {
        $admin = $this->makeAdmin();
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/v1/admin/users/$admin->id")
            ->assertStatus(409);
    }

    /** @test */
    public function moderator_cannot_delete_users(): void
    {
        $user = $this->makeRegularUser();
        Sanctum::actingAs($this->makeModerator());

        $this->deleteJson("/api/v1/admin/users/$user->id")
            ->assertStatus(403);
    }

    // =========================================================================
    // GET /api/v1/admin/users/{userId}/verification
    // =========================================================================

    /** @test */
    public function moderator_can_view_a_users_identity_verification(): void
    {
        $mod  = $this->makeModerator();
        $user = $this->makeRegularUser();
        $verification = $this->makePendingVerification(['user_id' => $user->id]);
        Sanctum::actingAs($mod);

        $this->getJson("/api/v1/admin/users/$user->id/verification")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $verification->id);
    }

    /** @test */
    public function returns_null_data_when_user_has_no_verification(): void
    {
        $mod  = $this->makeModerator();
        $user = $this->makeRegularUser();
        Sanctum::actingAs($mod);

        $this->getJson("/api/v1/admin/users/$user->id/verification")
            ->assertStatus(200)
            ->assertJsonPath('data', null);
    }

    /** @test */
    public function verification_returns_404_for_unknown_user(): void
    {
        Sanctum::actingAs($this->makeModerator());

        $this->getJson('/api/v1/admin/users/' . fake()->uuid() . '/verification')
            ->assertStatus(404);
    }

    /** @test */
    public function regular_user_cannot_view_verification_detail(): void
    {
        $user = $this->makeRegularUser();
        Sanctum::actingAs($this->makeRegularUser());

        $this->getJson("/api/v1/admin/users/$user->id/verification")
            ->assertStatus(403);
    }

    // =========================================================================
    // GET /api/v1/admin/users/{userId}/reports
    // =========================================================================

    /** @test */
    public function moderator_can_list_reports_against_a_user(): void
    {
        $mod      = $this->makeModerator();
        $reported = $this->makeRegularUser();
        $reporter = $this->makeRegularUser();
        Sanctum::actingAs($mod);

        Report::factory()->create([
            'reporter_id'      => $reporter->id,
            'reported_user_id' => $reported->id,
        ]);
        Report::factory()->create([
            'reporter_id'      => $reporter->id,
            'reported_user_id' => $reported->id,
        ]);
        // Another user's reports — must not appear
        Report::factory()->create([
            'reporter_id'      => $reporter->id,
            'reported_user_id' => $this->makeRegularUser()->id,
        ]);

        $this->getJson("/api/v1/admin/users/$reported->id/reports")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function user_reports_endpoint_returns_404_for_unknown_user(): void
    {
        Sanctum::actingAs($this->makeModerator());

        $this->getJson('/api/v1/admin/users/' . fake()->uuid() . '/reports')
            ->assertStatus(404);
    }

    /** @test */
    public function regular_user_cannot_list_reports_against_a_user(): void
    {
        $user = $this->makeRegularUser();
        Sanctum::actingAs($this->makeRegularUser());

        $this->getJson("/api/v1/admin/users/$user->id/reports")
            ->assertStatus(403);
    }
}
