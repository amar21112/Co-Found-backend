<?php

namespace Tests\Feature\Admin;

use App\Enums\IdentityVerificationLevel;
use App\Enums\IdentityVerificationStatus;
use App\Models\IdentityVerification;
use Laravel\Sanctum\Sanctum;

class AdminVerificationTest extends AdminTestCase
{
    // =========================================================================
    // GET /api/v1/admin/verifications
    // =========================================================================

    /** @test */
    public function moderator_can_list_verification_queue(): void
    {
        Sanctum::actingAs($this->makeModerator());

        $this->makePendingVerification();
        $this->makePendingVerification();

        $this->getJson('/api/v1/admin/verifications')
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [['id', 'verification_status', 'user', 'created_at']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    /** @test */
    public function admin_can_list_verification_queue(): void
    {
        Sanctum::actingAs($this->makeAdmin());
        $this->makePendingVerification();

        $this->getJson('/api/v1/admin/verifications')
            ->assertStatus(200);
    }

    /** @test */
    public function list_can_be_filtered_by_status(): void
    {
        Sanctum::actingAs($this->makeModerator());

        $this->makePendingVerification();
        $this->makeVerifiedVerification();

        $this->getJson('/api/v1/admin/verifications?status=pending')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function regular_user_cannot_access_verification_queue(): void
    {
        Sanctum::actingAs($this->makeRegularUser());

        $this->getJson('/api/v1/admin/verifications')
            ->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/admin/verifications')
            ->assertStatus(401);
    }

    // =========================================================================
    // GET /api/v1/admin/verifications/{id}
    // =========================================================================

    /** @test */
    public function moderator_can_view_verification_detail(): void
    {
        $verification = $this->makePendingVerification();
        Sanctum::actingAs($this->makeModerator());

        $this->getJson("/api/v1/admin/verifications/$verification->id")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $verification->id)
            ->assertJsonStructure([
                'data' => [
                    'id', 'verification_status', 'full_name_on_card',
                    'date_of_birth', 'liveness_check_passed', 'user',
                ],
            ]);
    }

    /** @test */
    public function show_returns_404_for_unknown_id(): void
    {
        Sanctum::actingAs($this->makeModerator());

        $this->getJson('/api/v1/admin/verifications/00000000-0000-0000-0000-000000000001')
            ->assertStatus(404);
    }

    // =========================================================================
    // PATCH /api/v1/admin/verifications/{id}/claim
    // =========================================================================

    /** @test */
    public function moderator_can_claim_a_pending_verification(): void
    {
        $verification = $this->makePendingVerification();
        $mod          = $this->makeModerator();
        Sanctum::actingAs($mod);

        $this->patchJson("/api/v1/admin/verifications/$verification->id/claim")
            ->assertStatus(200)
            ->assertJsonPath('data.verification_status', IdentityVerificationStatus::UnderReview->value);

        $this->assertDatabaseHas('identity_verifications', [
            'id'                  => $verification->id,
            'verification_status' => 'under_review',
        ]);

        $this->assertDatabaseHas('admin_actions', [
            'admin_id'    => $mod->id,
            'action_type' => 'verification_claimed',
            'target_id'   => $verification->id,
        ]);
    }

    /** @test */
    public function moderator_can_claim_an_escalated_verification(): void
    {
        $verification = IdentityVerification::factory()->create([
            'verification_status' => 'escalated',
        ]);
        Sanctum::actingAs($this->makeModerator());

        $this->patchJson("/api/v1/admin/verifications/$verification->id/claim")
            ->assertStatus(200)
            ->assertJsonPath('data.verification_status', IdentityVerificationStatus::UnderReview->value);
    }

    /** @test */
    public function cannot_claim_a_verification_already_under_review(): void
    {
        $verification = IdentityVerification::factory()->create([
            'verification_status' => 'under_review',
        ]);
        Sanctum::actingAs($this->makeModerator());

        $this->patchJson("/api/v1/admin/verifications/$verification->id/claim")
            ->assertStatus(409);
    }

    /** @test */
    public function cannot_claim_a_verified_verification(): void
    {
        $verification = $this->makeVerifiedVerification();
        Sanctum::actingAs($this->makeModerator());

        $this->patchJson("/api/v1/admin/verifications/$verification->id/claim")
            ->assertStatus(409);
    }

    /** @test */
    public function regular_user_cannot_claim_a_verification(): void
    {
        $verification = $this->makePendingVerification();
        Sanctum::actingAs($this->makeRegularUser());

        $this->patchJson("/api/v1/admin/verifications/$verification->id/claim")
            ->assertStatus(403);
    }

    // =========================================================================
    // PATCH /api/v1/admin/verifications/{id}/escalate
    // =========================================================================

    /** @test */
    public function moderator_can_escalate_an_under_review_verification(): void
    {
        $mod          = $this->makeModerator();
        $verification = IdentityVerification::factory()->create([
            'verification_status' => 'under_review',
        ]);
        Sanctum::actingAs($mod);

        $this->patchJson("/api/v1/admin/verifications/$verification->id/escalate", [
            'notes' => 'Document appears altered — needs senior review.',
        ])->assertStatus(200)
            ->assertJsonPath('data.verification_status', IdentityVerificationStatus::Escalated->value);

        $this->assertDatabaseHas('admin_actions', [
            'admin_id'    => $mod->id,
            'action_type' => 'verification_escalated',
            'target_id'   => $verification->id,
        ]);
    }

    /** @test */
    public function escalate_works_without_notes(): void
    {
        $verification = IdentityVerification::factory()->create([
            'verification_status' => 'under_review',
        ]);
        Sanctum::actingAs($this->makeModerator());

        $this->patchJson("/api/v1/admin/verifications/$verification->id/escalate")
            ->assertStatus(200)
            ->assertJsonPath('data.verification_status', IdentityVerificationStatus::Escalated->value);
    }

    /** @test */
    public function cannot_escalate_a_pending_verification(): void
    {
        $verification = $this->makePendingVerification();
        Sanctum::actingAs($this->makeModerator());

        $this->patchJson("/api/v1/admin/verifications/$verification->id/escalate")
            ->assertStatus(409);
    }

    /** @test */
    public function cannot_escalate_an_already_escalated_verification(): void
    {
        $verification = IdentityVerification::factory()->create([
            'verification_status' => 'escalated',
        ]);
        Sanctum::actingAs($this->makeModerator());

        $this->patchJson("/api/v1/admin/verifications/$verification->id/escalate")
            ->assertStatus(409);
    }

    /** @test */
    public function regular_user_cannot_escalate_a_verification(): void
    {
        $verification = IdentityVerification::factory()->create([
            'verification_status' => 'under_review',
        ]);
        Sanctum::actingAs($this->makeRegularUser());

        $this->patchJson("/api/v1/admin/verifications/$verification->id/escalate")
            ->assertStatus(403);
    }

    // =========================================================================
    // POST /api/v1/admin/verifications/{id}/review
    // =========================================================================

    /** @test */
    public function moderator_can_approve_a_pending_verification(): void
    {
        $mod          = $this->makeModerator();
        $verification = $this->makePendingVerification();
        $user         = $verification->user;
        Sanctum::actingAs($mod);

        $this->postJson("/api/v1/admin/verifications/$verification->id/review", [
            'review_action'           => 'approved',
            'review_notes'            => 'Document looks authentic.',
            'automated_checks_passed' => true,
        ])->assertStatus(200)
            ->assertJsonPath('data.verification_status', IdentityVerificationStatus::Verified->value);

        $user->refresh();
        $this->assertTrue($user->identity_verified);
        $this->assertEquals(IdentityVerificationLevel::Advanced, $user->identity_verification_level);

        $this->assertDatabaseHas('verification_reviews', [
            'verification_id' => $verification->id,
            'reviewer_id'     => $mod->id,
            'review_action'   => 'approved',
        ]);

        $this->assertDatabaseHas('admin_actions', [
            'admin_id'    => $mod->id,
            'action_type' => 'verification_review',
            'target_id'   => $verification->id,
        ]);
    }

    /** @test */
    public function moderator_can_reject_a_pending_verification(): void
    {
        $mod          = $this->makeModerator();
        $verification = $this->makePendingVerification();
        $user         = $verification->user;
        Sanctum::actingAs($mod);

        $this->postJson("/api/v1/admin/verifications/$verification->id/review", [
            'review_action'             => 'rejected',
            'review_notes'              => 'Image is too blurry to read.',
            'rejection_reason_category' => 'unclear',
            'automated_checks_passed'   => false,
        ])->assertStatus(200)
            ->assertJsonPath('data.verification_status', IdentityVerificationStatus::Rejected->value);

        $user->refresh();
        $this->assertFalse($user->identity_verified);
    }

    /** @test */
    public function moderator_can_request_resubmission(): void
    {
        $verification = $this->makePendingVerification();
        Sanctum::actingAs($this->makeModerator());

        $this->postJson("/api/v1/admin/verifications/$verification->id/review", [
            'review_action' => 'request_resubmission',
            'review_notes'  => 'Please upload a clearer photo.',
        ])->assertStatus(200)
            ->assertJsonPath('data.verification_status', IdentityVerificationStatus::Pending->value);
    }

    /** @test */
    public function cannot_review_an_already_approved_verification(): void
    {
        $verification = $this->makeVerifiedVerification();
        Sanctum::actingAs($this->makeModerator());

        $this->postJson("/api/v1/admin/verifications/$verification->id/review", [
            'review_action' => 'approved',
        ])->assertStatus(409);
    }

    /** @test */
    public function cannot_review_an_already_rejected_verification(): void
    {
        $verification = $this->makeRejectedVerification();
        Sanctum::actingAs($this->makeModerator());

        $this->postJson("/api/v1/admin/verifications/$verification->id/review", [
            'review_action'             => 'rejected',
            'rejection_reason_category' => 'other',
        ])->assertStatus(409);
    }

    /** @test */
    public function rejection_requires_a_rejection_reason_category(): void
    {
        $verification = $this->makePendingVerification();
        Sanctum::actingAs($this->makeModerator());

        $this->postJson("/api/v1/admin/verifications/$verification->id/review", [
            'review_action' => 'rejected',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['rejection_reason_category']);
    }

    /** @test */
    public function review_with_invalid_action_fails_validation(): void
    {
        $verification = $this->makePendingVerification();
        Sanctum::actingAs($this->makeModerator());

        $this->postJson("/api/v1/admin/verifications/$verification->id/review", [
            'review_action' => 'made_up_action',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['review_action']);
    }

    /** @test */
    public function regular_user_cannot_review_verifications(): void
    {
        $verification = $this->makePendingVerification();
        Sanctum::actingAs($this->makeRegularUser());

        $this->postJson("/api/v1/admin/verifications/$verification->id/review", [
            'review_action' => 'approved',
        ])->assertStatus(403);
    }
}
