<?php

namespace Tests\Feature\Admin;

use App\Models\SystemLog;
use Laravel\Sanctum\Sanctum;

class AdminSystemLogTest extends AdminTestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeLog(array $overrides = []): SystemLog
    {
        return SystemLog::factory()->create($overrides);
    }

    // =========================================================================
    // GET /api/v1/admin/system-logs
    // =========================================================================

    /** @test */
    public function moderator_can_list_system_logs(): void
    {
        $mod = $this->makeModerator();
        Sanctum::actingAs($mod);

        $this->makeLog();
        $this->makeLog();

        $this->getJson('/api/v1/admin/system-logs')
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function admin_can_list_system_logs(): void
    {
        Sanctum::actingAs($this->makeAdmin());

        $this->makeLog();

        $this->getJson('/api/v1/admin/system-logs')
            ->assertStatus(200);
    }

    /** @test */
    public function logs_can_be_filtered_by_log_level(): void
    {
        $mod = $this->makeModerator();
        Sanctum::actingAs($mod);

        $this->makeLog(['log_level' => 'error']);
        $this->makeLog(['log_level' => 'error']);
        $this->makeLog(['log_level' => 'info']);

        $this->getJson('/api/v1/admin/system-logs?log_level=error')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function logs_can_be_filtered_by_component(): void
    {
        $mod = $this->makeModerator();
        Sanctum::actingAs($mod);

        $this->makeLog(['component' => 'auth']);
        $this->makeLog(['component' => 'auth']);
        $this->makeLog(['component' => 'ml']);

        $this->getJson('/api/v1/admin/system-logs?component=auth')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function logs_can_be_filtered_by_event_type(): void
    {
        $mod = $this->makeModerator();
        Sanctum::actingAs($mod);

        $this->makeLog(['event_type' => 'login_failed']);
        $this->makeLog(['event_type' => 'login_failed']);
        $this->makeLog(['event_type' => 'token_issued']);

        $this->getJson('/api/v1/admin/system-logs?event_type=login_failed')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function regular_user_cannot_access_system_logs(): void
    {
        Sanctum::actingAs($this->makeRegularUser());

        $this->getJson('/api/v1/admin/system-logs')
            ->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/admin/system-logs')
            ->assertStatus(401);
    }
}
