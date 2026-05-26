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
     * Mock the two FirebaseService calls that VideoCallService makes:
     *   - exists()  → initiate(): verify the conversation node exists in RTDB
     *   - get()     → assertCanJoin(): read meta/participant_ids
     */
    private function mockFirebase(
        array $participantIds = [],
        bool  $conversationExists = true,
    ): void {
        $mock = Mockery::mock(FirebaseService::class);

        $mock->shouldReceive('conversationPath')->andReturnUsing(
            fn(string $id) => "conversations/$id"
        );

        $mock->shouldReceive('conversationMetaPath')->andReturnUsing(
            fn(string $id) => "conversations/$id/meta"
        );

        $mock->shouldReceive('exists')->andReturn($conversationExists);
        $mock->shouldReceive('get')->andReturn(
            $participantIds ? ['participant_ids' => $participantIds] : null
        );

        // Add this — mock isConversationParticipant
        $mock->shouldReceive('isConversationParticipant')
            ->andReturnUsing(
                fn(string $conversationId, string $userId) => in_array($userId, $participantIds)
            );

        $this->app->instance(FirebaseService::class, $mock);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function makeUser(): User
    {
        return User::factory()->create(['password' => Hash::make('Secret123')]);
    }

    private function makeScheduledConversationCall(User $initiator, string $conversationId = 'firebase-conv-key'): VideoCall
    {
        $call = VideoCall::factory()->scheduled()->forConversation($conversationId)->create([
            'initiated_by' => $initiator->id,
        ]);

        CallParticipant::factory()->host()->active()->create([
            'call_id' => $call->id,
            'user_id' => $initiator->id,
        ]);

        return $call;
    }

    private function makeActiveConversationCall(User $initiator, string $conversationId = 'firebase-conv-key'): VideoCall
    {
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
    // POST /api/v1/calls  —  initiate
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
    // POST /api/v1/calls/{id}/join
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
            ->assertJsonStructure(['data' => ['join_token']]);

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
        $this->mockFirebase([$initiator->id]);
        $call = $this->makeScheduledConversationCall($initiator);
        Sanctum::actingAs($outsider);

        $this->postJson("/api/v1/calls/$call->id/join")->assertStatus(409);
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
