<?php

namespace Tests\Feature\Auth;

use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PasswordResetTest extends AuthTestCase
{
    // =========================================================================
    // Forgot Password
    // =========================================================================

    /** @test */
    public function forgot_password_returns_200_for_known_email(): void
    {
        Log::spy();
        $this->makeActiveUser(['email' => 'active@example.com']);

        $this->postJson('/api/auth/password/forgot', ['email' => 'active@example.com'])
            ->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }

    /** @test */
    public function forgot_password_returns_200_for_unknown_email_anti_enumeration(): void
    {
        $this->postJson('/api/auth/password/forgot', ['email' => 'ghost@example.com'])
            ->assertStatus(200);
    }

    /** @test */
    public function forgot_password_creates_exactly_one_reset_token(): void
    {
        Log::spy();
        $this->makeActiveUser(['email' => 'active@example.com']);

        $this->postJson('/api/auth/password/forgot', ['email' => 'active@example.com']);
        $this->postJson('/api/auth/password/forgot', ['email' => 'active@example.com']);

        // Second call must invalidate the first — only one valid token exists
        $this->assertDatabaseCount('password_resets', 1);
    }

    /** @test */
    public function forgot_password_fails_with_invalid_email_format(): void
    {
        $this->postJson('/api/auth/password/forgot', ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // =========================================================================
    // Reset Password
    // =========================================================================

    /** @test */
    public function reset_password_updates_password_and_revokes_all_tokens(): void
    {
        $user  = $this->makeActiveUser();
        $token = $this->createValidResetToken($user);
        $user->createToken('device_1');
        $user->createToken('device_2');
        $this->assertDatabaseCount('personal_access_tokens', 2);

        $this->postJson('/api/auth/password/reset', [
            'token'                 => $token,
            'password'              => 'NewSecret456',
            'password_confirmation' => 'NewSecret456',
        ])->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertTrue(Hash::check('NewSecret456', $user->fresh()->password));
    }

    /** @test */
    public function reset_password_marks_token_as_used(): void
    {
        $user    = $this->makeActiveUser();
        $token   = $this->createValidResetToken($user);
        $resetId = PasswordReset::where('reset_token', $token)->value('id');

        $this->postJson('/api/auth/password/reset', [
            'token'                 => $token,
            'password'              => 'NewSecret456',
            'password_confirmation' => 'NewSecret456',
        ])->assertStatus(200);

        // Used token is preserved as an audit record (deleteOtherTokensForUser keeps it)
        $reset = PasswordReset::find($resetId);
        $this->assertNotNull($reset, 'Used token must be preserved as audit record.');
        $this->assertNotNull($reset->used_at);
    }

    /** @test */
    public function reset_password_rejects_a_used_token_replay_attack(): void
    {
        $user  = $this->makeActiveUser();
        $token = $this->createValidResetToken($user);

        $this->postJson('/api/auth/password/reset', [
            'token'                 => $token,
            'password'              => 'NewSecret456',
            'password_confirmation' => 'NewSecret456',
        ])->assertStatus(200);

        $this->postJson('/api/auth/password/reset', [
            'token'                 => $token,
            'password'              => 'AnotherPass789',
            'password_confirmation' => 'AnotherPass789',
        ])->assertStatus(400);
    }

    /** @test */
    public function reset_password_fails_with_invalid_token(): void
    {
        $this->postJson('/api/auth/password/reset', [
            'token'                 => 'totally-fake-token',
            'password'              => 'NewSecret456',
            'password_confirmation' => 'NewSecret456',
        ])->assertStatus(400);
    }

    /** @test */
    public function reset_password_fails_with_expired_token(): void
    {
        $user  = $this->makeActiveUser();
        $token = Str::random(64);
        PasswordReset::create([
            'user_id'     => $user->id,
            'reset_token' => $token,
            'expires_at'  => now()->subMinute(),
        ]);

        $this->postJson('/api/auth/password/reset', [
            'token'                 => $token,
            'password'              => 'NewSecret456',
            'password_confirmation' => 'NewSecret456',
        ])->assertStatus(400);
    }

    /** @test */
    public function reset_password_fails_with_weak_new_password(): void
    {
        $user  = $this->makeActiveUser();
        $token = $this->createValidResetToken($user);

        $this->postJson('/api/auth/password/reset', [
            'token'                 => $token,
            'password'              => 'weakonly',
            'password_confirmation' => 'weakonly',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }
}
