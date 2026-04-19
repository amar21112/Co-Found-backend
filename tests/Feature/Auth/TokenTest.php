<?php

namespace Tests\Feature\Auth;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use Laravel\Sanctum\Sanctum;

class TokenTest extends AuthTestCase
{
    // =========================================================================
    // Logout
    // =========================================================================

    /** @test */
    public function logout_revokes_current_token_only(): void
    {
        $user = $this->makeActiveUser();
        $user->createToken('other_device');
        Sanctum::actingAs($user);

        $this->postJson('/api/auth/logout')
            ->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    /** @test */
    public function logout_requires_authentication(): void
    {
        $this->postJson('/api/auth/logout')->assertStatus(401);
    }

    // =========================================================================
    // Me
    // =========================================================================

    /** @test */
    public function me_returns_authenticated_user_profile(): void
    {
        $user = $this->makeActiveUser(['email' => 'active@example.com']);
        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')
            ->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.email', 'active@example.com');
    }

    /** @test */
    public function me_is_accessible_by_guest_token(): void
    {
        $guest = $this->makeGuestUser();
        Sanctum::actingAs($guest);

        $this->getJson('/api/auth/me')
            ->assertStatus(200)
            ->assertJsonPath('data.role', UserRole::Guest->value);
    }

    /** @test */
    public function me_requires_authentication(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }

    // =========================================================================
    // Refresh
    // =========================================================================

    /** @test */
    public function refresh_issues_new_token_and_revokes_old(): void
    {
        $user      = $this->makeActiveUser();
        $plaintext = $user->createToken('api_token')->plainTextToken;

        $this->withToken($plaintext)
            ->postJson('/api/auth/refresh')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['access_token', 'token_type', 'user']]);

        // Old token revoked, new one created — net count stays at 1
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    /** @test */
    public function refresh_works_for_guest_users(): void
    {
        $guest = $this->makeGuestUser();
        Sanctum::actingAs($guest);

        $this->postJson('/api/auth/refresh')
            ->assertStatus(200)
            ->assertJsonPath('data.user.role', UserRole::Guest->value);
    }

    /** @test */
    public function refresh_requires_authentication(): void
    {
        $this->postJson('/api/auth/refresh')->assertStatus(401);
    }

    // =========================================================================
    // Guest session
    // =========================================================================

    /** @test */
    public function guest_endpoint_creates_ephemeral_user_with_guest_role(): void
    {
        $this->postJson('/api/auth/guest')
            ->assertStatus(201)
            ->assertJsonStructure([
                'status', 'message',
                'data' => ['access_token', 'token_type', 'user'],
            ])
            ->assertJsonPath('data.user.role', UserRole::Guest->value)
            ->assertJsonPath('data.user.account_status', AccountStatus::Active->value);

        $this->assertDatabaseHas('users', ['role' => UserRole::Guest->value]);
    }

    /** @test */
    public function each_guest_call_creates_a_distinct_account(): void
    {
        $this->postJson('/api/auth/guest')->assertStatus(201);
        $this->postJson('/api/auth/guest')->assertStatus(201);

        $this->assertDatabaseCount('users', 2);
    }

    /** @test */
    public function guest_cannot_access_no_guest_protected_routes(): void
    {
        $guest = $this->makeGuestUser();
        Sanctum::actingAs($guest);

        $this->postJson('/api/auth/email/resend')
            ->assertStatus(403)
            ->assertJsonPath('code', 'GUEST_ACCESS_RESTRICTED');
    }

    /** @test */
    public function guest_is_blocked_by_no_guest_middleware_on_write_routes(): void
    {
        $guest = $this->makeGuestUser();
        Sanctum::actingAs($guest);

        $this->putJson('/api/v1/profile', ['full_name' => 'Hack'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'GUEST_ACCESS_RESTRICTED');
    }
}
