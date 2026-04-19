<?php

namespace Tests\Feature\Auth;

use Laravel\Sanctum\Sanctum;

class MiddlewareTest extends AuthTestCase
{
    /** @test */
    public function pending_user_is_blocked_by_verified_middleware_on_write_routes(): void
    {
        $user = $this->makePendingUser();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', ['full_name' => 'New Name'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'EMAIL_NOT_VERIFIED');
    }

    /** @test */
    public function active_verified_user_can_access_verified_routes(): void
    {
        $user = $this->makeActiveUser();
        Sanctum::actingAs($user);

        // A valid PUT /profile should not be rejected by auth middleware.
        // It may return 200 or 422 (validation) but must not return 401/403.
        $response = $this->putJson('/api/v1/profile', ['full_name' => 'Valid Name']);
        $this->assertNotEquals(401, $response->status());
        $this->assertNotEquals(403, $response->status());
    }

    /** @test */
    public function unauthenticated_user_is_rejected_on_protected_routes(): void
    {
        $this->putJson('/api/v1/profile', ['full_name' => 'Name'])
            ->assertStatus(401);
    }
}
