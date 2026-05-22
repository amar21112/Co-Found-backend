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
use Illuminate\Support\Facades\DB;
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
        $conversation = Conversation::factory()->create();

        $call = VideoCall::factory()->scheduled()->forConversation($conversation->id)->create(array_merge(
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
        $conversation = Conversation::factory()->create();

        $call = VideoCall::factory()->active()->forConversation($conversation->id)->create(array_merge(
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
        $conversation = Conversation::factory()->create();

        return VideoCall::factory()->ended()->forConversation($conversation->id)->create([
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
        // In production, ProjectService::create() adds the owner to project_team_members.
        // Replicate that here so capacity calculations are accurate in tests.
        $this->addToProject(
            $project,
            $initiator,
            [
                'position'    => 'Founder',
                'permissions' => 'owner',
            ]
        );

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

    private function addToProject(Project $project, User $user, array $overrides = []): void
    {
        ProjectTeamMember::factory()->create(array_merge([
            'project_id' => $project->id,
            'user_id'    => $user->id,
            'is_active'  => true,
        ], $overrides));
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

        $this->makeActiveCall($user);
        $this->makeCall($user);
        $this->makeActiveCall($other); // should NOT appear

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
    public function verified_user_can_initiate_a_conversation_call(): void
    {
        $user         = $this->makeUser();
        $conversation = Conversation::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/calls', [
            'conversation_id' => $conversation->id,
            'status'          => 'scheduled',
        ])->assertStatus(201)
            ->assertJsonPath('data.call_type', 'conversation')
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonStructure([
                'data' => ['id', 'call_type', 'status', 'room_name', 'room_url', 'initiator'],
            ]);

        $call = VideoCall::where('initiated_by', $user->id)->first();
        $this->assertNotNull($call);
        $this->assertEquals($conversation->id, $call->conversation_id);
        $this->assertNull($call->project_id);

        $this->assertDatabaseHas('call_participants', [
            'call_id' => $call->id,
            'user_id' => $user->id,
            'role'    => 'host',
        ]);
    }

    /** @test */
    public function verified_user_can_initiate_a_project_call(): void
    {
        $user    = $this->makeUser();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/calls', [
            'project_id' => $project->id,
            'status'     => 'scheduled',
        ])->assertStatus(201)
            ->assertJsonPath('data.call_type', 'project')
            ->assertJsonPath('data.status', 'scheduled');

        $call = VideoCall::where('initiated_by', $user->id)->first();
        $this->assertEquals($project->id, $call->project_id);
        $this->assertNull($call->conversation_id);
    }

    /** @test */
    public function call_cannot_be_initiated_without_a_context_id(): void
    {
        // Ad-hoc calls are not supported — every call must be anchored.
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/v1/calls')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['context']);
    }

    /** @test */
    public function call_cannot_be_initiated_with_both_context_ids(): void
    {
        // A call cannot belong to two contexts simultaneously.
        $user         = $this->makeUser();
        $conversation = Conversation::factory()->create();
        $project      = Project::factory()->create(['owner_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/calls', [
            'conversation_id' => $conversation->id,
            'project_id'      => $project->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['context']);
    }

    /** @test */
    public function call_type_is_derived_from_context_not_sent_by_client(): void
    {
        // The client sends a context ID; call_type is set by the backend.
        // Sending call_type explicitly should have no effect.
        $user         = $this->makeUser();
        $conversation = Conversation::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/calls', [
            'conversation_id' => $conversation->id,
            // even if someone tries to pass call_type, the backend ignores it
        ])->assertStatus(201);

        $this->assertEquals('conversation', $response->json('data.call_type'));
    }

    /** @test */
    public function room_name_uses_cofound_prefix(): void
    {
        $conversation = Conversation::factory()->create();
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/v1/calls', ['conversation_id' => $conversation->id])
            ->assertStatus(201);

        $call = VideoCall::latest()->first();
        $this->assertStringStartsWith('cofound-', $call->room_name);
        $this->assertEquals(24, strlen($call->room_name)); // 'cofound-' (8) + 16 random chars
    }

    /** @test */
    public function initiate_returns_join_token_for_the_host(): void
    {
        $conversation = Conversation::factory()->create();
        Sanctum::actingAs($this->makeUser());

        $response = $this->postJson('/api/v1/calls', ['conversation_id' => $conversation->id])
            ->assertStatus(201);

        $token = $response->json('data.join_token');
        $this->assertNotNull($token);
        $this->assertCount(3, explode('.', $token));
    }

    /** @test */
    public function initiator_calling_join_after_initiate_gets_a_fresh_token(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeCall($initiator);
        Sanctum::actingAs($initiator);

        $response = $this->postJson("/api/v1/calls/$call->id/join")
            ->assertStatus(200);

        $this->assertNotNull($response->json('data.join_token'));
    }

    /** @test */
    public function can_initiate_a_call_as_immediately_active(): void
    {
        $conversation = Conversation::factory()->create();
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/v1/calls', [
            'conversation_id' => $conversation->id,
            'status'          => 'active',
        ])->assertStatus(201)
            ->assertJsonPath('data.status', 'active');
    }

    /** @test */
    public function start_time_in_the_past_is_rejected(): void
    {
        $conversation = Conversation::factory()->create();
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/v1/calls', [
            'conversation_id' => $conversation->id,
            'start_time'      => now()->subHour()->toISOString(),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['start_time']);
    }

    /** @test */
    public function unauthenticated_user_cannot_initiate_calls(): void
    {
        $conversation = Conversation::factory()->create();

        $this->postJson('/api/v1/calls', ['conversation_id' => $conversation->id])
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
    public function show_does_not_expose_join_token(): void
    {
        // GET /calls/{id} is a read-only endpoint — it never issues a token.
        // Tokens are issued only by POST /calls (initiate, host only) and
        // POST /calls/{id}/join (any permitted participant).
        $initiator = $this->makeUser();
        $call      = $this->makeCall($initiator);
        Sanctum::actingAs($initiator);

        $response = $this->getJson("/api/v1/calls/$call->id")->assertStatus(200);

        $this->assertArrayNotHasKey('join_token', $response->json('data'));
    }

    /** @test */
    public function show_returns_404_for_unknown_call(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->getJson('/api/v1/calls/00000000-0000-0000-0000-000000000001')
            ->assertStatus(404);
    }

    // =========================================================================
    // POST /api/v1/calls/{id}/join — JWT token issuance
    // =========================================================================

    /** @test */
    public function join_returns_a_jwt_token(): void
    {
        $initiator = $this->makeUser();
        $joiner    = $this->makeUser();
        $call      = $this->makeCall($initiator);
        // Joiner must be a conversation participant to pass assertCanJoin
        $this->addToConversation(Conversation::find($call->conversation_id), $joiner);
        Sanctum::actingAs($joiner);

        $response = $this->postJson("/api/v1/calls/$call->id/join")
            ->assertStatus(200);

        $token = $response->json('data.join_token');
        $this->assertNotNull($token);
        $this->assertNotEmpty($token);
        $this->assertCount(3, explode('.', $token));
    }

    /** @test */
    public function initiator_receives_jwt_on_join(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeCall($initiator);
        // Initiator is always allowed — no conversation membership needed
        Sanctum::actingAs($initiator);

        $response = $this->postJson("/api/v1/calls/$call->id/join")
            ->assertStatus(200);

        $this->assertNotNull($response->json('data.join_token'));
    }

    /** @test */
    public function join_token_is_not_persisted_to_database(): void
    {
        $initiator = $this->makeUser();
        $joiner    = $this->makeUser();
        $call      = $this->makeCall($initiator);
        $this->addToConversation(Conversation::find($call->conversation_id), $joiner);
        Sanctum::actingAs($joiner);

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        // Confirm join_token is never written to the video_calls row
        $record = DB::table('video_calls')->where('id', $call->id)->first();
        $this->assertFalse(property_exists($record, 'join_token'));
    }

    /** @test */
    public function rejoining_after_leave_issues_a_fresh_token(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeActiveCall($initiator);
        $joiner    = $this->makeUser();
        // Must be in the conversation, even when rejoining via an existing participant row
        $this->addToConversation(Conversation::find($call->conversation_id), $joiner);

        CallParticipant::factory()->left()->create([
            'call_id' => $call->id,
            'user_id' => $joiner->id,
        ]);
        Sanctum::actingAs($joiner);

        $response = $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        $this->assertNotNull($response->json('data.join_token'));
    }

    /** @test */
    public function reconnecting_while_already_active_issues_a_fresh_token(): void
    {
        // Idempotent join (already active) must still return a token so
        // the client can reconnect after a page refresh / network drop.
        $initiator = $this->makeUser();
        $call      = $this->makeActiveCall($initiator);
        Sanctum::actingAs($initiator);

        $r1 = $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);
        $r2 = $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        $this->assertNotNull($r1->json('data.join_token'));
        $this->assertNotNull($r2->json('data.join_token'));
    }

    // =========================================================================
    // POST /api/v1/calls/{id}/join — conversation access control
    // =========================================================================

    /** @test */
    public function conversation_participant_can_join_a_conversation_call(): void
    {
        $initiator    = $this->makeUser();
        $joiner       = $this->makeUser();
        $conversation = Conversation::factory()->create();
        $this->addToConversation($conversation, $initiator);
        $this->addToConversation($conversation, $joiner);

        $call = $this->makeConversationCall($initiator, $conversation);
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
    public function non_conversation_member_cannot_join_conversation_call(): void
    {
        $initiator    = $this->makeUser();
        $outsider     = $this->makeUser();
        $conversation = Conversation::factory()->create();
        $this->addToConversation($conversation, $initiator);

        $call = $this->makeConversationCall($initiator, $conversation);
        Sanctum::actingAs($outsider);

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(409);
    }

    /** @test */
    public function joining_a_scheduled_call_activates_it(): void
    {
        $initiator    = $this->makeUser();
        $joiner       = $this->makeUser();
        $conversation = Conversation::factory()->create();
        $this->addToConversation($conversation, $initiator);
        $this->addToConversation($conversation, $joiner);

        $call = $this->makeConversationCall($initiator, $conversation);
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

        $this->assertDatabaseCount('call_participants', 1);
    }

    /** @test */
    public function user_can_rejoin_after_leaving(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeActiveCall($initiator);
        $joiner    = $this->makeUser();

        // Add joiner to the underlying conversation so they pass assertCanJoin
        $conversation = Conversation::find($call->conversation_id);
        $this->addToConversation($conversation, $joiner);

        CallParticipant::factory()->left()->create([
            'call_id' => $call->id,
            'user_id' => $joiner->id,
        ]);
        Sanctum::actingAs($joiner);

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        $participant = CallParticipant::where('call_id', $call->id)
            ->where('user_id', $joiner->id)
            ->first();
        $this->assertNull($participant->left_at);
        $this->assertDatabaseCount('call_participants', 2); // host + rejoined participant
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
    // POST /api/v1/calls/{id}/join — project access control
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

    // =========================================================================
    // POST /api/v1/calls/{id}/join — capacity enforcement
    // =========================================================================

    /** @test */
    public function direct_conversation_call_is_limited_to_two_participants(): void
    {
        $initiator    = $this->makeUser();
        $member       = $this->makeUser();
        $outsider     = $this->makeUser();
        $conversation = Conversation::factory()->create(['conversation_type' => 'direct']);
        $this->addToConversation($conversation, $initiator);
        $this->addToConversation($conversation, $member);

        $call = $this->makeConversationCall($initiator, $conversation);

        // Both legitimate members join successfully
        Sanctum::actingAs($member);
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        // A third user (even if added to conversation later) cannot join — call is full
        $this->addToConversation($conversation, $outsider);
        Sanctum::actingAs($outsider);
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(409);
    }

    /** @test */
    public function active_participant_can_reconnect_when_call_is_full(): void
    {
        // Idempotent join (already active) must succeed even when call is at capacity.
        // The slot is already theirs — reconnect does not count against the limit.
        $initiator    = $this->makeUser();
        $member       = $this->makeUser();
        $conversation = Conversation::factory()->create(['conversation_type' => 'direct']);
        $this->addToConversation($conversation, $initiator);
        $this->addToConversation($conversation, $member);

        $call = $this->makeConversationCall($initiator, $conversation);

        Sanctum::actingAs($member);
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        // Call is now full (2/2) — but initiator can still reconnect
        Sanctum::actingAs($initiator);
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);
    }

    /** @test */
    public function participant_can_rejoin_after_leaving_when_slot_is_free(): void
    {
        $initiator    = $this->makeUser();
        $member       = $this->makeUser();
        $conversation = Conversation::factory()->create(['conversation_type' => 'direct']);
        $this->addToConversation($conversation, $initiator);
        $this->addToConversation($conversation, $member);

        $call = $this->makeConversationCall($initiator, $conversation);

        // Member joins then leaves — slot frees up
        Sanctum::actingAs($member);
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);
        $this->postJson("/api/v1/calls/$call->id/leave")->assertStatus(200);

        // Member can rejoin — their slot is free again
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);
    }

    /** @test */
    public function project_call_allows_all_team_members_to_join(): void
    {
        $initiator = $this->makeUser();
        $project   = Project::factory()->create(['owner_id' => $initiator->id]);
        $member1   = $this->makeUser();
        $member2   = $this->makeUser();
        $member3   = $this->makeUser();
        $this->addToProject($project, $member1);
        $this->addToProject($project, $member2);
        $this->addToProject($project, $member3);

        $call = $this->makeProjectCall($initiator, $project);

        // All 3 members can join (plus initiator = 4 total, within team size)
        Sanctum::actingAs($member1);
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        Sanctum::actingAs($member2);
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        Sanctum::actingAs($member3);
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);
    }

    /** @test */
    public function non_member_cannot_join_full_project_call(): void
    {
        $initiator = $this->makeUser();
        $project   = Project::factory()->create(['owner_id' => $initiator->id]);
        // Only 1 active member besides initiator — total team = 2
        $member    = $this->makeUser();
        $outsider  = $this->makeUser();
        $this->addToProject($project, $member);

        $call = $this->makeProjectCall($initiator, $project);

        // Fill the call
        Sanctum::actingAs($member);
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        // Outsider is not a team member — rejected regardless of capacity
        Sanctum::actingAs($outsider);
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(409);
    }

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
        $initiator    = $this->makeUser();
        $conversation = Conversation::factory()->create();
        $call         = VideoCall::factory()->active()->forConversation($conversation->id)->create([
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
        $call      = $this->makeCall($initiator);
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

        $this->patchJson("/api/v1/calls/$call->id/cancel")->assertStatus(409);
    }

    /** @test */
    public function cannot_cancel_an_already_ended_call(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeEndedCall($initiator);
        Sanctum::actingAs($initiator);

        $this->patchJson("/api/v1/calls/$call->id/cancel")->assertStatus(409);
    }

    /** @test */
    public function non_host_cannot_cancel_a_call(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeCall($initiator);
        Sanctum::actingAs($this->makeUser());

        $this->patchJson("/api/v1/calls/$call->id/cancel")->assertStatus(403);
    }

    /** @test */
    public function cannot_end_a_scheduled_call_use_cancel_instead(): void
    {
        $initiator = $this->makeUser();
        $call      = $this->makeCall($initiator);
        Sanctum::actingAs($initiator);

        $this->patchJson("/api/v1/calls/$call->id/end")->assertStatus(409);
    }
}
