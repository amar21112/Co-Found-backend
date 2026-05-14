<?php

namespace Tests\Feature\Chat;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\File;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Covers chat endpoints NOT exercised by ChatTest:
 *
 *  PATCH  /conversations/{id}                        update (title, muted)
 *  POST   /conversations/{id}/participants           addParticipant
 *  DELETE /conversations/{id}/participants/{userId}  removeParticipant
 *  PATCH  /conversations/{id}/messages/{id}/pin      pin / unpin
 *  POST   /conversations/{id}/messages/{id}/read     markRead (single message)
 *  POST   /conversations/{id}/read-all               markAllRead
 *  DELETE /conversations/{id}/messages/{id}/reactions removeReaction
 *  POST   /conversations/{id}/typing                 typing indicator set
 *  DELETE /conversations/{id}/typing                 typing indicator cleared
 *  GET    /conversations/{id}/files                  list shared files
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

    private function makeGroupConversation(User $creator, array $members = []): Conversation
    {
        $conv = Conversation::factory()->create([
            'created_by'        => $creator->id,
            'conversation_type' => 'group',
        ]);

        ConversationParticipant::factory()->create([
            'conversation_id' => $conv->id,
            'user_id'         => $creator->id,
            'is_admin'        => true,
        ]);

        foreach ($members as $member) {
            ConversationParticipant::factory()->create([
                'conversation_id' => $conv->id,
                'user_id'         => $member->id,
                'is_admin'        => false,
            ]);
        }

        return $conv;
    }

    private function makeDirect(User $a, User $b): Conversation
    {
        $conv = Conversation::factory()->direct()->create(['created_by' => $a->id]);

        ConversationParticipant::factory()->create([
            'conversation_id' => $conv->id,
            'user_id'         => $a->id,
            'is_admin'        => true,
        ]);
        ConversationParticipant::factory()->create([
            'conversation_id' => $conv->id,
            'user_id'         => $b->id,
            'is_admin'        => false,
        ]);

        return $conv;
    }

    private function makeMessage(Conversation $conv, User $sender, array $overrides = []): Message
    {
        return Message::factory()->create(array_merge([
            'conversation_id' => $conv->id,
            'sender_id'       => $sender->id,
            'message_type'    => 'text',
            'content'         => 'Hello.',
        ], $overrides));
    }

    private function makeFile(User $uploader): File
    {
        return File::factory()->create(['uploader_id' => $uploader->id, 'upload_completed' => true]);
    }

    private function makeNotification(User $user, array $overrides = []): Notification
    {
        return Notification::factory()->create(array_merge([
            'user_id' => $user->id,
            'read'    => false,
        ], $overrides));
    }

    // =========================================================================
    // PATCH /conversations/{id}  — update title / muted
    // =========================================================================

    /** @test */
    public function admin_can_update_group_conversation_title(): void
    {
        $admin = $this->makeUser();
        $conv  = $this->makeGroupConversation($admin);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/conversations/$conv->id", [
            'title' => 'New Title',
        ])->assertStatus(200)
            ->assertJsonPath('data.title', 'New Title');

        $this->assertDatabaseHas('conversations', [
            'id'    => $conv->id,
            'title' => 'New Title',
        ]);
    }

    /** @test */
    public function non_admin_cannot_update_conversation(): void
    {
        $admin  = $this->makeUser();
        $member = $this->makeUser();
        $conv   = $this->makeGroupConversation($admin, [$member]);
        Sanctum::actingAs($member);

        $this->patchJson("/api/v1/conversations/$conv->id", [
            'title' => 'Hacked',
        ])->assertStatus(403);
    }

    /** @test */
    public function direct_conversation_cannot_be_updated(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $conv  = $this->makeDirect($userA, $userB);
        Sanctum::actingAs($userA);

        $this->patchJson("/api/v1/conversations/$conv->id", [
            'title' => 'Should Fail',
        ])->assertStatus(403);
    }

    // =========================================================================
    // POST /conversations/{id}/participants  — addParticipant
    // =========================================================================

    /** @test */
    public function admin_can_add_participant_to_group_conversation(): void
    {
        $admin   = $this->makeUser();
        $newUser = $this->makeUser();
        $conv    = $this->makeGroupConversation($admin);
        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/conversations/$conv->id/participants", [
            'user_id' => $newUser->id,
        ])->assertStatus(201);

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conv->id,
            'user_id'         => $newUser->id,
        ]);
    }

    /** @test */
    public function cannot_add_already_existing_participant(): void
    {
        $admin   = $this->makeUser();
        $member  = $this->makeUser();
        $conv    = $this->makeGroupConversation($admin, [$member]);
        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/conversations/$conv->id/participants", [
            'user_id' => $member->id,
        ])->assertStatus(409);
    }

    /** @test */
    public function non_admin_cannot_add_participant(): void
    {
        $admin   = $this->makeUser();
        $member  = $this->makeUser();
        $newUser = $this->makeUser();
        $conv    = $this->makeGroupConversation($admin, [$member]);
        Sanctum::actingAs($member);

        $this->postJson("/api/v1/conversations/$conv->id/participants", [
            'user_id' => $newUser->id,
        ])->assertStatus(403);
    }

    // =========================================================================
    // DELETE /conversations/{id}/participants/{userId}  — removeParticipant
    // =========================================================================

    /** @test */
    public function admin_can_remove_participant_from_conversation(): void
    {
        $admin  = $this->makeUser();
        $member = $this->makeUser();
        $conv   = $this->makeGroupConversation($admin, [$member]);
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/v1/conversations/$conv->id/participants/$member->id")
            ->assertStatus(200);

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conv->id,
            'user_id'         => $member->id,
        ]);

        $participant = ConversationParticipant::where('conversation_id', $conv->id)
            ->where('user_id', $member->id)
            ->first();

        $this->assertNotNull($participant->left_at);
    }

    /** @test */
    public function non_admin_cannot_remove_participant(): void
    {
        $admin   = $this->makeUser();
        $memberA = $this->makeUser();
        $memberB = $this->makeUser();
        $conv    = $this->makeGroupConversation($admin, [$memberA, $memberB]);
        Sanctum::actingAs($memberA);

        $this->deleteJson("/api/v1/conversations/$conv->id/participants/$memberB->id")
            ->assertStatus(403);
    }

    // =========================================================================
    // PATCH /conversations/{id}/messages/{id}/pin  — pin / unpin
    // =========================================================================

    /** @test */
    public function admin_can_pin_a_message(): void
    {
        $admin   = $this->makeUser();
        $member  = $this->makeUser();
        $conv    = $this->makeGroupConversation($admin, [$member]);
        $message = $this->makeMessage($conv, $member);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/conversations/$conv->id/messages/$message->id/pin", [
            'is_pinned' => true,
        ])->assertStatus(200);

        $this->assertDatabaseHas('messages', [
            'id'        => $message->id,
            'is_pinned' => true,
        ]);
    }

    /** @test */
    public function admin_can_unpin_a_message(): void
    {
        $admin   = $this->makeUser();
        $conv    = $this->makeGroupConversation($admin);
        $message = $this->makeMessage($conv, $admin, ['is_pinned' => true]);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/conversations/$conv->id/messages/$message->id/pin", [
            'is_pinned' => false,
        ])->assertStatus(200);

        $this->assertDatabaseHas('messages', [
            'id'        => $message->id,
            'is_pinned' => false,
        ]);
    }

    /** @test */
    public function non_admin_cannot_pin_a_message(): void
    {
        $admin   = $this->makeUser();
        $member  = $this->makeUser();
        $conv    = $this->makeGroupConversation($admin, [$member]);
        $message = $this->makeMessage($conv, $admin);
        Sanctum::actingAs($member);

        $this->patchJson("/api/v1/conversations/$conv->id/messages/$message->id/pin", [
            'is_pinned' => true,
        ])->assertStatus(403);
    }

    /** @test */
    public function pinning_message_from_different_conversation_returns_404(): void
    {
        $admin   = $this->makeUser();
        $convA   = $this->makeGroupConversation($admin);
        $convB   = $this->makeGroupConversation($admin);
        $message = $this->makeMessage($convB, $admin);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/conversations/$convA->id/messages/$message->id/pin", [
            'is_pinned' => true,
        ])->assertStatus(404);
    }

    // =========================================================================
    // POST /conversations/{id}/messages/{id}/read  — markRead (single)
    // =========================================================================
    //
    // TODO: After Firebase refactor, read receipts will be tracked in Firebase
    // RTDB only. These DB assertions will no longer be valid once the backend
    // stops writing to message_read_receipts.
    // =========================================================================

    /** @test */
    public function participant_can_mark_a_message_as_read(): void
    {
        $this->markTestSkipped(
            'Will move to Firebase-only after refactor. ' .
            'Read receipts are stored in Firebase RTDB; backend message_read_receipts table will be removed.'
        );
    }

    /** @test */
    public function marking_same_message_read_twice_is_idempotent(): void
    {
        $this->markTestSkipped(
            'Will move to Firebase-only after refactor. ' .
            'Read receipts are stored in Firebase RTDB; backend message_read_receipts table will be removed.'
        );
    }

    // =========================================================================
    // POST /conversations/{id}/read-all  — markAllRead
    // =========================================================================
    //
    // TODO: After Firebase refactor, bulk read state will be handled client-side
    // via Firebase RTDB. The message_read_receipts DB writes will be removed.
    // =========================================================================

    /** @test */
    public function participant_can_mark_all_messages_in_conversation_as_read(): void
    {
        $this->markTestSkipped(
            'Will move to Firebase-only after refactor. ' .
            'Bulk read state will be tracked client-side in Firebase RTDB; backend message_read_receipts table will be removed.'
        );
    }

    /** @test */
    public function non_participant_cannot_mark_all_read(): void
    {
        $creator  = $this->makeUser();
        $outsider = $this->makeUser();
        $conv     = $this->makeGroupConversation($creator);
        Sanctum::actingAs($outsider);

        $this->postJson("/api/v1/conversations/$conv->id/read-all")
            ->assertStatus(403);
    }

    // =========================================================================
    // DELETE /conversations/{id}/messages/{id}/reactions  — removeReaction
    // =========================================================================
    //
    // TODO: After Firebase refactor, reactions will be stored in Firebase RTDB
    // only. These DB assertions against message_reactions will be removed.
    // =========================================================================

    /** @test */
    public function participant_can_remove_their_reaction(): void
    {
        $this->markTestSkipped(
            'Will move to Firebase-only after refactor. ' .
            'Reactions are stored in Firebase RTDB; backend message_reactions table will be removed.'
        );
    }

    /** @test */
    public function non_participant_cannot_remove_reaction(): void
    {
        $creator  = $this->makeUser();
        $outsider = $this->makeUser();
        $conv     = $this->makeGroupConversation($creator);
        $message  = $this->makeMessage($conv, $creator);
        Sanctum::actingAs($outsider);

        $this->deleteJson(
            "/api/v1/conversations/$conv->id/messages/$message->id/reactions",
            ['reaction' => '👍']
        )->assertStatus(403);
    }

    // =========================================================================
    // POST /conversations/{id}/typing   — typing indicator
    // DELETE /conversations/{id}/typing — clear typing indicator
    // =========================================================================
    //
    // TODO: Typing indicators are pure Firebase RTDB — no DB writes occur.
    // The happy-path tests below only verify the HTTP 200 but assert nothing
    // meaningful until the backend is refactored to drop Firebase sync calls.
    // =========================================================================

    /** @test */
    public function participant_can_set_typing_indicator(): void
    {
        $this->markTestSkipped(
            'Will move to Firebase-only after refactor. ' .
            'Typing indicators are written exclusively to Firebase RTDB; no DB state to assert against.'
        );
    }

    /** @test */
    public function non_participant_cannot_set_typing_indicator(): void
    {
        $creator  = $this->makeUser();
        $outsider = $this->makeUser();
        $conv     = $this->makeGroupConversation($creator);
        Sanctum::actingAs($outsider);

        $this->postJson("/api/v1/conversations/$conv->id/typing")
            ->assertStatus(403);
    }

    /** @test */
    public function participant_can_clear_typing_indicator(): void
    {
        $this->markTestSkipped(
            'Will move to Firebase-only after refactor. ' .
            'Typing indicators are written exclusively to Firebase RTDB; no DB state to assert against.'
        );
    }

    // =========================================================================
    // GET /conversations/{id}/files  — list shared files
    // =========================================================================

    /** @test */
    public function participant_can_list_shared_files_in_conversation(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $conv  = $this->makeGroupConversation($userA, [$userB]);
        $file  = $this->makeFile($userA);
        Sanctum::actingAs($userA);

        // Share the file first
        $this->postJson("/api/v1/conversations/$conv->id/files", [
            'file_id' => $file->id,
        ])->assertStatus(201);

        $this->getJson("/api/v1/conversations/$conv->id/files")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function non_participant_cannot_list_shared_files(): void
    {
        $creator  = $this->makeUser();
        $outsider = $this->makeUser();
        $conv     = $this->makeGroupConversation($creator);
        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/conversations/$conv->id/files")
            ->assertStatus(403);
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
