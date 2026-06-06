<?php

namespace Tests\Feature\Call;

use App\Firebase\FirebaseService;
use App\Models\CallParticipant;
use App\Models\Project;
use App\Models\ProjectTeamMember;
use App\Models\User;
use App\Models\VideoCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class VideoCallTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────────────
    // Firebase mock
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Mock FirebaseService for the test suite.
     *
     * Covers every method VideoCallService and JitsiReservationService call:
     *   - exists()                      → initiate(): verify conversation exists
     *   - isConversationParticipant()   → assertCanJoin() + verifyParticipant()
     *   - isPrivateConversation()       → resolveMaxParticipants() / resolveActualLimit()
     *   - conversationParticipantCount() → capacity for non-private conversations
     *   - conversationPath()            → path builder used in initiate()
     */
    private function mockFirebase(
        array $participantIds      = [],
        bool  $conversationExists  = true,
        bool  $isPrivate           = true,
        int   $participantCount    = 0,
    ): void {
        $mock = Mockery::mock(FirebaseService::class);

        $mock->shouldReceive('conversationPath')->andReturnUsing(
            fn(string $id) => "conversations/$id"
        );

        $mock->shouldReceive('conversationMetaPath')->andReturnUsing(
            fn(string $id) => "conversations/$id/meta"
        );

        $mock->shouldReceive('exists')->andReturn($conversationExists);

        $mock->shouldReceive('isConversationParticipant')
            ->andReturnUsing(
                fn(string $conversationId, string $userId) => in_array($userId, $participantIds, strict: true)
            );

        $mock->shouldReceive('isPrivateConversation')
            ->andReturn($isPrivate);

        $count = $participantCount ?: count($participantIds);
        $mock->shouldReceive('conversationParticipantCount')
            ->andReturn($count);

        $this->app->instance(FirebaseService::class, $mock);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function makeUser(): User
    {
        return User::factory()->create(['password' => Hash::make('Secret123')]);
    }

    private function makeScheduledConversationCall(
        User   $initiator,
        string $conversationId = 'firebase-conv-key'
    ): VideoCall {
        $call = VideoCall::factory()->scheduled()->forConversation($conversationId)->create([
            'initiated_by' => $initiator->id,
        ]);

        CallParticipant::factory()->host()->active()->create([
            'call_id' => $call->id,
            'user_id' => $initiator->id,
        ]);

        return $call;
    }

    private function makeActiveConversationCall(
        User   $initiator,
        string $conversationId = 'firebase-conv-key'
    ): VideoCall {
        $call = VideoCall::factory()->active()->forConversation($conversationId)->create([
            'initiated_by' => $initiator->id,
        ]);

        CallParticipant::factory()->host()->active()->create([
            'call_id' => $call->id,
            'user_id' => $initiator->id,
        ]);

        return $call;
    }

    private function makeEndedCall(User $initiator): VideoCall
    {
        return VideoCall::factory()->ended()->forConversation('firebase-conv-key')->create([
            'initiated_by' => $initiator->id,
        ]);
    }

    private function makeProjectCall(User $initiator, Project $project): VideoCall
    {
        $this->addToProject($project, $initiator);

        $call = VideoCall::factory()->scheduled()->forProject($project->id)->create([
            'initiated_by' => $initiator->id,
        ]);

        CallParticipant::factory()->host()->active()->create([
            'call_id' => $call->id,
            'user_id' => $initiator->id,
        ]);

        return $call;
    }

    private function joinCall(VideoCall $call, User $user): void
    {
        CallParticipant::factory()->active()->create([
            'call_id' => $call->id,
            'user_id' => $user->id,
        ]);
    }

    private function addToProject(Project $project, User $user): void
    {
        ProjectTeamMember::factory()->create([
            'project_id' => $project->id,
            'user_id'    => $user->id,
            'is_active'  => true,
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
        $this->mockFirebase([$user->id]);
        Sanctum::actingAs($user);

        $this->makeActiveConversationCall($user);
        $this->makeScheduledConversationCall($user);
        $this->makeActiveConversationCall($other);

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
        $this->mockFirebase([$user->id]);
        Sanctum::actingAs($user);

        $this->makeActiveConversationCall($user);
        $this->makeScheduledConversationCall($user);

        $this->getJson('/api/v1/calls?status=active')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function participated_calls_appear_in_list(): void
    {
        $initiator = $this->makeUser();
        $joiner    = $this->makeUser();
        $this->mockFirebase([$initiator->id, $joiner->id]);
        $call = $this->makeActiveConversationCall($initiator);
        $this->joinCall($call, $joiner);
        Sanctum::actingAs($joiner);

        $this->getJson('/api/v1/calls')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // =========================================================================
    // POST /api/v1/calls — initiate
    // =========================================================================

    /** @test */
    public function user_can_initiate_a_conversation_call(): void
    {
        $user = $this->makeUser();
        $this->mockFirebase([$user->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/calls', ['conversation_id' => 'firebase-conv-key'])
            ->assertStatus(201)
            ->assertJsonPath('data.call_type', 'conversation')
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonStructure([
                'data' => ['id', 'call_type', 'status', 'room_name', 'room_url', 'join_token', 'initiator'],
            ]);

        $call = VideoCall::where('initiated_by', $user->id)->first();
        $this->assertEquals('firebase-conv-key', $call->conversation_id);
        $this->assertNull($call->project_id);
        $this->assertDatabaseHas('call_participants', [
            'call_id' => $call->id,
            'user_id' => $user->id,
            'role'    => 'host',
        ]);
    }

    /** @test */
    public function user_can_initiate_a_project_call(): void
    {
        $user    = $this->makeUser();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $this->mockFirebase();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/calls', ['project_id' => $project->id])
            ->assertStatus(201)
            ->assertJsonPath('data.call_type', 'project');

        $call = VideoCall::where('initiated_by', $user->id)->first();
        $this->assertEquals($project->id, $call->project_id);
        $this->assertNull($call->conversation_id);
    }

    /** @test */
    public function initiate_returns_a_join_token_for_the_host(): void
    {
        $user = $this->makeUser();
        $this->mockFirebase([$user->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/calls', ['conversation_id' => 'firebase-conv-key'])
            ->assertStatus(201);

        $token = $response->json('data.join_token');
        $this->assertNotNull($token);
        $this->assertCount(3, explode('.', $token));
    }

    /** @test */
    public function initiate_stores_active_token_jti_on_host_participant_row(): void
    {
        $user = $this->makeUser();
        $this->mockFirebase([$user->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/calls', ['conversation_id' => 'firebase-conv-key'])
            ->assertStatus(201);

        $call = VideoCall::where('initiated_by', $user->id)->first();

        $participant = CallParticipant::where('call_id', $call->id)
            ->where('user_id', $user->id)
            ->first();

        // jti must be stored and must be a valid UUID
        $this->assertNotNull($participant->active_token_jti);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $participant->active_token_jti
        );
    }

    /** @test */
    public function initiate_returns_token_refresh_interval(): void
    {
        $user = $this->makeUser();
        $this->mockFirebase([$user->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/calls', ['conversation_id' => 'firebase-conv-key'])
            ->assertStatus(201);

        // token_refresh_interval must be present alongside join_token
        $this->assertArrayHasKey('token_refresh_interval', $response->json('data'));
        $this->assertGreaterThan(0, $response->json('data.token_refresh_interval'));
    }

    /** @test */
    public function call_type_is_derived_server_side_not_from_client(): void
    {
        $user = $this->makeUser();
        $this->mockFirebase([$user->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/calls', ['conversation_id' => 'firebase-conv-key'])
            ->assertStatus(201)
            ->assertJsonPath('data.call_type', 'conversation');
    }

    /** @test */
    public function room_name_uses_cofound_prefix(): void
    {
        $user = $this->makeUser();
        $this->mockFirebase([$user->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/calls', ['conversation_id' => 'firebase-conv-key'])
            ->assertStatus(201);

        $this->assertStringStartsWith('cofound-', VideoCall::latest()->first()->room_name);
    }

    /** @test */
    public function room_name_is_lowercase(): void
    {
        // strtolower() on Str::random() ensures room name matches Jitsi's
        // internal lowercasing so the JWT room claim never mismatches the MUC JID.
        $user = $this->makeUser();
        $this->mockFirebase([$user->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/calls', ['conversation_id' => 'firebase-conv-key'])
            ->assertStatus(201);

        $roomName = VideoCall::latest()->first()->room_name;
        $this->assertEquals(strtolower($roomName), $roomName);
    }

    /** @test */
    public function call_cannot_be_initiated_without_a_context(): void
    {
        $this->mockFirebase();
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/v1/calls')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['context']);
    }

    /** @test */
    public function call_cannot_have_both_context_ids(): void
    {
        $user    = $this->makeUser();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $this->mockFirebase([$user->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/calls', [
            'conversation_id' => 'firebase-conv-key',
            'project_id'      => $project->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['context']);
    }

    /** @test */
    public function conversation_not_found_in_firebase_returns_404(): void
    {
        $this->mockFirebase(conversationExists: false);
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/v1/calls', ['conversation_id' => 'nonexistent-key'])
            ->assertStatus(404);
    }

    /** @test */
    public function can_initiate_call_as_immediately_active(): void
    {
        $user = $this->makeUser();
        $this->mockFirebase([$user->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/calls', [
            'conversation_id' => 'firebase-conv-key',
            'status'          => 'active',
        ])->assertStatus(201)
            ->assertJsonPath('data.status', 'active');
    }

    /** @test */
    public function start_time_in_the_past_is_rejected(): void
    {
        $this->mockFirebase();
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/v1/calls', [
            'conversation_id' => 'firebase-conv-key',
            'start_time'      => now()->subHour()->toISOString(),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['start_time']);
    }

    /** @test */
    public function unauthenticated_user_cannot_initiate_a_call(): void
    {
        $this->postJson('/api/v1/calls', ['conversation_id' => 'firebase-conv-key'])
            ->assertStatus(401);
    }

    // =========================================================================
    // GET /api/v1/calls/{id}
    // =========================================================================

    /** @test */
    public function user_can_view_a_call(): void
    {
        $initiator = $this->makeUser();
        $this->mockFirebase([$initiator->id]);
        $call = $this->makeScheduledConversationCall($initiator);
        Sanctum::actingAs($initiator);

        $this->getJson("/api/v1/calls/$call->id")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $call->id)
            ->assertJsonStructure([
                'data' => ['id', 'call_type', 'status', 'room_name', 'initiator', 'participants'],
            ]);
    }

    /** @test */
    public function show_does_not_expose_join_token(): void
    {
        $initiator = $this->makeUser();
        $this->mockFirebase([$initiator->id]);
        $call = $this->makeScheduledConversationCall($initiator);
        Sanctum::actingAs($initiator);

        $response = $this->getJson("/api/v1/calls/$call->id")->assertStatus(200);
        $this->assertArrayNotHasKey('join_token', $response->json('data'));
        $this->assertArrayNotHasKey('token_refresh_interval', $response->json('data'));
    }

    /** @test */
    public function show_returns_404_for_unknown_call(): void
    {
        $this->mockFirebase();
        Sanctum::actingAs($this->makeUser());

        $this->getJson('/api/v1/calls/00000000-0000-0000-0000-000000000001')
            ->assertStatus(404);
    }

    // =========================================================================
    // POST /api/v1/calls/{id}/join — access + token issuance
    // =========================================================================

    /** @test */
    public function conversation_participant_can_join_call(): void
    {
        $initiator = $this->makeUser();
        $joiner    = $this->makeUser();
        $this->mockFirebase([$initiator->id, $joiner->id]);
        $call = $this->makeScheduledConversationCall($initiator);
        Sanctum::actingAs($joiner);

        $this->postJson("/api/v1/calls/$call->id/join")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonStructure(['data' => ['join_token', 'token_refresh_interval']]);

        $this->assertDatabaseHas('call_participants', [
            'call_id' => $call->id,
            'user_id' => $joiner->id,
            'role'    => 'participant',
        ]);
    }

    /** @test */
    public function non_conversation_participant_cannot_join_call(): void
    {
        $initiator = $this->makeUser();
        $outsider  = $this->makeUser();
        $this->mockFirebase([$initiator->id]); // outsider not in list
        $call = $this->makeScheduledConversationCall($initiator);
        Sanctum::actingAs($outsider);

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(409);
    }

    /** @test */
    public function join_stores_active_token_jti_on_participant_row(): void
    {
        $initiator = $this->makeUser();
        $joiner    = $this->makeUser();
        $this->mockFirebase([$initiator->id, $joiner->id]);
        $call = $this->makeScheduledConversationCall($initiator);
        Sanctum::actingAs($joiner);

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        $participant = CallParticipant::where('call_id', $call->id)
            ->where('user_id', $joiner->id)
            ->first();

        $this->assertNotNull($participant->active_token_jti);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $participant->active_token_jti
        );
    }

    /** @test */
    public function reconnect_issues_fresh_token_and_updates_jti(): void
    {
        // When an already-active participant calls /join again (idempotent reconnect),
        // a new jti must be minted and stored, invalidating the previous token.
        $initiator = $this->makeUser();
        $this->mockFirebase([$initiator->id]);
        $call = $this->makeActiveConversationCall($initiator);
        Sanctum::actingAs($initiator);

        // First /join — get initial jti
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);
        $jtiFirst = CallParticipant::where('call_id', $call->id)
            ->where('user_id', $initiator->id)
            ->value('active_token_jti');

        // Second /join — must get a different jti
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);
        $jtiSecond = CallParticipant::where('call_id', $call->id)
            ->where('user_id', $initiator->id)
            ->value('active_token_jti');

        $this->assertNotEquals($jtiFirst, $jtiSecond,
            'Each /join must produce a new jti to invalidate previously shared tokens.'
        );
    }

    /** @test */
    public function join_response_includes_token_refresh_interval(): void
    {
        // The frontend uses this value to schedule silent token refreshes.
        // It must never be hardcoded on the client side.
        $initiator = $this->makeUser();
        $joiner    = $this->makeUser();
        $this->mockFirebase([$initiator->id, $joiner->id]);
        $call = $this->makeScheduledConversationCall($initiator);
        Sanctum::actingAs($joiner);

        $response = $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        $this->assertArrayHasKey('token_refresh_interval', $response->json('data'));
        $interval = $response->json('data.token_refresh_interval');
        $this->assertGreaterThan(0, $interval);
        $this->assertLessThan(config('jitsi.token_ttl', 30), $interval);
    }

    /** @test */
    public function rejoin_after_leave_stores_new_jti(): void
    {
        // When a participant rejoins after leaving, the old jti (which was
        // cleared on leave) must be replaced with a fresh one.
        $initiator = $this->makeUser();
        $joiner    = $this->makeUser();
        $this->mockFirebase([$initiator->id, $joiner->id]);
        $call = $this->makeActiveConversationCall($initiator);

        CallParticipant::factory()->left()->create([
            'call_id'          => $call->id,
            'user_id'          => $joiner->id,
            'active_token_jti' => null, // cleared on leave
        ]);
        Sanctum::actingAs($joiner);

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        $participant = CallParticipant::where('call_id', $call->id)
            ->where('user_id', $joiner->id)
            ->first();

        $this->assertNull($participant->left_at);
        $this->assertNotNull($participant->active_token_jti);
    }

    /** @test */
    public function leave_clears_active_token_jti(): void
    {
        // When a participant leaves, their jti must be cleared so a stale token
        // cannot be used to rejoin (the jti check only applies to active rows,
        // but clearing it is defensive practice and keeps the data clean).
        $initiator = $this->makeUser();
        $joiner    = $this->makeUser();
        $this->mockFirebase([$initiator->id, $joiner->id]);
        $call = $this->makeActiveConversationCall($initiator);
        $this->joinCall($call, $joiner);
        Sanctum::actingAs($joiner);

        $this->postJson("/api/v1/calls/$call->id/leave")->assertStatus(200);

        $participant = CallParticipant::where('call_id', $call->id)
            ->where('user_id', $joiner->id)
            ->first();

        $this->assertNull($participant->active_token_jti);
    }

    /** @test */
    public function joining_a_scheduled_call_activates_it(): void
    {
        $initiator = $this->makeUser();
        $joiner    = $this->makeUser();
        $this->mockFirebase([$initiator->id, $joiner->id]);
        $call = $this->makeScheduledConversationCall($initiator);
        Sanctum::actingAs($joiner);

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        $this->assertDatabaseHas('video_calls', ['id' => $call->id, 'status' => 'active']);
    }

    /** @test */
    public function joining_is_idempotent_when_already_active(): void
    {
        $initiator = $this->makeUser();
        $this->mockFirebase([$initiator->id]);
        $call = $this->makeActiveConversationCall($initiator);
        Sanctum::actingAs($initiator);

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        $this->assertDatabaseCount('call_participants', 1);
    }

    /** @test */
    public function user_can_rejoin_after_leaving(): void
    {
        $initiator = $this->makeUser();
        $joiner    = $this->makeUser();
        $this->mockFirebase([$initiator->id, $joiner->id]);
        $call = $this->makeActiveConversationCall($initiator);

        CallParticipant::factory()->left()->create([
            'call_id' => $call->id,
            'user_id' => $joiner->id,
        ]);
        Sanctum::actingAs($joiner);

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        $participant = CallParticipant::where('call_id', $call->id)
            ->where('user_id', $joiner->id)->first();
        $this->assertNull($participant->left_at);
        $this->assertDatabaseCount('call_participants', 2);
    }

    /** @test */
    public function cannot_join_an_ended_call(): void
    {
        $initiator = $this->makeUser();
        $this->mockFirebase([$initiator->id]);
        $call = $this->makeEndedCall($initiator);
        Sanctum::actingAs($this->makeUser());

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(409);
    }

    /** @test */
    public function join_token_is_not_persisted_to_database(): void
    {
        $initiator = $this->makeUser();
        $joiner    = $this->makeUser();
        $this->mockFirebase([$initiator->id, $joiner->id]);
        $call = $this->makeScheduledConversationCall($initiator);
        Sanctum::actingAs($joiner);

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        $record = DB::table('video_calls')->where('id', $call->id)->first();
        $this->assertFalse(property_exists($record, 'join_token'));
    }

    // =========================================================================
    // POST /api/v1/calls/{id}/join — capacity enforcement
    // =========================================================================

    /** @test */
    public function private_conversation_call_is_limited_to_two_participants(): void
    {
        // isPrivate = true → max 2.
        // Initiator already holds slot 1. Joiner takes slot 2.
        // A third person is blocked even though they are in the participant list.
        $initiator = $this->makeUser();
        $joiner    = $this->makeUser();
        $third     = $this->makeUser();

        $this->mockFirebase(
            participantIds: [$initiator->id, $joiner->id, $third->id],
            isPrivate: true
        );

        $call = $this->makeScheduledConversationCall($initiator);

        // Joiner takes the second slot
        Sanctum::actingAs($joiner);
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        // Third person — call is full
        Sanctum::actingAs($third);
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(409);
    }

    /** @test */
    public function group_conversation_call_allows_all_members(): void
    {
        // isPrivate = false → cap = participantCount (3 here).
        $initiator = $this->makeUser();
        $member1   = $this->makeUser();
        $member2   = $this->makeUser();

        $this->mockFirebase(
            participantIds: [$initiator->id, $member1->id, $member2->id],
            isPrivate: false,
            participantCount: 3
        );

        $call = $this->makeScheduledConversationCall($initiator);

        Sanctum::actingAs($member1);
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        Sanctum::actingAs($member2);
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);
    }

    /** @test */
    public function reconnect_does_not_count_against_capacity(): void
    {
        // An already-active participant calling /join again must not be blocked
        // even when the call is at capacity. Their slot is already taken.
        $initiator = $this->makeUser();
        $joiner    = $this->makeUser();

        $this->mockFirebase(
            participantIds: [$initiator->id, $joiner->id],
            isPrivate: true  // max 2 — call will be full after joiner joins
        );

        $call = $this->makeScheduledConversationCall($initiator);

        Sanctum::actingAs($joiner);
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        // Call is now at capacity (2/2).
        // Initiator reconnects — must succeed because slot is already theirs.
        Sanctum::actingAs($initiator);
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);
    }

    /** @test */
    public function project_call_allows_all_team_members(): void
    {
        $initiator = $this->makeUser();
        $member1   = $this->makeUser();
        $member2   = $this->makeUser();
        $project   = Project::factory()->create(['owner_id' => $initiator->id]);

        $this->addToProject($project, $member1);
        $this->addToProject($project, $member2);
        $this->mockFirebase();

        $call = $this->makeProjectCall($initiator, $project);

        Sanctum::actingAs($member1);
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);

        Sanctum::actingAs($member2);
        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);
    }

    /** @test */
    public function project_call_blocks_non_member(): void
    {
        $initiator = $this->makeUser();
        $project   = Project::factory()->create(['owner_id' => $initiator->id]);
        $this->mockFirebase();

        $call = $this->makeProjectCall($initiator, $project);

        Sanctum::actingAs($this->makeUser()); // outsider

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(409);
    }

    /** @test */
    public function project_team_member_can_join_project_call(): void
    {
        $initiator = $this->makeUser();
        $project   = Project::factory()->create(['owner_id' => $initiator->id]);
        $member    = $this->makeUser();
        $this->addToProject($project, $member);
        $this->mockFirebase();
        $call = $this->makeProjectCall($initiator, $project);
        Sanctum::actingAs($member);

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(200);
    }

    /** @test */
    public function non_project_member_cannot_join_project_call(): void
    {
        $initiator = $this->makeUser();
        $project   = Project::factory()->create(['owner_id' => $initiator->id]);
        $this->mockFirebase();
        $call = $this->makeProjectCall($initiator, $project);
        Sanctum::actingAs($this->makeUser());

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(409);
    }

    // =========================================================================
    // POST /api/v1/calls/{id}/leave
    // =========================================================================

    /** @test */
    public function participant_can_leave_a_call(): void
    {
        $initiator = $this->makeUser();
        $joiner    = $this->makeUser();
        $this->mockFirebase([$initiator->id, $joiner->id]);
        $call = $this->makeActiveConversationCall($initiator);
        $this->joinCall($call, $joiner);
        Sanctum::actingAs($joiner);

        $this->postJson("/api/v1/calls/$call->id/leave")->assertStatus(200);

        $participant = CallParticipant::where('call_id', $call->id)
            ->where('user_id', $joiner->id)->first();
        $this->assertNotNull($participant->left_at);
        $this->assertNull($participant->active_token_jti); // cleared on leave
    }

    /** @test */
    public function call_ends_automatically_when_last_participant_leaves(): void
    {
        $initiator = $this->makeUser();
        $this->mockFirebase([$initiator->id]);
        $call = $this->makeActiveConversationCall($initiator);
        Sanctum::actingAs($initiator);

        $this->postJson("/api/v1/calls/$call->id/leave")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'ended');

        $this->assertDatabaseHas('video_calls', ['id' => $call->id, 'status' => 'ended']);
    }

    /** @test */
    public function call_continues_when_others_remain_after_host_leaves(): void
    {
        $initiator = $this->makeUser();
        $this->mockFirebase([$initiator->id]);
        $call = $this->makeActiveConversationCall($initiator);
        $this->joinCall($call, $this->makeUser());
        Sanctum::actingAs($initiator);

        $this->postJson("/api/v1/calls/$call->id/leave")
            ->assertStatus(200)
            ->assertJsonMissing(['status' => 'ended']);
    }

    /** @test */
    public function non_participant_cannot_leave_a_call(): void
    {
        $initiator = $this->makeUser();
        $this->mockFirebase();
        $call = $this->makeActiveConversationCall($initiator);
        Sanctum::actingAs($this->makeUser());

        $this->postJson("/api/v1/calls/$call->id/leave")->assertStatus(403);
    }

    /** @test */
    public function cannot_leave_an_ended_call(): void
    {
        $initiator = $this->makeUser();
        $this->mockFirebase();
        $call = $this->makeEndedCall($initiator);
        Sanctum::actingAs($initiator);

        $this->postJson("/api/v1/calls/$call->id/leave")->assertStatus(409);
    }

    // =========================================================================
    // PATCH /api/v1/calls/{id}/end
    // =========================================================================

    /** @test */
    public function host_can_end_an_active_call(): void
    {
        $initiator = $this->makeUser();
        $this->mockFirebase([$initiator->id]);
        $call = $this->makeActiveConversationCall($initiator);
        $this->joinCall($call, $this->makeUser());
        Sanctum::actingAs($initiator);

        $this->patchJson("/api/v1/calls/$call->id/end")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'ended');
    }

    /** @test */
    public function ending_a_call_marks_all_participants_as_left(): void
    {
        $initiator = $this->makeUser();
        $this->mockFirebase([$initiator->id]);
        $call = $this->makeActiveConversationCall($initiator);
        $this->joinCall($call, $this->makeUser());
        Sanctum::actingAs($initiator);

        $this->patchJson("/api/v1/calls/$call->id/end")->assertStatus(200);

        $this->assertEquals(
            0,
            CallParticipant::where('call_id', $call->id)->whereNull('left_at')->count()
        );
    }

    /** @test */
    public function ending_a_call_clears_all_participant_jtis(): void
    {
        // When the host ends the call, all active_token_jti values must be
        // cleared so no dangling tokens can be used to re-enter a destroyed room.
        $initiator = $this->makeUser();
        $this->mockFirebase([$initiator->id]);
        $call = $this->makeActiveConversationCall($initiator);
        $this->joinCall($call, $this->makeUser());
        Sanctum::actingAs($initiator);

        $this->patchJson("/api/v1/calls/$call->id/end")->assertStatus(200);

        $hasJti = CallParticipant::where('call_id', $call->id)
            ->whereNotNull('active_token_jti')
            ->exists();

        $this->assertFalse($hasJti);
    }

    /** @test */
    public function ended_call_records_duration_seconds(): void
    {
        $initiator = $this->makeUser();
        $this->mockFirebase([$initiator->id]);
        $call = VideoCall::factory()->active()->forConversation('firebase-conv-key')->create([
            'initiated_by' => $initiator->id,
            'start_time'   => now()->subMinutes(5),
        ]);
        CallParticipant::factory()->host()->active()->create([
            'call_id' => $call->id,
            'user_id' => $initiator->id,
        ]);
        Sanctum::actingAs($initiator);

        $response = $this->patchJson("/api/v1/calls/$call->id/end")->assertStatus(200);
        $this->assertGreaterThan(0, $response->json('data.duration_seconds'));
    }

    /** @test */
    public function non_host_cannot_end_a_call(): void
    {
        $initiator = $this->makeUser();
        $other     = $this->makeUser();
        $this->mockFirebase([$initiator->id, $other->id]);
        $call = $this->makeActiveConversationCall($initiator);
        $this->joinCall($call, $other);
        Sanctum::actingAs($other);

        $this->patchJson("/api/v1/calls/$call->id/end")->assertStatus(403);
    }

    /** @test */
    public function cannot_end_an_already_ended_call(): void
    {
        $initiator = $this->makeUser();
        $this->mockFirebase();
        $call = $this->makeEndedCall($initiator);
        Sanctum::actingAs($initiator);

        $this->patchJson("/api/v1/calls/$call->id/end")->assertStatus(409);
    }

    /** @test */
    public function cannot_end_a_scheduled_call(): void
    {
        $initiator = $this->makeUser();
        $this->mockFirebase([$initiator->id]);
        $call = $this->makeScheduledConversationCall($initiator);
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
        $this->mockFirebase([$initiator->id]);
        $call = $this->makeScheduledConversationCall($initiator);
        Sanctum::actingAs($initiator);

        $this->patchJson("/api/v1/calls/$call->id/cancel")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }

    /** @test */
    public function cannot_cancel_an_active_call(): void
    {
        $initiator = $this->makeUser();
        $this->mockFirebase([$initiator->id]);
        $call = $this->makeActiveConversationCall($initiator);
        Sanctum::actingAs($initiator);

        $this->patchJson("/api/v1/calls/$call->id/cancel")->assertStatus(409);
    }

    /** @test */
    public function cannot_cancel_an_ended_call(): void
    {
        $initiator = $this->makeUser();
        $this->mockFirebase();
        $call = $this->makeEndedCall($initiator);
        Sanctum::actingAs($initiator);

        $this->patchJson("/api/v1/calls/$call->id/cancel")->assertStatus(409);
    }

    /** @test */
    public function non_host_cannot_cancel_a_call(): void
    {
        $initiator = $this->makeUser();
        $this->mockFirebase();
        $call = $this->makeScheduledConversationCall($initiator);
        Sanctum::actingAs($this->makeUser());

        $this->patchJson("/api/v1/calls/$call->id/cancel")->assertStatus(403);
    }
}
