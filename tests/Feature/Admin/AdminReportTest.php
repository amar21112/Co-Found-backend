<?php

namespace Tests\Feature\Admin;

use App\Models\Report;
use Laravel\Sanctum\Sanctum;

class AdminReportTest extends AdminTestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeReport(array $overrides = []): Report
    {
        return Report::factory()->create($overrides);
    }

    // =========================================================================
    // GET /api/v1/admin/reports
    // =========================================================================

    /** @test */
    public function moderator_can_list_all_reports(): void
    {
        $mod = $this->makeModerator();
        $reporter = $this->makeRegularUser();
        Sanctum::actingAs($mod);

        $this->makeReport(['reporter_id' => $reporter->id]);
        $this->makeReport(['reporter_id' => $reporter->id]);

        $this->getJson('/api/v1/admin/reports')
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function admin_can_list_all_reports(): void
    {
        $admin = $this->makeAdmin();
        Sanctum::actingAs($admin);

        $this->makeReport(['reporter_id' => $this->makeRegularUser()->id]);

        $this->getJson('/api/v1/admin/reports')
            ->assertStatus(200);
    }

    /** @test */
    public function reports_can_be_filtered_by_status(): void
    {
        $mod = $this->makeModerator();
        $reporter = $this->makeRegularUser();
        Sanctum::actingAs($mod);

        $this->makeReport(['reporter_id' => $reporter->id, 'status' => 'pending']);
        $this->makeReport(['reporter_id' => $reporter->id, 'status' => 'resolved']);
        $this->makeReport(['reporter_id' => $reporter->id, 'status' => 'resolved']);

        $this->getJson('/api/v1/admin/reports?status=resolved')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function regular_user_cannot_list_admin_reports(): void
    {
        Sanctum::actingAs($this->makeRegularUser());

        $this->getJson('/api/v1/admin/reports')
            ->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/admin/reports')
            ->assertStatus(401);
    }

    // =========================================================================
    // GET /api/v1/admin/reports/{id}
    // =========================================================================

    /** @test */
    public function moderator_can_view_a_single_report(): void
    {
        $mod = $this->makeModerator();
        $report = $this->makeReport(['reporter_id' => $this->makeRegularUser()->id]);
        Sanctum::actingAs($mod);

        $this->getJson("/api/v1/admin/reports/$report->id")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $report->id);
    }

    /** @test */
    public function show_returns_404_for_unknown_report(): void
    {
        Sanctum::actingAs($this->makeModerator());

        $this->getJson('/api/v1/admin/reports/' . fake()->uuid())
            ->assertStatus(404);
    }

    /** @test */
    public function regular_user_cannot_view_admin_report(): void
    {
        $report = $this->makeReport(['reporter_id' => $this->makeRegularUser()->id]);
        Sanctum::actingAs($this->makeRegularUser());

        $this->getJson("/api/v1/admin/reports/$report->id")
            ->assertStatus(403);
    }

    // =========================================================================
    // PATCH /api/v1/admin/reports/{id}
    // =========================================================================

    /** @test */
    public function moderator_can_update_report_status(): void
    {
        $mod = $this->makeModerator();
        $report = $this->makeReport([
            'reporter_id' => $this->makeRegularUser()->id,
            'status'      => 'pending',
        ]);
        Sanctum::actingAs($mod);

        $this->patchJson("/api/v1/admin/reports/$report->id", [
            'status' => 'under_review',
        ])->assertStatus(200)
            ->assertJsonPath('data.status', 'under_review');

        $this->assertDatabaseHas('reports', [
            'id'     => $report->id,
            'status' => 'under_review',
        ]);
    }

    /** @test */
    public function providing_resolution_action_stamps_resolved_at_and_sets_resolved(): void
    {
        $mod = $this->makeModerator();
        $report = $this->makeReport([
            'reporter_id' => $this->makeRegularUser()->id,
            'status'      => 'under_review',
        ]);
        Sanctum::actingAs($mod);

        $this->patchJson("/api/v1/admin/reports/$report->id", [
            'resolution_action' => 'Content removed and user warned.',
        ])->assertStatus(200)
            ->assertJsonPath('data.status', 'resolved');

        $this->assertDatabaseHas('reports', [
            'id'                => $report->id,
            'status'            => 'resolved',
            'resolved_by'       => $mod->id,
        ]);

        $this->assertNotNull($report->fresh()->resolved_at);
    }

    /** @test */
    public function moderator_can_assign_report_to_another_moderator(): void
    {
        $mod     = $this->makeModerator();
        $modTwo  = $this->makeModerator();
        $report  = $this->makeReport(['reporter_id' => $this->makeRegularUser()->id]);
        Sanctum::actingAs($mod);

        $this->patchJson("/api/v1/admin/reports/$report->id", [
            'assigned_to' => $modTwo->id,
        ])->assertStatus(200)
            ->assertJsonPath('data.assigned_moderator.id', $modTwo->id);
    }

    /** @test */
    public function regular_user_cannot_update_report(): void
    {
        $report = $this->makeReport(['reporter_id' => $this->makeRegularUser()->id]);
        Sanctum::actingAs($this->makeRegularUser());

        $this->patchJson("/api/v1/admin/reports/$report->id", [
            'status' => 'resolved',
        ])->assertStatus(403);
    }

    /** @test */
    public function update_returns_404_for_unknown_report(): void
    {
        Sanctum::actingAs($this->makeModerator());

        $this->patchJson('/api/v1/admin/reports/' . fake()->uuid(), [
            'status' => 'resolved',
        ])->assertStatus(404);
    }
}
