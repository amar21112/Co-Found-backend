<?php

namespace Tests\Feature\Chat;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Covers chat endpoints NOT exercised by ChatTest:
 *  PATCH  /notifications/{id}/read                   mark single notification read
 *  POST   /notifications/read-all                    mark all notifications read
 */
class ChatExtendedTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['email_verified' => true], $overrides));
    }

    private function makeNotification(User $user, array $overrides = []): Notification
    {
        return Notification::factory()->create(array_merge([
            'user_id' => $user->id,
            'read'    => false,
        ], $overrides));
    }

    // =========================================================================
    // PATCH /notifications/{id}/read  — mark single notification read
    // =========================================================================

    /** @test */
    public function user_can_mark_a_notification_as_read(): void
    {
        $user         = $this->makeUser();
        $notification = $this->makeNotification($user);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/notifications/$notification->id/read")
            ->assertStatus(200)
            ->assertJsonPath('data.read', true);

        $this->assertDatabaseHas('notifications', [
            'id'   => $notification->id,
            'read' => true,
        ]);
    }

    /** @test */
    public function user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner        = $this->makeUser();
        $other        = $this->makeUser();
        $notification = $this->makeNotification($owner);
        Sanctum::actingAs($other);

        $this->patchJson("/api/v1/notifications/$notification->id/read")
            ->assertStatus(404);
    }

    // =========================================================================
    // POST /notifications/read-all  — mark all notifications read
    // =========================================================================

    /** @test */
    public function user_can_mark_all_notifications_as_read(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->makeNotification($user);
        $this->makeNotification($user);
        // Another user's notification — must stay unread
        $this->makeNotification($this->makeUser());

        $this->postJson('/api/v1/notifications/read-all')
            ->assertStatus(200);

        $this->assertDatabaseCount('notifications', 3);
        $this->assertEquals(
            2,
            Notification::where('user_id', $user->id)->where('read', true)->count()
        );
    }

    /** @test */
    public function unauthenticated_user_cannot_mark_notifications_as_read(): void
    {
        $this->postJson('/api/v1/notifications/read-all')
            ->assertStatus(401);
    }
}
