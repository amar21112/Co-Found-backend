<?php

namespace Tests\Feature\Admin;

use App\Jobs\GenerateMatchesJob;
use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use ReflectionProperty;

/**
 * Tests the ML job dispatch behaviour inside the admin verification review flow.
 *
 * Extends AdminTestCase to reuse all existing helpers (makeModerator, etc.)
 * and focuses specifically on the new behaviour added:
 *   - GenerateMatchesJob is dispatched when a verification is approved
 *   - It is NOT dispatched for rejections or resubmission requests
 *   - The job carries the correct user ID
 *   - The review itself still succeeds (ML dispatch is fire-and-forget)
 */
class AdminVerificationApprovalTest extends AdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    // =========================================================================
    // Approval → dispatches job
    // =========================================================================

    /** @test */
    public function approving_a_verification_dispatches_generate_matches_job(): void
    {
        $mod          = $this->makeModerator();
        $verification = $this->makePendingVerification();
        Sanctum::actingAs($mod);

        $this->postJson("/api/v1/admin/verifications/$verification->id/review", [
            'review_action'           => 'approved',
            'review_notes'            => 'All good.',
            'automated_checks_passed' => true,
        ])->assertStatus(200);

        Queue::assertPushed(GenerateMatchesJob::class, function ($job) use ($verification) {
            $ref = new ReflectionProperty($job, 'userId');
            return $ref->getValue($job) === $verification->user_id;
        });
    }

    /** @test */
    public function job_is_dispatched_with_the_correct_user_id(): void
    {
        $mod          = $this->makeModerator();
        $user         = User::factory()->create(['role' => 'guest', 'account_status' => 'active']);
        $verification = IdentityVerification::factory()->create([
            'user_id'             => $user->id,
            'verification_status' => 'pending',
        ]);
        Sanctum::actingAs($mod);

        $this->postJson("/api/v1/admin/verifications/$verification->id/review", [
            'review_action'           => 'approved',
            'automated_checks_passed' => true,
        ])->assertStatus(200);

        Queue::assertPushed(GenerateMatchesJob::class, function ($job) use ($user) {
            $ref = new ReflectionProperty($job, 'userId');
            return $ref->getValue($job) === $user->id;
        });
    }

    /** @test */
    public function exactly_one_job_is_dispatched_per_approval(): void
    {
        $mod          = $this->makeModerator();
        $verification = $this->makePendingVerification();
        Sanctum::actingAs($mod);

        $this->postJson("/api/v1/admin/verifications/$verification->id/review", [
            'review_action'           => 'approved',
            'automated_checks_passed' => true,
        ])->assertStatus(200);

        Queue::assertPushed(GenerateMatchesJob::class, 1);
    }

    // =========================================================================
    // Non-approval actions → no job
    // =========================================================================

    /** @test */
    public function rejecting_a_verification_does_not_dispatch_generate_matches_job(): void
    {
        $verification = $this->makePendingVerification();
        Sanctum::actingAs($this->makeModerator());

        $this->postJson("/api/v1/admin/verifications/$verification->id/review", [
            'review_action'             => 'rejected',
            'review_notes'              => 'Document unclear.',
            'rejection_reason_category' => 'unclear',
            'automated_checks_passed'   => false,
        ])->assertStatus(200);

        Queue::assertNotPushed(GenerateMatchesJob::class);
    }

    /** @test */
    public function requesting_resubmission_does_not_dispatch_generate_matches_job(): void
    {
        $verification = $this->makePendingVerification();
        Sanctum::actingAs($this->makeModerator());

        $this->postJson("/api/v1/admin/verifications/$verification->id/review", [
            'review_action' => 'request_resubmission',
            'review_notes'  => 'Please upload a clearer photo.',
        ])->assertStatus(200);

        Queue::assertNotPushed(GenerateMatchesJob::class);
    }

    // =========================================================================
    // Review outcome is not affected by job dispatch
    // =========================================================================

    /** @test */
    public function approval_review_outcome_is_correct_regardless_of_job_dispatch(): void
    {
        $mod          = $this->makeModerator();
        $verification = $this->makePendingVerification();
        $user         = $verification->user;
        Sanctum::actingAs($mod);

        $this->postJson("/api/v1/admin/verifications/$verification->id/review", [
            'review_action'           => 'approved',
            'automated_checks_passed' => true,
        ])->assertStatus(200)
            ->assertJsonPath('data.verification_status', 'verified');

        $user->refresh();
        $this->assertTrue($user->identity_verified);
        $this->assertDatabaseHas('verification_reviews', [
            'verification_id' => $verification->id,
            'review_action'   => 'approved',
        ]);
    }

    /** @test */
    public function failed_review_validation_does_not_dispatch_job(): void
    {
        $verification = $this->makePendingVerification();
        Sanctum::actingAs($this->makeModerator());

        // Missing required review_action
        $this->postJson("/api/v1/admin/verifications/$verification->id/review")
            ->assertStatus(422);

        Queue::assertNotPushed(GenerateMatchesJob::class);
    }

    /** @test */
    public function reviewing_already_approved_verification_does_not_dispatch_job(): void
    {
        $verification = $this->makeVerifiedVerification();
        Sanctum::actingAs($this->makeModerator());

        $this->postJson("/api/v1/admin/verifications/$verification->id/review", [
            'review_action' => 'approved',
        ])->assertStatus(409);

        Queue::assertNotPushed(GenerateMatchesJob::class);
    }
}
