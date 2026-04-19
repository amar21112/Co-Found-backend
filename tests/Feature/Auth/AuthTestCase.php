<?php

namespace Tests\Feature\Auth;

use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Base class for all auth feature tests.
 *
 * Provides shared factory helpers and common payload builders so
 * individual test classes stay focused on behaviour, not setup boilerplate.
 */
abstract class AuthTestCase extends TestCase
{
    use RefreshDatabase;

    // ── User factories ────────────────────────────────────────────────────────

    protected function makeActiveUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'password' => Hash::make('Secret123'),
        ], $overrides));
        // factory default: role=regular_user, account_status=active, email_verified=true
    }

    protected function makePendingUser(array $overrides = []): User
    {
        return User::factory()->unverified()->create(array_merge([
            'password' => Hash::make('Secret123'),
        ], $overrides));
    }

    protected function makeGuestUser(): User
    {
        return User::factory()->guest()->create();
    }

    // ── Payload builders ──────────────────────────────────────────────────────

    protected function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'email'                 => 'john@example.com',
            'username'              => 'johndoe',
            'password'              => 'Secret123',
            'password_confirmation' => 'Secret123',
            'full_name'             => 'John Doe',
        ], $overrides);
    }

    // ── Common fixtures ───────────────────────────────────────────────────────

    protected function createValidResetToken(User $user): string
    {
        $token = Str::random(64);

        PasswordReset::create([
            'user_id'     => $user->id,
            'reset_token' => $token,
            'expires_at'  => now()->addHour(),
        ]);

        return $token;
    }
}
