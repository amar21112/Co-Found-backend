<?php

namespace Tests\Feature\Chat;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\UserRestriction;
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

    private function makeConversation(User $creator, array $participants = [], array $overrides = []): Conversation
    {
        $conversation = Conversation::factory()->create(array_merge(
            ['created_by' => $creator->id, 'conversation_type' => 'group'],
            $overrides
        ));

        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id'         => $creator->id,
            'is_admin'        => true,
        ]);

        foreach ($participants as $participant) {
            ConversationParticipant::factory()->create([
                'conversation_id' => $conversation->id,
                'user_id'         => $participant->id,
                'is_admin'        => false,
            ]);
        }

        return $conversation;
    }

    private function makeDirect(User $userA, User $userB): Conversation
    {
        $conversation = Conversation::factory()->direct()->create([
            'created_by' => $userA->id,
        ]);

        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id'         => $userA->id,
            'is_admin'        => true,
        ]);
        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id'         => $userB->id,
            'is_admin'        => false,
        ]);

        return $conversation;
    }

    private function makeMessage(Conversation $conversation, User $sender, array $overrides = []): Message
    {
        return Message::factory()->create(array_merge([
            'conversation_id' => $conversation->id,
            'sender_id'       => $sender->id,
            'content'         => 'Test message content.',
        ], $overrides));
    }

    private function restrictUser(User $user, string $type): void
    {
        $mod = User::factory()->moderator()->create();
        UserRestriction::factory()->create([
            'user_id'          => $user->id,
            'restricted_by'    => $mod->id,
            'restriction_type' => $type,
            'is_active'        => true,
            'expires_at'       => now()->addDay(),
        ]);
    }

    // =========================================================================
    // GET /api/v1/conversations
    // =========================================================================

    /** @test */
    public function user_can_list_their_conversations(): void
    {
        $user  = $this->makeUser();
        $other = $this->makeUser();
        Sanctum::actingAs($user);

        $this->makeConversation($user, [$other]);
        $this->makeConversation($user, [$other]);

        // A conversation the user is NOT in — must not appear
        $this->makeConversation($other, [$this->makeUser()]);

        $this->getJson('/api/v1/conversations')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function conversations_can_be_filtered_by_type(): void
    {
        $user  = $this->makeUser();
        $other = $this->makeUser();
        Sanctum::actingAs($user);

        $this->makeConversation($user, [$other], ['conversation_type' => 'group']);
        $this->makeDirect($user, $other);

        $this->getJson('/api/v1/conversations?type=direct')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function unauthenticated_user_cannot_list_conversations(): void
    {
        $this->getJson('/api/v1/conversations')->assertStatus(401);
    }

    // =========================================================================
    // POST /api/v1/conversations
    // =========================================================================

    /** @test */
    public function user_can_create_a_direct_conversation(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        Sanctum::actingAs($userA);

        $this->postJson('/api/v1/conversations', [
            'conversation_type' => 'direct',
            'participant_ids'   => [$userB->id],
        ])->assertStatus(201)
            ->assertJsonPath('data.conversation_type', 'direct');

        $this->assertDatabaseHas('conversations', [
            'conversation_type' => 'direct',
            'created_by'        => $userA->id,
        ]);
    }

    /** @test */
    public function user_can_create_a_group_conversation(): void
    {
        $creator = $this->makeUser();
        $userB   = $this->makeUser();
        $userC   = $this->makeUser();
        Sanctum::actingAs($creator);

        $this->postJson('/api/v1/conversations', [
            'conversation_type' => 'group',
            'title'             => 'Team Chat',
            'participant_ids'   => [$userB->id, $userC->id],
        ])->assertStatus(201)
            ->assertJsonPath('data.title', 'Team Chat');
    }

    /** @test */
    public function cannot_create_duplicate_direct_conversation(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        Sanctum::actingAs($userA);

        $this->makeDirect($userA, $userB);

        $this->postJson('/api/v1/conversations', [
            'conversation_type' => 'direct',
            'participant_ids'   => [$userB->id],
        ])->assertStatus(409);
    }

    /** @test */
    public function group_conversation_requires_title(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        Sanctum::actingAs($userA);

        $this->postJson('/api/v1/conversations', [
            'conversation_type' => 'group',
            'participant_ids'   => [$userB->id],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function guest_cannot_create_conversation(): void
    {
        $other = $this->makeUser();
        Sanctum::actingAs(User::factory()->guest()->create());

        $this->postJson('/api/v1/conversations', [
            'conversation_type' => 'direct',
            'participant_ids'   => [$other->id],
        ])->assertStatus(403);
    }

    // =========================================================================
    // GET /api/v1/conversations/{id}
    // =========================================================================

    /** @test */
    public function participant_can_view_conversation(): void
    {
        $user         = $this->makeUser();
        $conversation = $this->makeConversation($user);
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/conversations/$conversation->id")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $conversation->id);
    }

    /** @test */
    public function non_participant_cannot_view_conversation(): void
    {
        $creator      = $this->makeUser();
        $conversation = $this->makeConversation($creator);
        Sanctum::actingAs($this->makeUser());

        $this->getJson("/api/v1/conversations/$conversation->id")
            ->assertStatus(403);
    }

    // =========================================================================
    // GET /api/v1/conversations/{id}/messages
    // =========================================================================

    /** @test */
    public function participant_can_list_messages(): void
    {
        $user         = $this->makeUser();
        $conversation = $this->makeConversation($user);
        Sanctum::actingAs($user);

        $this->makeMessage($conversation, $user);
        $this->makeMessage($conversation, $user);

        $this->getJson("/api/v1/conversations/$conversation->id/messages")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function non_participant_cannot_list_messages(): void
    {
        $creator      = $this->makeUser();
        $conversation = $this->makeConversation($creator);
        Sanctum::actingAs($this->makeUser());

        $this->getJson("/api/v1/conversations/$conversation->id/messages")
            ->assertStatus(403);
    }

    // =========================================================================
    // POST /api/v1/conversations/{id}/messages
    // =========================================================================

    /** @test */
    public function participant_can_send_a_message(): void
    {
        $user         = $this->makeUser();
        $conversation = $this->makeConversation($user);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/conversations/$conversation->id/messages", [
            'content' => 'Hello, world!',
        ])->assertStatus(201)
            ->assertJsonPath('data.content', 'Hello, world!');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'content'         => 'Hello, world!',
        ]);
    }

    /** @test */
    public function non_participant_cannot_send_message(): void
    {
        $creator      = $this->makeUser();
        $conversation = $this->makeConversation($creator);
        Sanctum::actingAs($this->makeUser());

        $this->postJson("/api/v1/conversations/$conversation->id/messages", [
            'content' => 'Hello!',
        ])->assertStatus(403);
    }

    /** @test */
    public function user_with_messaging_ban_cannot_send_message(): void
    {
        $user         = $this->makeUser();
        $conversation = $this->makeConversation($user);
        Sanctum::actingAs($user);

        $this->restrictUser($user, 'messaging_ban');

        $this->postJson("/api/v1/conversations/$conversation->id/messages", [
            'content' => 'I am banned.',
        ])->assertStatus(403);
    }

    /** @test */
    public function message_content_is_required(): void
    {
        $user         = $this->makeUser();
        $conversation = $this->makeConversation($user);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/conversations/$conversation->id/messages")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    // =========================================================================
    // PUT /api/v1/conversations/{id}/messages/{messageId}  (edit)
    // =========================================================================

    /** @test */
    public function sender_can_edit_their_message(): void
    {
        $user         = $this->makeUser();
        $conversation = $this->makeConversation($user);
        $message      = $this->makeMessage($conversation, $user);
        Sanctum::actingAs($user);

        $this->putJson(
            "/api/v1/conversations/$conversation->id/messages/$message->id",
            ['content' => 'Edited content.']
        )->assertStatus(200)
            ->assertJsonPath('data.content', 'Edited content.');
    }

    /** @test */
    public function user_cannot_edit_another_users_message(): void
    {
        $creator      = $this->makeUser();
        $other        = $this->makeUser();
        $conversation = $this->makeConversation($creator, [$other]);
        $message      = $this->makeMessage($conversation, $creator);
        Sanctum::actingAs($other);

        $this->putJson(
            "/api/v1/conversations/$conversation->id/messages/$message->id",
            ['content' => 'Hacked.']
        )->assertStatus(403);
    }

    // =========================================================================
    // DELETE /api/v1/conversations/{id}/messages/{messageId}
    // =========================================================================

    /** @test */
    public function sender_can_delete_their_message(): void
    {
        $user         = $this->makeUser();
        $conversation = $this->makeConversation($user);
        $message      = $this->makeMessage($conversation, $user);
        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/conversations/$conversation->id/messages/$message->id")
            ->assertStatus(200);

        $this->assertSoftDeleted('messages', ['id' => $message->id]);
    }

    /** @test */
    public function admin_can_delete_any_message_in_conversation(): void
    {
        $admin        = $this->makeUser();
        $other        = $this->makeUser();
        $conversation = $this->makeConversation($admin, [$other]);
        $message      = $this->makeMessage($conversation, $other);
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/v1/conversations/$conversation->id/messages/$message->id")
            ->assertStatus(200);
    }

    /** @test */
    public function non_admin_cannot_delete_others_message(): void
    {
        $creator      = $this->makeUser();
        $member       = $this->makeUser();
        $conversation = $this->makeConversation($creator, [$member]);
        $message      = $this->makeMessage($conversation, $creator);
        Sanctum::actingAs($member);

        $this->deleteJson("/api/v1/conversations/$conversation->id/messages/$message->id")
            ->assertStatus(403);
    }

    // =========================================================================
    // POST /api/v1/conversations/{id}/messages/{messageId}/reactions
    // =========================================================================

    /** @test */
    public function participant_can_add_reaction_to_message(): void
    {
        $user         = $this->makeUser();
        $conversation = $this->makeConversation($user);
        $message      = $this->makeMessage($conversation, $user);
        Sanctum::actingAs($user);

        $this->postJson(
            "/api/v1/conversations/$conversation->id/messages/$message->id/reactions",
            ['reaction' => '👍']
        )->assertStatus(200);

        $this->assertDatabaseHas('message_reactions', [
            'message_id' => $message->id,
            'user_id'    => $user->id,
            'reaction'   => '👍',
        ]);
    }

    // =========================================================================
    // POST /api/v1/conversations/{id}/leave
    // =========================================================================

    /** @test */
    public function participant_can_leave_conversation(): void
    {
        $creator      = $this->makeUser();
        $member       = $this->makeUser();
        $conversation = $this->makeConversation($creator, [$member]);
        Sanctum::actingAs($member);

        $this->postJson("/api/v1/conversations/$conversation->id/leave")
            ->assertStatus(200);

        // Record still exists but marked as left
        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id'         => $member->id,
        ]);

        // But left_at is set
        $participant = ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $member->id)
            ->first();

        $this->assertNotNull($participant->left_at);
    }

    // =========================================================================
    // TODO: Remove Firebase sync from backend
    // =========================================================================
    //
    // Firebase RTDB handles:
    //   - Real-time messaging
    //   - Message persistence (NoSQL)
    //   - Typing indicators
    //   - Read receipts
    //   - Reactions
    //   - Offline support
    //
    // Backend (Laravel/MySQL) should NOT:
    //   - Sync to Firebase (duplication)
    //   - Store chat messages (Firebase already persists them)
    //   - Act as a real-time broker
    // =========================================================================

    /** @test */
    public function backend_should_not_duplicate_firebase_persistence(): void
    {
        $this->markTestSkipped(
            'TODO: Remove all Firebase sync logic from backend. ' .
            'Chat messages, reactions, and read receipts are stored in Firebase RTDB only. ' .
            'Backend retains only conversation metadata and user auth.'
        );
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
