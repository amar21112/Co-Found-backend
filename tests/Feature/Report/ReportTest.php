<?php

namespace Tests\Feature\Report;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportTest extends TestCase
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

    private function makeReport(User $reporter, User $reported, array $overrides = []): Report
    {
        return Report::factory()->create(array_merge([
            'reporter_id'      => $reporter->id,
            'reported_user_id' => $reported->id,
            'status'           => 'pending',
            'report_type'      => 'harassment',
        ], $overrides));
    }

    private function validPayload(User $reported, array $overrides = []): array
    {
        return array_merge([
            'reported_user_id' => $reported->id,
            'report_type'      => 'harassment',
            'description'      => 'This user is harassing me.',
        ], $overrides);
    }

    // =========================================================================
    // GET /api/v1/reports
    // =========================================================================

    /** @test */
    public function user_can_list_their_own_reports(): void
    {
        $reporter = $this->makeUser();
        $reported = $this->makeUser();
        Sanctum::actingAs($reporter);

        $this->makeReport($reporter, $reported);
        $this->makeReport($reporter, $reported);

        // Another user's report — must not appear
        $this->makeReport($this->makeUser(), $reported);

        $this->getJson('/api/v1/reports')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function reports_can_be_filtered_by_status(): void
    {
        $reporter = $this->makeUser();
        $reported = $this->makeUser();
        Sanctum::actingAs($reporter);

        $this->makeReport($reporter, $reported, ['status' => 'pending']);
        $this->makeReport($reporter, $reported, ['status' => 'resolved']);

        $this->getJson('/api/v1/reports?status=pending')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function unauthenticated_user_cannot_list_reports(): void
    {
        $this->getJson('/api/v1/reports')->assertStatus(401);
    }

    // =========================================================================
    // POST /api/v1/reports
    // =========================================================================

    /** @test */
    public function user_can_submit_a_report_against_a_user(): void
    {
        $reporter = $this->makeUser();
        $reported = $this->makeUser();
        Sanctum::actingAs($reporter);

        $this->postJson('/api/v1/reports', $this->validPayload($reported))
            ->assertStatus(201)
            ->assertJsonPath('data.report_type', 'harassment')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('reports', [
            'reporter_id'      => $reporter->id,
            'reported_user_id' => $reported->id,
            'report_type'      => 'harassment',
        ]);
    }

    /** @test */
    public function user_can_submit_a_report_against_content(): void
    {
        $reporter = $this->makeUser();
        Sanctum::actingAs($reporter);

        $this->postJson('/api/v1/reports', [
            'reported_content_type' => 'project',
            'reported_content_id'   => Str::uuid()->toString(),
            'report_type'           => 'spam',
            'description'           => 'This project is spam.',
        ])->assertStatus(201);
    }

    /** @test */
    public function report_requires_either_user_or_content(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/v1/reports', [
            'report_type' => 'spam',
        ])->assertStatus(422);
    }

    /** @test */
    public function report_type_must_be_valid(): void
    {
        $reporter = $this->makeUser();
        $reported = $this->makeUser();
        Sanctum::actingAs($reporter);

        $this->postJson('/api/v1/reports', $this->validPayload($reported, [
            'report_type' => 'invalid_type',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['report_type']);
    }

    /** @test */
    public function evidence_urls_must_be_valid(): void
    {
        $reporter = $this->makeUser();
        $reported = $this->makeUser();
        Sanctum::actingAs($reporter);

        $this->postJson('/api/v1/reports', $this->validPayload($reported, [
            'evidence' => ['not-a-url'],
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['evidence.0']);
    }

    /** @test */
    public function guest_cannot_submit_report(): void
    {
        $reported = $this->makeUser();
        Sanctum::actingAs(User::factory()->guest()->create());

        $this->postJson('/api/v1/reports', $this->validPayload($reported))
            ->assertStatus(403);
    }

    // =========================================================================
    // GET /api/v1/reports/{id}
    // =========================================================================

    /** @test */
    public function user_can_view_their_own_report(): void
    {
        $reporter = $this->makeUser();
        $reported = $this->makeUser();
        $report   = $this->makeReport($reporter, $reported);
        Sanctum::actingAs($reporter);

        $this->getJson("/api/v1/reports/$report->id")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $report->id);
    }

    /** @test */
    public function user_cannot_view_another_users_report(): void
    {
        $reporter = $this->makeUser();
        $reported = $this->makeUser();
        $report   = $this->makeReport($reporter, $reported);
        Sanctum::actingAs($this->makeUser());

        $this->getJson("/api/v1/reports/$report->id")
            ->assertStatus(404);
    }

    /** @test */
    public function show_returns_404_for_unknown_report(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->getJson('/api/v1/reports/00000000-0000-0000-0000-000000000001')
            ->assertStatus(404);
    }

    // =========================================================================
    // PATCH /api/v1/reports/{id}  (update / add detail)
    // =========================================================================

    /** @test */
    public function reporter_can_add_more_description_while_pending(): void
    {
        $reporter = $this->makeUser();
        $reported = $this->makeUser();
        $report   = $this->makeReport($reporter, $reported, ['status' => 'pending']);
        Sanctum::actingAs($reporter);

        $this->patchJson("/api/v1/reports/$report->id", [
            'description' => 'Additional details about the harassment.',
        ])->assertStatus(200)
            ->assertJsonPath('data.description', 'Additional details about the harassment.');
    }

    /** @test */
    public function cannot_update_a_resolved_report(): void
    {
        $reporter = $this->makeUser();
        $reported = $this->makeUser();
        $report   = $this->makeReport($reporter, $reported, ['status' => 'resolved']);
        Sanctum::actingAs($reporter);

        $this->patchJson("/api/v1/reports/$report->id", [
            'description' => 'New details.',
        ])->assertStatus(409);
    }

    // =========================================================================
    // DELETE /api/v1/reports/{id}  (withdraw)
    // =========================================================================

    /** @test */
    public function reporter_can_withdraw_a_pending_report(): void
    {
        $reporter = $this->makeUser();
        $reported = $this->makeUser();
        $report   = $this->makeReport($reporter, $reported, ['status' => 'pending']);
        Sanctum::actingAs($reporter);

        $this->patchJson("/api/v1/reports/$report->id/withdraw")
            ->assertStatus(200);

        $this->assertDatabaseHas('reports', [
            'id'     => $report->id,
            'status' => 'withdrawn',
        ]);
    }

    /** @test */
    public function cannot_withdraw_a_report_under_review(): void
    {
        $reporter = $this->makeUser();
        $reported = $this->makeUser();
        $report   = $this->makeReport($reporter, $reported, ['status' => 'under_review']);
        Sanctum::actingAs($reporter);

        $this->patchJson("/api/v1/reports/$report->id/withdraw")
            ->assertStatus(409);
    }

    /** @test */
    public function user_cannot_withdraw_another_users_report(): void
    {
        $reporter = $this->makeUser();
        $reported = $this->makeUser();
        $report   = $this->makeReport($reporter, $reported);
        Sanctum::actingAs($this->makeUser());

        $this->patchJson("/api/v1/reports/$report->id/withdraw")
            ->assertStatus(404);
    }

    /** @test */
    public function admin_can_hard_delete_a_report(): void
    {
        $admin    = User::factory()->moderator()->create();
        $reporter = $this->makeUser();
        $reported = $this->makeUser();
        $report   = $this->makeReport($reporter, $reported, ['status' => 'withdrawn']);
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/v1/reports/$report->id")
            ->assertStatus(200);

        $this->assertDatabaseMissing('reports', [
            'id' => $report->id,
        ]);
    }

    /** @test */
    public function non_admin_cannot_hard_delete_a_report(): void
    {
        $reporter = $this->makeUser();
        $reported = $this->makeUser();
        $report   = $this->makeReport($reporter, $reported, ['status' => 'withdrawn']);
        Sanctum::actingAs($reporter);

        $this->deleteJson("/api/v1/reports/$report->id")
            ->assertStatus(403);
    }
}
