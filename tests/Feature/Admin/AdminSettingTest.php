<?php

namespace Tests\Feature\Admin;

use App\Models\SystemSetting;
use Laravel\Sanctum\Sanctum;

class AdminSettingTest extends AdminTestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeSetting(array $overrides = []): SystemSetting
    {
        return SystemSetting::factory()->create($overrides);
    }

    // =========================================================================
    // GET /api/v1/admin/settings
    // =========================================================================

    /** @test */
    public function admin_can_list_settings(): void
    {
        $admin = $this->makeAdmin();
        Sanctum::actingAs($admin);

        $this->makeSetting();
        $this->makeSetting();

        $this->getJson('/api/v1/admin/settings')
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function moderator_cannot_list_settings(): void
    {
        Sanctum::actingAs($this->makeModerator());

        $this->getJson('/api/v1/admin/settings')
            ->assertStatus(403);
    }

    /** @test */
    public function regular_user_cannot_list_settings(): void
    {
        Sanctum::actingAs($this->makeRegularUser());

        $this->getJson('/api/v1/admin/settings')
            ->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/admin/settings')
            ->assertStatus(401);
    }

    // =========================================================================
    // GET /api/v1/admin/settings/{key}
    // =========================================================================

    /** @test */
    public function admin_can_view_a_setting_by_key(): void
    {
        $admin   = $this->makeAdmin();
        $this->makeSetting(['setting_key' => 'max_file_size_mb']);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/settings/max_file_size_mb')
            ->assertStatus(200)
            ->assertJsonPath('data.setting_key', 'max_file_size_mb');
    }

    /** @test */
    public function show_returns_404_for_unknown_key(): void
    {
        Sanctum::actingAs($this->makeAdmin());

        $this->getJson('/api/v1/admin/settings/does_not_exist')
            ->assertStatus(404);
    }

    /** @test */
    public function moderator_cannot_view_a_setting(): void
    {
        $this->makeSetting(['setting_key' => 'test_key']);
        Sanctum::actingAs($this->makeModerator());

        $this->getJson('/api/v1/admin/settings/test_key')
            ->assertStatus(403);
    }

    // =========================================================================
    // PATCH /api/v1/admin/settings/{key}
    // =========================================================================

    /** @test */
    public function admin_can_update_a_setting_value(): void
    {
        $admin   = $this->makeAdmin();
        $this->makeSetting(['setting_key' => 'max_projects_per_user']);
        Sanctum::actingAs($admin);

        $this->patchJson('/api/v1/admin/settings/max_projects_per_user', [
            'setting_value' => 20,
            'change_reason' => 'Increased limit for beta users.',
        ])->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['setting_key', 'setting_value']]);

        $this->assertDatabaseHas('system_settings', [
            'setting_key' => 'max_projects_per_user',
        ]);
    }

    /** @test */
    public function setting_value_is_required_to_update(): void
    {
        $admin = $this->makeAdmin();
        $this->makeSetting(['setting_key' => 'some_key']);
        Sanctum::actingAs($admin);

        $this->patchJson('/api/v1/admin/settings/some_key')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['setting_value']);
    }

    /** @test */
    public function updating_setting_writes_admin_action_log(): void
    {
        $admin   = $this->makeAdmin();
        $this->makeSetting(['setting_key' => 'log_test_setting']);
        Sanctum::actingAs($admin);

        $this->patchJson('/api/v1/admin/settings/log_test_setting', [
            'setting_value' => 'new_value',
        ])->assertStatus(200);

        $this->assertDatabaseHas('admin_actions', [
            'admin_id'    => $admin->id,
            'action_type' => 'setting_updated',
            'target_type' => 'system_setting',
        ]);
    }

    /** @test */
    public function moderator_cannot_update_settings(): void
    {
        $this->makeSetting(['setting_key' => 'mod_test']);
        Sanctum::actingAs($this->makeModerator());

        $this->patchJson('/api/v1/admin/settings/mod_test', [
            'setting_value' => 'hacked',
        ])->assertStatus(403);
    }

    /** @test */
    public function update_returns_404_for_unknown_key(): void
    {
        Sanctum::actingAs($this->makeAdmin());

        $this->patchJson('/api/v1/admin/settings/ghost_key', [
            'setting_value' => 'anything',
        ])->assertStatus(404);
    }

    // =========================================================================
    // GET /api/v1/admin/settings/{key}/history
    // =========================================================================

    /** @test */
    public function admin_can_view_change_history_for_a_setting(): void
    {
        $admin   = $this->makeAdmin();
        $this->makeSetting(['setting_key' => 'history_key']);
        Sanctum::actingAs($admin);

        // Create history by updating the setting
        $this->patchJson('/api/v1/admin/settings/history_key', [
            'setting_value' => 'value_one',
        ]);
        $this->patchJson('/api/v1/admin/settings/history_key', [
            'setting_value' => 'value_two',
        ]);

        $this->getJson('/api/v1/admin/settings/history_key/history')
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
                'meta',
            ]);

        $this->assertDatabaseCount('configuration_history', 2);
    }

    /** @test */
    public function history_returns_404_for_unknown_key(): void
    {
        Sanctum::actingAs($this->makeAdmin());

        $this->getJson('/api/v1/admin/settings/ghost_key/history')
            ->assertStatus(404);
    }

    /** @test */
    public function moderator_cannot_view_setting_history(): void
    {
        $this->makeSetting(['setting_key' => 'hist_key']);
        Sanctum::actingAs($this->makeModerator());

        $this->getJson('/api/v1/admin/settings/hist_key/history')
            ->assertStatus(403);
    }
}
