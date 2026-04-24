<?php

namespace Tests\Feature\Auth;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

class EmailVerificationTest extends AuthTestCase
{
    // =========================================================================
    // Verify
    // =========================================================================

    /** @test */
    public function email_verification_activates_pending_account(): void
    {
        $token = Str::random(64);
        $user  = $this->makePendingUser([
            'email_verification_token'   => $token,
            'email_verification_expires' => now()->addHours(24),
        ]);

        $this->getJson("/api/v1/auth/email/verify/$token")
            ->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.email_verified', true)
            ->assertJsonPath('data.account_status', AccountStatus::Active->value);

        $user->refresh();
        $this->assertTrue($user->email_verified);
        $this->assertEquals(AccountStatus::Active, $user->account_status);
        $this->assertNull($user->email_verification_token);
        $this->assertNull($user->email_verification_expires);
    }

    /** @test */
    public function email_verification_fails_with_completely_invalid_token(): void
    {
        $this->getJson('/api/v1/auth/email/verify/not-a-real-token')
            ->assertStatus(400);
    }

    /** @test */
    public function email_verification_fails_with_expired_token(): void
    {
        $token = Str::random(64);
        $this->makePendingUser([
            'email_verification_token'   => $token,
            'email_verification_expires' => now()->subHour(),
        ]);

        $this->getJson("/api/v1/auth/email/verify/$token")
            ->assertStatus(400);
    }

    /** @test */
    public function email_verification_fails_when_already_verified(): void
    {
        $token = Str::random(64);
        User::factory()->create([
            'email_verified'             => true,
            'account_status'             => AccountStatus::Active->value,
            'email_verification_token'   => $token,
            'email_verification_expires' => now()->addHours(24),
        ]);

        $this->getJson("/api/v1/auth/email/verify/$token")
            ->assertStatus(409);
    }

    // =========================================================================
    // Resend
    // =========================================================================

    /** @test */
    public function resend_generates_new_token_and_invalidates_old(): void
    {
        Log::spy();
        $oldToken = Str::random(64);
        $user     = $this->makePendingUser([
            'email_verification_token'   => $oldToken,
            'email_verification_expires' => now()->addHour(),
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/email/resend')
            ->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $user->refresh();
        $this->assertNotNull($user->email_verification_token);
        $this->assertNotEquals($oldToken, $user->email_verification_token);
        $this->assertTrue($user->email_verification_expires->isFuture());
    }

    /** @test */
    public function resend_fails_if_email_already_verified(): void
    {
        $user = $this->makeActiveUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/email/resend')->assertStatus(409);
    }

    /** @test */
    public function resend_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/email/resend')->assertStatus(401);
    }

    /** @test */
    public function resend_is_blocked_for_guest_users(): void
    {
        Sanctum::actingAs($this->makeGuestUser());

        $this->postJson('/api/v1/auth/email/resend')
            ->assertStatus(403)
            ->assertJsonPath('code', 'GUEST_ACCESS_RESTRICTED');
    }
}
