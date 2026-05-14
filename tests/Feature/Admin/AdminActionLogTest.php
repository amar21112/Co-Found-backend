<?php

namespace Tests\Feature\Admin;

use App\Models\AdminAction;
use Laravel\Sanctum\Sanctum;

class AdminActionLogTest extends AdminTestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeLog(array $overrides = []): AdminAction
    {
        return AdminAction::factory()->create($overrides);
    }

    // =========================================================================
    // GET /api/v1/admin/action-logs
    // =========================================================================

    /** @test */
    public function moderator_can_list_action_logs(): void
    {
        $mod = $this->makeModerator();
        Sanctum::actingAs($mod);

        $this->makeLog(['admin_id' => $mod->id]);
        $this->makeLog(['admin_id' => $mod->id]);

        $this->getJson('/api/v1/admin/action-logs')
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function admin_can_list_action_logs(): void
    {
        $admin = $this->makeAdmin();
        Sanctum::actingAs($admin);

        $this->makeLog(['admin_id' => $admin->id]);

        $this->getJson('/api/v1/admin/action-logs')
            ->assertStatus(200);
    }

    /** @test */
    public function logs_can_be_filtered_by_admin_id(): void
    {
        $modA = $this->makeModerator();
        $modB = $this->makeModerator();
        Sanctum::actingAs($modA);

        $this->makeLog(['admin_id' => $modA->id]);
        $this->makeLog(['admin_id' => $modA->id]);
        $this->makeLog(['admin_id' => $modB->id]);

        $this->getJson("/api/v1/admin/action-logs?admin_id=$modA->id")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function logs_can_be_filtered_by_action_type(): void
    {
        $mod = $this->makeModerator();
        Sanctum::actingAs($mod);

        $this->makeLog(['admin_id' => $mod->id, 'action_type' => 'user_updated']);
        $this->makeLog(['admin_id' => $mod->id, 'action_type' => 'user_updated']);
        $this->makeLog(['admin_id' => $mod->id, 'action_type' => 'report_updated']);

        $this->getJson('/api/v1/admin/action-logs?action_type=user_updated')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function logs_can_be_filtered_by_target_type(): void
    {
        $mod = $this->makeModerator();
        Sanctum::actingAs($mod);

        $this->makeLog(['admin_id' => $mod->id, 'target_type' => 'user']);
        $this->makeLog(['admin_id' => $mod->id, 'target_type' => 'user']);
        $this->makeLog(['admin_id' => $mod->id, 'target_type' => 'report']);

        $this->getJson('/api/v1/admin/action-logs?target_type=user')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function regular_user_cannot_access_action_logs(): void
    {
        Sanctum::actingAs($this->makeRegularUser());

        $this->getJson('/api/v1/admin/action-logs')
            ->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/admin/action-logs')
            ->assertStatus(401);
    }
}
