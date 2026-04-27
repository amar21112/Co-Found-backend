<?php

namespace Tests\Feature\Call;

use App\Models\CallParticipant;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Project;
use App\Models\ProjectTeamMember;
use App\Models\User;
use App\Models\VideoCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VideoCallTest extends TestCase
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

    private function makeCall(User $initiator, array $overrides = []): VideoCall
    {
        $call = VideoCall::factory()->scheduled()->adhoc()->create(array_merge(
            ['initiated_by' => $initiator->id],
            $overrides
        ));

        CallParticipant::factory()->host()->active()->create([
            'call_id' => $call->id,
            'user_id' => $initiator->id,
        ]);

        return $call;
    }

    private function makeActiveCall(User $initiator, array $overrides = []): VideoCall
    {
        $call = VideoCall::factory()->active()->adhoc()->create(array_merge(
            ['initiated_by' => $initiator->id],
            $overrides
        ));

        CallParticipant::factory()->host()->active()->create([
            'call_id' => $call->id,
            'user_id' => $initiator->id,
        ]);

        return $call;
    }

    private function makeEndedCall(User $initiator): VideoCall
    {
        return VideoCall::factory()->ended()->create([
            'initiated_by' => $initiator->id,
        ]);
    }

    private function joinCall(VideoCall $call, User $user): CallParticipant
    {
        return CallParticipant::factory()->active()->create([
            'call_id' => $call->id,
            'user_id' => $user->id,
        ]);
    }

    private function makeProjectCall(User $initiator, Project $project): VideoCall
    {
        $call = VideoCall::factory()->scheduled()->forProject($project->id)->create([
            'initiated_by' => $initiator->id,
        ]);

        CallParticipant::factory()->host()->active()->create([
            'call_id' => $call->id,
            'user_id' => $initiator->id,
        ]);

        return $call;
    }

    private function makeConversationCall(User $initiator, Conversation $conversation): VideoCall
    {
        $call = VideoCall::factory()->scheduled()->forConversation($conversation->id)->create([
            'initiated_by' => $initiator->id,
        ]);

        CallParticipant::factory()->host()->active()->create([
            'call_id' => $call->id,
            'user_id' => $initiator->id,
        ]);

        return $call;
    }

    private function addToProject(Project $project, User $user): void
    {
        ProjectTeamMember::factory()->create([
            'project_id' => $project->id,
            'user_id'    => $user->id,
            'is_active'  => true,
        ]);
    }

    private function addToConversation(Conversation $conversation, User $user): void
    {
        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id'         => $user->id,
            'left_at'         => null,
        ]);
    }

    // =========================================================================
    // GET /api/v1/calls
    // =========================================================================

    /** @test */
    public function user_can_list_their_own_calls(): void
    {
        $user  = $this->makeUser();
        $other = $this->makeUser();
        Sanctum::actingAs($user);

        // Calls initiated by the user
        $this->makeActiveCall($user);
        $this->makeCall($user);

        // Call by another user — should NOT appear
        $this->makeActiveCall($other);

        $this->getJson('/api/v1/calls')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'call_type', 'status', 'initiator']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    /** @test */
    public function call_list_can_be_filtered_by_status(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->makeActiveCall($user);
        $this->makeCall($user); // scheduled

        $this->getJson('/api/v1/calls?status=active')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function participated_calls_appear_in_user_list(): void
    {
        $initiator = $this->makeUser();
        $joiner    = $this->makeUser();
        $call      = $this->makeActiveCall($initiator);
        $this->joinCall($call, $joiner);
        Sanctum::actingAs($joiner);

        $this->getJson('/api/v1/calls')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // =========================================================================
    // POST /api/v1/calls
    // =========================================================================

    /** @test */
    public function verified_user_can_initiate_a_direct_call(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/calls', [
            'call_type' => 'direct',
            'status'    => 'scheduled',
        ])->assertStatus(201)
            ->assertJsonPath('data.call_type', 'direct')
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonStructure([
                'data' => ['id', 'call_type', 'status', 'room_name', 'room_url', 'initiator'],
            ]);

        $call = VideoCall::where('initiated_by', $user->id)->first();
        $this->assertNotNull($call);

        // Initiator auto-added as host
        $this->assertDatabaseHas('call_participants', [
            'call_id' => $call->id,
            'user_id' => $user->id,
            'role'    => 'host',
        ]);
    }

    /** @test */
    public function room_url_is_returned_to_the_initiator(): void
    {
        Sanctum::actingAs($this->makeUser());

        $response = $this->postJson('/api/v1/calls', ['call_type' => 'direct'])
            ->assertStatus(201);

        $this->assertNotNull($response->json('data.room_url'));
        $this->assertStringContainsString('meet.jit.si', $response->json('data.room_url'));
    }

    /** @test */
    public function can_initiate_a_call_as_immediately_active(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/v1/calls', ['call_type' => 'direct', 'status' => 'active'])
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'active');
    }

    /** @test */
    public function call_type_is_required(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/v1/calls')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['call_type']);
    }

    /** @test */
    public function invalid_call_type_is_rejected(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/v1/calls', ['call_type' => 'invalid'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['call_type']);
    }

    /** @test */
    public function start_time_in_the_past_is_rejected(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/v1/calls', [
            'call_type'  => 'direct',
            'start_time' => now()->subHour()->toISOString(),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['start_time']);
    }

    /** @test */
    public function unauthenticated_user_cannot_initiate_calls(): void
    {
        $this->postJson('/api/v1/calls', ['call_type' => 'direct'])
            ->assertStatus(401);
    }

    // =========================================================================
    // GET /api/v1/calls/{id}
    // =========================================================================

    /** @test */
    public function user_can_view_call_detail(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeCall($initiator);
        Sanctum::actingAs($initiator);

        $this->getJson("/api/v1/calls/$call->id")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $call->id)
            ->assertJsonStructure([
                'data' => ['id', 'call_type', 'status', 'room_name', 'initiator', 'participants'],
            ]);
    }

    /** @test */
    public function room_url_is_hidden_from_non_participants(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeCall($initiator);
        Sanctum::actingAs($this->makeUser());

        $response = $this->getJson("/api/v1/calls/$call->id")
            ->assertStatus(200);

        $this->assertNull($response->json('data.room_url'));
    }

    /** @test */
    public function room_url_is_visible_to_participants(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeCall($initiator);
        Sanctum::actingAs($initiator);

        $response = $this->getJson("/api/v1/calls/$call->id")->assertStatus(200);

        $this->assertNotNull($response->json('data.room_url'));
    }

    /** @test */
    public function show_returns_404_for_unknown_call(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->getJson('/api/v1/calls/00000000-0000-0000-0000-000000000001')
            ->assertStatus(404);
    }

    // =========================================================================
    // POST /api/v1/calls/{id}/join — ad-hoc
    // =========================================================================

    /** @test */
    public function user_can_join_an_adhoc_scheduled_call(): void
    {
        $initiator = $this->makeUser();
        $joiner    = $this->makeUser();
        $call      = $this->makeCall($initiator);
        Sanctum::actingAs($joiner);

        $this->postJson("/api/v1/calls/$call->id/join")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('call_participants', [
            'call_id' => $call->id,
            'user_id' => $joiner->id,
            'role'    => 'participant',
        ]);
    }

    /** @test */
    public function joining_a_scheduled_call_activates_it(): void
    {
        $initiator = $this->makeUser();
        $joiner    = $this->makeUser();
        $call      = $this->makeCall($initiator);
        Sanctum::actingAs($joiner);

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        $this->assertDatabaseHas('video_calls', ['id' => $call->id, 'status' => 'active']);
    }

    /** @test */
    public function joining_is_idempotent_when_already_active_in_call(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeActiveCall($initiator);
        Sanctum::actingAs($initiator);

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        // Still only one participant record for the initiator
        $this->assertDatabaseCount('call_participants', 1);
    }

    /** @test */
    public function user_can_rejoin_after_leaving(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeActiveCall($initiator);
        $joiner    = $this->makeUser();

        // Create participant row that has already left
        CallParticipant::factory()->left()->create([
            'call_id' => $call->id,
            'user_id' => $joiner->id,
        ]);
        Sanctum::actingAs($joiner);

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        // Should still be only one row (updated, not duplicated)
        $count = CallParticipant::where('call_id', $call->id)
            ->where('user_id', $joiner->id)
            ->count();
        $this->assertEquals(1, $count);

        // Row should now be active again
        $participant = CallParticipant::where('call_id', $call->id)
            ->where('user_id', $joiner->id)
            ->first();
        $this->assertNull($participant->left_at);
    }

    /** @test */
    public function cannot_join_an_ended_call(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeEndedCall($initiator);
        Sanctum::actingAs($this->makeUser());

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(409);
    }

    // =========================================================================
    // POST /api/v1/calls/{id}/join — access control
    // =========================================================================

    /** @test */
    public function project_team_member_can_join_project_call(): void
    {
        $initiator = $this->makeUser();
        $project   = Project::factory()->create(['owner_id' => $initiator->id]);
        $member    = $this->makeUser();
        $this->addToProject($project, $member);

        $call = $this->makeProjectCall($initiator, $project);
        Sanctum::actingAs($member);

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);
    }

    /** @test */
    public function non_project_member_cannot_join_project_call(): void
    {
        $initiator = $this->makeUser();
        $project   = Project::factory()->create(['owner_id' => $initiator->id]);
        $outsider  = $this->makeUser();

        $call = $this->makeProjectCall($initiator, $project);
        Sanctum::actingAs($outsider);

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(409);
    }

    /** @test */
    public function conversation_participant_can_join_conversation_call(): void
    {
        $initiator    = $this->makeUser();
        $conversation = Conversation::factory()->create();
        $participant  = $this->makeUser();
        $this->addToConversation($conversation, $initiator);
        $this->addToConversation($conversation, $participant);

        $call = $this->makeConversationCall($initiator, $conversation);
        Sanctum::actingAs($participant);

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);
    }

    /** @test */
    public function non_conversation_member_cannot_join_conversation_call(): void
    {
        $initiator    = $this->makeUser();
        $conversation = Conversation::factory()->create();
        $outsider     = $this->makeUser();
        $this->addToConversation($conversation, $initiator);

        $call = $this->makeConversationCall($initiator, $conversation);
        Sanctum::actingAs($outsider);

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(409);
    }

    // =========================================================================
    // POST /api/v1/calls/{id}/leave
    // =========================================================================

    /** @test */
    public function participant_can_leave_a_call(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeActiveCall($initiator);
        $joiner    = $this->makeUser();
        $this->joinCall($call, $joiner);
        Sanctum::actingAs($joiner);

        $this->postJson("/api/v1/calls/$call->id/leave")->assertStatus(200);

        $participant = CallParticipant::where('call_id', $call->id)
            ->where('user_id', $joiner->id)->first();
        $this->assertNotNull($participant->left_at);
        $this->assertNotNull($participant->duration_seconds);
    }

    /** @test */
    public function call_ends_automatically_when_last_participant_leaves(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeActiveCall($initiator);
        Sanctum::actingAs($initiator);

        $this->postJson("/api/v1/calls/$call->id/leave")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'ended');

        $this->assertDatabaseHas('video_calls', ['id' => $call->id, 'status' => 'ended']);
    }

    /** @test */
    public function call_continues_when_host_leaves_but_others_remain(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeActiveCall($initiator);
        $this->joinCall($call, $this->makeUser());
        Sanctum::actingAs($initiator);

        $response = $this->postJson("/api/v1/calls/$call->id/leave")
            ->assertStatus(200);

        $this->assertNotEquals('ended', $response->json('data.status'));
    }

    /** @test */
    public function non_participant_cannot_leave_a_call(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeActiveCall($initiator);
        Sanctum::actingAs($this->makeUser());

        $this->postJson("/api/v1/calls/$call->id/leave")->assertStatus(403);
    }

    /** @test */
    public function cannot_leave_an_already_ended_call(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeEndedCall($initiator);
        Sanctum::actingAs($initiator);

        $this->postJson("/api/v1/calls/$call->id/leave")->assertStatus(409);
    }

    // =========================================================================
    // PATCH /api/v1/calls/{id}/end
    // =========================================================================

    /** @test */
    public function host_can_end_a_call(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeActiveCall($initiator);
        $this->joinCall($call, $this->makeUser());
        Sanctum::actingAs($initiator);

        $this->patchJson("/api/v1/calls/$call->id/end")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'ended');

        $this->assertDatabaseHas('video_calls', ['id' => $call->id, 'status' => 'ended']);
    }

    /** @test */
    public function ending_a_call_marks_all_active_participants_as_left(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeActiveCall($initiator);
        $this->joinCall($call, $this->makeUser());
        Sanctum::actingAs($initiator);

        $this->patchJson("/api/v1/calls/$call->id/end")->assertStatus(200);

        $activeCount = CallParticipant::where('call_id', $call->id)
            ->whereNull('left_at')->count();
        $this->assertEquals(0, $activeCount);
    }

    /** @test */
    public function ended_call_records_duration_seconds(): void
    {
        $initiator = $this->makeUser();
        $call      = VideoCall::factory()->active()->adhoc()->create([
            'initiated_by' => $initiator->id,
            'start_time'   => now()->subMinutes(5),
        ]);
        CallParticipant::factory()->host()->active()->create([
            'call_id' => $call->id, 'user_id' => $initiator->id,
        ]);
        Sanctum::actingAs($initiator);

        $response = $this->patchJson("/api/v1/calls/$call->id/end")->assertStatus(200);

        $this->assertGreaterThan(0, $response->json('data.duration_seconds'));
    }

    /** @test */
    public function non_host_cannot_end_a_call(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeActiveCall($initiator);
        $other     = $this->makeUser();
        $this->joinCall($call, $other);
        Sanctum::actingAs($other);

        $this->patchJson("/api/v1/calls/$call->id/end")->assertStatus(403);
    }

    /** @test */
    public function cannot_end_an_already_ended_call(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeEndedCall($initiator);
        Sanctum::actingAs($initiator);

        $this->patchJson("/api/v1/calls/$call->id/end")->assertStatus(409);
    }

    // =========================================================================
    // PATCH /api/v1/calls/{id}/cancel
    // =========================================================================

    /** @test */
    public function host_can_cancel_a_scheduled_call(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeCall($initiator); // scheduled
        Sanctum::actingAs($initiator);

        $this->patchJson("/api/v1/calls/$call->id/cancel")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('video_calls', [
            'id'     => $call->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function cannot_cancel_an_active_call(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeActiveCall($initiator);
        Sanctum::actingAs($initiator);

        // Active calls must use end(), not cancel()
        $this->patchJson("/api/v1/calls/$call->id/cancel")
            ->assertStatus(409);
    }

    /** @test */
    public function cannot_cancel_an_already_ended_call(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeEndedCall($initiator);
        Sanctum::actingAs($initiator);

        $this->patchJson("/api/v1/calls/$call->id/cancel")
            ->assertStatus(409);
    }

    /** @test */
    public function non_host_cannot_cancel_a_call(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeCall($initiator);
        Sanctum::actingAs($this->makeUser());

        $this->patchJson("/api/v1/calls/$call->id/cancel")
            ->assertStatus(403);
    }

    /** @test */
    public function cannot_end_a_scheduled_call_use_cancel_instead(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeCall($initiator); // scheduled
        Sanctum::actingAs($initiator);

        // end() on a scheduled call should return 409 — use cancel() instead
        $this->patchJson("/api/v1/calls/$call->id/end")
            ->assertStatus(409);
    }
}
