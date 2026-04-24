<?php

namespace Tests\Feature\Admin;

use App\Enums\AccountStatus;
use App\Models\UserRestriction;
use Laravel\Sanctum\Sanctum;

class AdminRestrictionTest extends AdminTestCase
{
    // =========================================================================
    // GET /api/v1/admin/restrictions
    // =========================================================================

    /** @test */
    public function moderator_can_list_restrictions(): void
    {
        $mod    = $this->makeModerator();
        $target = $this->makeRegularUser();
        Sanctum::actingAs($mod);

        $this->makeActiveRestriction($target, $mod);
        $this->makeActiveRestriction($target, $mod, 'posting_ban');

        $this->getJson('/api/v1/admin/restrictions')
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [['id', 'restriction_type', 'reason', 'is_active', 'user']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    /** @test */
    public function list_can_be_filtered_by_is_active(): void
    {
        $mod    = $this->makeModerator();
        $target = $this->makeRegularUser();
        Sanctum::actingAs($mod);

        $this->makeActiveRestriction($target, $mod);
        $this->makeLiftedRestriction($target, $mod);

        $this->getJson('/api/v1/admin/restrictions?is_active=1')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function list_active_filter_excludes_logically_expired_restrictions(): void
    {
        $mod    = $this->makeModerator();
        $target = $this->makeRegularUser();
        Sanctum::actingAs($mod);

        UserRestriction::factory()->create([
            'user_id'          => $target->id,
            'restricted_by'    => $mod->id,
            'restriction_type' => 'messaging_ban',
            'is_active'        => true,
            'expires_at'       => now()->addDay(),
        ]);

        // is_active=true in DB but past expires_at — logically expired
        UserRestriction::factory()->create([
            'user_id'          => $target->id,
            'restricted_by'    => $mod->id,
            'restriction_type' => 'posting_ban',
            'is_active'        => true,
            'expires_at'       => now()->subHour(),
        ]);

        $this->getJson('/api/v1/admin/restrictions?is_active=1')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function list_can_be_filtered_by_restriction_type(): void
    {
        $mod    = $this->makeModerator();
        $target = $this->makeRegularUser();
        Sanctum::actingAs($mod);

        $this->makeActiveRestriction($target, $mod);
        $this->makeActiveRestriction($target, $mod, 'posting_ban');

        $this->getJson('/api/v1/admin/restrictions?restriction_type=messaging_ban')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function regular_user_cannot_list_restrictions(): void
    {
        Sanctum::actingAs($this->makeRegularUser());

        $this->getJson('/api/v1/admin/restrictions')
            ->assertStatus(403);
    }

    // =========================================================================
    // GET /api/v1/admin/restrictions/{id}
    // =========================================================================

    /** @test */
    public function moderator_can_view_a_single_restriction(): void
    {
        $mod         = $this->makeModerator();
        $target      = $this->makeRegularUser();
        $restriction = $this->makeActiveRestriction($target, $mod);
        Sanctum::actingAs($mod);

        $this->getJson("/api/v1/admin/restrictions/$restriction->id")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $restriction->id)
            ->assertJsonPath('data.restriction_type', 'messaging_ban')
            ->assertJsonStructure([
                'data' => [
                    'id', 'restriction_type', 'reason', 'is_active',
                    'starts_at', 'expires_at', 'user', 'restricted_by',
                ],
            ]);
    }

    /** @test */
    public function show_returns_404_for_unknown_restriction(): void
    {
        Sanctum::actingAs($this->makeModerator());

        $this->getJson('/api/v1/admin/restrictions/00000000-0000-0000-0000-000000000001')
            ->assertStatus(404);
    }

    /** @test */
    public function regular_user_cannot_view_a_restriction(): void
    {
        $mod         = $this->makeModerator();
        $target      = $this->makeRegularUser();
        $restriction = $this->makeActiveRestriction($target, $mod);
        Sanctum::actingAs($this->makeRegularUser());

        $this->getJson("/api/v1/admin/restrictions/$restriction->id")
            ->assertStatus(403);
    }

    // =========================================================================
    // GET /api/v1/admin/users/{userId}/restrictions
    // =========================================================================

    /** @test */
    public function moderator_can_list_restrictions_for_a_user(): void
    {
        $mod    = $this->makeModerator();
        $target = $this->makeRegularUser();
        Sanctum::actingAs($mod);

        $this->makeActiveRestriction($target, $mod);
        $this->makeActiveRestriction($target, $mod, 'posting_ban');
        $this->makeLiftedRestriction($target, $mod);

        // Make sure a different user's restriction doesn't appear
        $other = $this->makeRegularUser();
        $this->makeActiveRestriction($other, $mod);

        $this->getJson("/api/v1/admin/users/$target->id/restrictions")
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function user_restriction_list_can_be_filtered_by_is_active(): void
    {
        $mod    = $this->makeModerator();
        $target = $this->makeRegularUser();
        Sanctum::actingAs($mod);

        $this->makeActiveRestriction($target, $mod);
        $this->makeLiftedRestriction($target, $mod);

        $this->getJson("/api/v1/admin/users/$target->id/restrictions?is_active=1")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function user_restriction_list_returns_empty_for_user_with_no_restrictions(): void
    {
        $target = $this->makeRegularUser();
        Sanctum::actingAs($this->makeModerator());

        $this->getJson("/api/v1/admin/users/$target->id/restrictions")
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function regular_user_cannot_list_user_restrictions(): void
    {
        $target = $this->makeRegularUser();
        Sanctum::actingAs($this->makeRegularUser());

        $this->getJson("/api/v1/admin/users/$target->id/restrictions")
            ->assertStatus(403);
    }

    // =========================================================================
    // POST /api/v1/admin/restrictions
    // =========================================================================

    /** @test */
    public function moderator_can_issue_a_restriction(): void
    {
        $mod    = $this->makeModerator();
        $target = $this->makeRegularUser();
        Sanctum::actingAs($mod);

        $this->postJson('/api/v1/admin/restrictions', [
            'user_id'          => $target->id,
            'restriction_type' => 'messaging_ban',
            'reason'           => 'Repeated spam messages sent to multiple users.',
            'duration_hours'   => 24,
        ])->assertStatus(201)
            ->assertJsonPath('data.restriction_type', 'messaging_ban')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('user_restrictions', [
            'user_id'          => $target->id,
            'restricted_by'    => $mod->id,
            'restriction_type' => 'messaging_ban',
            'is_active'        => true,
        ]);

        $this->assertDatabaseHas('admin_actions', [
            'admin_id'    => $mod->id,
            'action_type' => 'restriction_issued',
            'target_id'   => $target->id,
        ]);
    }

    /** @test */
    public function full_suspension_sets_user_account_status_to_suspended(): void
    {
        $mod    = $this->makeModerator();
        $target = $this->makeRegularUser();
        Sanctum::actingAs($mod);

        $this->postJson('/api/v1/admin/restrictions', [
            'user_id'          => $target->id,
            'restriction_type' => 'full_suspension',
            'reason'           => 'Severe violation of community guidelines.',
            'duration_hours'   => 72,
        ])->assertStatus(201);

        $this->assertEquals(AccountStatus::Suspended, $target->fresh()->account_status);
    }

    /** @test */
    public function permanent_restriction_has_null_duration_and_no_expiry(): void
    {
        $mod    = $this->makeModerator();
        $target = $this->makeRegularUser();
        Sanctum::actingAs($mod);

        $response = $this->postJson('/api/v1/admin/restrictions', [
            'user_id'          => $target->id,
            'restriction_type' => 'application_ban',
            'reason'           => 'Consistent misuse of the application feature.',
        ])->assertStatus(201);

        $this->assertNull($response->json('data.duration_hours'));
        $this->assertNull($response->json('data.expires_at'));
        $this->assertTrue($response->json('data.is_permanent'));
    }

    /** @test */
    public function issuing_same_type_restriction_deactivates_previous_one(): void
    {
        $mod    = $this->makeModerator();
        $target = $this->makeRegularUser();
        Sanctum::actingAs($mod);

        $first = $this->makeActiveRestriction($target, $mod);

        $this->postJson('/api/v1/admin/restrictions', [
            'user_id'          => $target->id,
            'restriction_type' => 'messaging_ban',
            'reason'           => 'Continued spam behaviour after warning.',
            'duration_hours'   => 48,
        ])->assertStatus(201);

        $this->assertFalse($first->fresh()->is_active);

        $activeCount = UserRestriction::where('user_id', $target->id)
            ->where('restriction_type', 'messaging_ban')
            ->where('is_active', true)
            ->count();
        $this->assertEquals(1, $activeCount);
    }

    /** @test */
    public function store_fails_with_invalid_restriction_type(): void
    {
        $target = $this->makeRegularUser();
        Sanctum::actingAs($this->makeModerator());

        $this->postJson('/api/v1/admin/restrictions', [
            'user_id'          => $target->id,
            'restriction_type' => 'made_up_type',
            'reason'           => 'Some reason here.',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['restriction_type']);
    }

    /** @test */
    public function store_fails_with_reason_too_short(): void
    {
        $target = $this->makeRegularUser();
        Sanctum::actingAs($this->makeModerator());

        $this->postJson('/api/v1/admin/restrictions', [
            'user_id'          => $target->id,
            'restriction_type' => 'messaging_ban',
            'reason'           => 'Short',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    /** @test */
    public function store_fails_for_non_existent_user(): void
    {
        Sanctum::actingAs($this->makeModerator());

        $this->postJson('/api/v1/admin/restrictions', [
            'user_id'          => '00000000-0000-0000-0000-000000000001',
            'restriction_type' => 'messaging_ban',
            'reason'           => 'Some detailed reason here.',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    /** @test */
    public function regular_user_cannot_issue_restrictions(): void
    {
        $target = $this->makeRegularUser();
        Sanctum::actingAs($this->makeRegularUser());

        $this->postJson('/api/v1/admin/restrictions', [
            'user_id'          => $target->id,
            'restriction_type' => 'messaging_ban',
            'reason'           => 'Some detailed reason here.',
        ])->assertStatus(403);
    }

    // =========================================================================
    // PATCH /api/v1/admin/restrictions/{id}/lift
    // =========================================================================

    /** @test */
    public function moderator_can_lift_an_active_restriction(): void
    {
        $mod         = $this->makeModerator();
        $target      = $this->makeRegularUser();
        $restriction = $this->makeActiveRestriction($target, $mod);
        Sanctum::actingAs($mod);

        $this->patchJson("/api/v1/admin/restrictions/$restriction->id/lift")
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $restriction->refresh();
        $this->assertFalse($restriction->is_active);
        $this->assertNotNull($restriction->lifted_at);
        $this->assertEquals($mod->id, $restriction->lifted_by);

        $this->assertDatabaseHas('admin_actions', [
            'admin_id'    => $mod->id,
            'action_type' => 'restriction_lifted',
        ]);
    }

    /** @test */
    public function lifting_full_suspension_restores_user_account_status(): void
    {
        $mod    = $this->makeModerator();
        $target = $this->makeRegularUser(['account_status' => AccountStatus::Suspended->value]);
        Sanctum::actingAs($mod);

        $restriction = $this->makeActiveRestriction($target, $mod, 'full_suspension');

        $this->patchJson("/api/v1/admin/restrictions/$restriction->id/lift")
            ->assertStatus(200);

        $this->assertEquals(AccountStatus::Active, $target->fresh()->account_status);
    }

    /** @test */
    public function lifting_full_suspension_does_not_restore_status_if_another_suspension_exists(): void
    {
        $mod    = $this->makeModerator();
        $target = $this->makeRegularUser(['account_status' => AccountStatus::Suspended->value]);
        Sanctum::actingAs($mod);

        $first  = $this->makeActiveRestriction($target, $mod, 'full_suspension');
        $this->makeActiveRestriction($target, $mod, 'full_suspension');

        $this->patchJson("/api/v1/admin/restrictions/$first->id/lift")
            ->assertStatus(200);

        $this->assertEquals(AccountStatus::Suspended, $target->fresh()->account_status);
    }

    /** @test */
    public function cannot_lift_an_already_lifted_restriction(): void
    {
        $mod         = $this->makeModerator();
        $target      = $this->makeRegularUser();
        $restriction = $this->makeLiftedRestriction($target, $mod);
        Sanctum::actingAs($mod);

        $this->patchJson("/api/v1/admin/restrictions/$restriction->id/lift")
            ->assertStatus(409);
    }

    /** @test */
    public function lift_returns_404_for_unknown_restriction(): void
    {
        Sanctum::actingAs($this->makeModerator());

        $this->patchJson('/api/v1/admin/restrictions/00000000-0000-0000-0000-000000000001/lift')
            ->assertStatus(404);
    }

    /** @test */
    public function regular_user_cannot_lift_restrictions(): void
    {
        $mod         = $this->makeModerator();
        $target      = $this->makeRegularUser();
        $restriction = $this->makeActiveRestriction($target, $mod);
        Sanctum::actingAs($this->makeRegularUser());

        $this->patchJson("/api/v1/admin/restrictions/$restriction->id/lift")
            ->assertStatus(403);
    }
}
