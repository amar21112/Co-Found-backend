<?php

namespace Tests\Feature\Chat;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatTest extends TestCase
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

    // =========================================================================
    // GET /api/v1/notifications
    // =========================================================================

    /** @test */
    public function user_can_list_their_notifications(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        Notification::factory()->count(3)->create(['user_id' => $user->id]);

        $this->getJson('/api/v1/notifications')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function user_only_sees_their_own_notifications(): void
    {
        $user  = $this->makeUser();
        $other = $this->makeUser();
        Sanctum::actingAs($user);

        Notification::factory()->unread()->count(2)->create([
            'user_id' => $user->id,
        ]);

        Notification::factory()->count(3)->create([
            'user_id' => $other->id
        ]);

        $this->getJson('/api/v1/notifications')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('unread_count', 2);
    }

    // =========================================================================
    // GET /api/v1/notifications/preferences
    // =========================================================================

    /** @test */
    public function user_can_view_notification_preferences(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        NotificationPreference::factory()->create(['user_id' => $user->id]);

        $this->getJson('/api/v1/notifications/preferences')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['platform_notifications', 'email_notifications', 'push_notifications'],
            ]);
    }

    /** @test */
    public function user_can_update_notification_preferences(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        NotificationPreference::factory()->create([
            'user_id'              => $user->id,
            'push_notifications'   => true,
            'email_notifications'  => true,
        ]);

        $this->putJson('/api/v1/notifications/preferences', [
            'push_notifications'  => false,
            'email_notifications' => false,
        ])->assertStatus(200);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id'             => $user->id,
            'push_notifications'  => false,
            'email_notifications' => false,
        ]);
    }
}
