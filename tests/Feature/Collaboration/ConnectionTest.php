<?php

namespace Tests\Feature\Collaboration;

use App\Models\CollaborationInvitation;
use App\Models\CollaborationRating;
use App\Models\Project;
use App\Models\User;
use App\Models\UserConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConnectionTest extends TestCase
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

    private function makeConnection(
        User   $requester,
        User   $recipient,
        string $status = 'pending'
    ): UserConnection {
        return UserConnection::factory()->create([
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
            'status'       => $status,
        ]);
    }

    private function makeInvitation(
        User   $sender,
        User   $recipient,
        string $status = 'pending',
        array  $overrides = []
    ): CollaborationInvitation {
        return CollaborationInvitation::factory()->create(array_merge([
            'sender_id'       => $sender->id,
            'recipient_id'    => $recipient->id,
            'status'          => $status,
            'invitation_type' => 'co_founder',
        ], $overrides));
    }

    // =========================================================================
    // GET /api/v1/connections
    // =========================================================================

    /** @test */
    public function user_can_list_their_connections(): void
    {
        $user  = $this->makeUser();
        $other = $this->makeUser();
        Sanctum::actingAs($user);

        $this->makeConnection($user, $other, 'accepted');
        $this->makeConnection($user, $this->makeUser());

        $this->getJson('/api/v1/connections')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function connections_can_be_filtered_by_status(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->makeConnection($user, $this->makeUser(), 'accepted');
        $this->makeConnection($user, $this->makeUser());
        $this->makeConnection($user, $this->makeUser(), 'blocked');

        $this->getJson('/api/v1/connections?status=accepted')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function unauthenticated_user_cannot_list_connections(): void
    {
        $this->getJson('/api/v1/connections')->assertStatus(401);
    }

    // =========================================================================
    // POST /api/v1/connections  (send request)
    // =========================================================================

    /** @test */
    public function user_can_send_a_connection_request(): void
    {
        $requester = $this->makeUser();
        $recipient = $this->makeUser();
        Sanctum::actingAs($requester);

        $this->postJson('/api/v1/connections', [
            'recipient_id' => $recipient->id,
            'message'      => 'Hi, let us connect!',
        ])->assertStatus(201);

        $this->assertDatabaseHas('user_connections', [
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
            'status'       => 'pending',
        ]);
    }

    /** @test */
    public function cannot_send_connection_request_to_self(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/connections', [
            'recipient_id' => $user->id,
        ])->assertStatus(422);
    }

    /** @test */
    public function cannot_send_duplicate_connection_request(): void
    {
        $requester = $this->makeUser();
        $recipient = $this->makeUser();
        Sanctum::actingAs($requester);

        $this->makeConnection($requester, $recipient);

        $this->postJson('/api/v1/connections', [
            'recipient_id' => $recipient->id,
        ])->assertStatus(409);
    }

    /** @test */
    public function cannot_send_request_to_already_connected_user(): void
    {
        $requester = $this->makeUser();
        $recipient = $this->makeUser();
        Sanctum::actingAs($requester);

        $this->makeConnection($requester, $recipient, 'accepted');

        $this->postJson('/api/v1/connections', [
            'recipient_id' => $recipient->id,
        ])->assertStatus(409);
    }

    /** @test */
    public function recipient_id_must_exist(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/v1/connections', [
            'recipient_id' => '00000000-0000-0000-0000-000000000001',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['recipient_id']);
    }

    // =========================================================================
    // PATCH /api/v1/connections/{id}/accept
    // =========================================================================

    /** @test */
    public function recipient_can_accept_connection_request(): void
    {
        $requester  = $this->makeUser();
        $recipient  = $this->makeUser();
        $connection = $this->makeConnection($requester, $recipient);
        Sanctum::actingAs($recipient);

        $this->patchJson("/api/v1/connections/$connection->id/accept")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseHas('user_connections', [
            'id'     => $connection->id,
            'status' => 'accepted',
        ]);
    }

    /** @test */
    public function requester_cannot_accept_own_request(): void
    {
        $requester  = $this->makeUser();
        $recipient  = $this->makeUser();
        $connection = $this->makeConnection($requester, $recipient);
        Sanctum::actingAs($requester);

        $this->patchJson("/api/v1/connections/$connection->id/accept")
            ->assertStatus(403);
    }

    /** @test */
    public function cannot_accept_already_accepted_connection(): void
    {
        $requester  = $this->makeUser();
        $recipient  = $this->makeUser();
        $connection = $this->makeConnection($requester, $recipient, 'accepted');
        Sanctum::actingAs($recipient);

        $this->patchJson("/api/v1/connections/$connection->id/accept")
            ->assertStatus(409);
    }

    // =========================================================================
    // PATCH /api/v1/connections/{id}/reject
    // =========================================================================

    /** @test */
    public function recipient_can_reject_connection_request(): void
    {
        $requester  = $this->makeUser();
        $recipient  = $this->makeUser();
        $connection = $this->makeConnection($requester, $recipient);
        Sanctum::actingAs($recipient);

        $this->patchJson("/api/v1/connections/$connection->id/reject")
            ->assertStatus(200);

        $this->assertDatabaseMissing('user_connections', ['id' => $connection->id]);
    }

    /** @test */
    public function cannot_reject_an_accepted_connection(): void
    {
        $requester  = $this->makeUser();
        $recipient  = $this->makeUser();
        $connection = $this->makeConnection($requester, $recipient, 'accepted');
        Sanctum::actingAs($recipient);

        $this->patchJson("/api/v1/connections/$connection->id/reject")
            ->assertStatus(409);
    }

    // =========================================================================
    // PATCH /api/v1/connections/{id}/block
    // =========================================================================

    /** @test */
    public function user_can_block_a_connection(): void
    {
        $requester  = $this->makeUser();
        $recipient  = $this->makeUser();
        $connection = $this->makeConnection($requester, $recipient, 'accepted');
        Sanctum::actingAs($recipient);

        $this->patchJson("/api/v1/connections/$connection->id/block")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'blocked');
    }

    /** @test */
    public function cannot_block_already_blocked_connection(): void
    {
        $requester  = $this->makeUser();
        $recipient  = $this->makeUser();
        $connection = $this->makeConnection($requester, $recipient, 'blocked');
        Sanctum::actingAs($recipient);

        $this->patchJson("/api/v1/connections/$connection->id/block")
            ->assertStatus(409);
    }

    // =========================================================================
    // DELETE /api/v1/connections/{id}
    // =========================================================================

    /** @test */
    public function user_can_remove_an_accepted_connection(): void
    {
        $requester  = $this->makeUser();
        $recipient  = $this->makeUser();
        $connection = $this->makeConnection($requester, $recipient, 'accepted');
        Sanctum::actingAs($requester);

        $this->deleteJson("/api/v1/connections/$connection->id")
            ->assertStatus(200);

        $this->assertDatabaseMissing('user_connections', ['id' => $connection->id]);
    }

    /** @test */
    public function unrelated_user_cannot_remove_connection(): void
    {
        $requester  = $this->makeUser();
        $recipient  = $this->makeUser();
        $connection = $this->makeConnection($requester, $recipient, 'accepted');
        Sanctum::actingAs($this->makeUser());

        $this->deleteJson("/api/v1/connections/$connection->id")
            ->assertStatus(403);
    }

    // =========================================================================
    // Invitations — GET, POST, PATCH respond, PATCH withdraw
    // =========================================================================

    /** @test */
    public function user_can_list_invitations(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->makeInvitation($this->makeUser(), $user);
        $this->makeInvitation($this->makeUser(), $user);

        $this->getJson('/api/v1/invitations')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function user_can_send_a_collaboration_invitation(): void
    {
        $sender    = $this->makeUser();
        $recipient = $this->makeUser();
        Sanctum::actingAs($sender);

        $this->postJson('/api/v1/invitations', [
            'recipient_id'    => $recipient->id,
            'invitation_type' => 'co_founder',
            'message'         => 'Want to co-found something?',
        ])->assertStatus(201);

        $this->assertDatabaseHas('collaboration_invitations', [
            'sender_id'    => $sender->id,
            'recipient_id' => $recipient->id,
            'status'       => 'pending',
        ]);
    }

    /** @test */
    public function cannot_invite_yourself(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/invitations', [
            'recipient_id'    => $user->id,
            'invitation_type' => 'co_founder',
        ])->assertStatus(422);
    }

    /** @test */
    public function cannot_send_duplicate_pending_invitation(): void
    {
        $sender    = $this->makeUser();
        $recipient = $this->makeUser();
        Sanctum::actingAs($sender);

        $this->makeInvitation($sender, $recipient);

        $this->postJson('/api/v1/invitations', [
            'recipient_id'    => $recipient->id,
            'invitation_type' => 'co_founder',
        ])->assertStatus(409);
    }

    /** @test */
    public function recipient_can_accept_invitation(): void
    {
        $sender     = $this->makeUser();
        $recipient  = $this->makeUser();
        $invitation = $this->makeInvitation($sender, $recipient);
        Sanctum::actingAs($recipient);

        $this->patchJson("/api/v1/invitations/$invitation->id/respond", [
            'action' => 'accepted',
        ])->assertStatus(200)
            ->assertJsonPath('data.status', 'accepted');
    }

    /** @test */
    public function recipient_can_decline_invitation(): void
    {
        $sender     = $this->makeUser();
        $recipient  = $this->makeUser();
        $invitation = $this->makeInvitation($sender, $recipient);
        Sanctum::actingAs($recipient);

        $this->patchJson("/api/v1/invitations/$invitation->id/respond", [
            'action' => 'declined',
        ])->assertStatus(200)
            ->assertJsonPath('data.status', 'declined');
    }

    /** @test */
    public function cannot_respond_to_already_responded_invitation(): void
    {
        $sender     = $this->makeUser();
        $recipient  = $this->makeUser();
        $invitation = $this->makeInvitation($sender, $recipient, 'accepted');
        Sanctum::actingAs($recipient);

        $this->patchJson("/api/v1/invitations/$invitation->id/respond", [
            'action' => 'declined',
        ])->assertStatus(409);
    }

    /** @test */
    public function sender_can_withdraw_pending_invitation(): void
    {
        $sender     = $this->makeUser();
        $recipient  = $this->makeUser();
        $invitation = $this->makeInvitation($sender, $recipient);
        Sanctum::actingAs($sender);

        $this->patchJson("/api/v1/invitations/$invitation->id/withdraw")
            ->assertStatus(200);

        $this->assertDatabaseHas('collaboration_invitations', [
            'id'     => $invitation->id,
            'status' => 'withdrawn',
        ]);
    }

    /** @test */
    public function cannot_withdraw_non_pending_invitation(): void
    {
        $sender     = $this->makeUser();
        $recipient  = $this->makeUser();
        $invitation = $this->makeInvitation($sender, $recipient, 'accepted');
        Sanctum::actingAs($sender);

        $this->patchJson("/api/v1/invitations/$invitation->id/withdraw")
            ->assertStatus(409);
    }

    /** @test */
    public function recipient_cannot_withdraw_invitation(): void
    {
        $sender     = $this->makeUser();
        $recipient  = $this->makeUser();
        $invitation = $this->makeInvitation($sender, $recipient);
        Sanctum::actingAs($recipient);

        $this->patchJson("/api/v1/invitations/$invitation->id/withdraw")
            ->assertStatus(403);
    }

    // =========================================================================
    // Ratings — POST, PUT, DELETE, GET
    // =========================================================================

    /** @test */
    public function user_can_rate_another_user(): void
    {
        $rater = $this->makeUser();
        $rated = $this->makeUser();
        Sanctum::actingAs($rater);

        $this->postJson('/api/v1/ratings', [
            'rated_user_id'  => $rated->id,
            'overall_rating' => 4,
            'review_text'    => 'Great collaborator!',
        ])->assertStatus(201);

        $this->assertDatabaseHas('collaboration_ratings', [
            'rater_id'       => $rater->id,
            'rated_user_id'  => $rated->id,
            'overall_rating' => 4,
        ]);
    }

    /** @test */
    public function cannot_rate_yourself(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/ratings', [
            'rated_user_id'  => $user->id,
            'overall_rating' => 5,
        ])->assertStatus(422);
    }

    /** @test */
    public function cannot_rate_same_user_twice_for_same_project(): void
    {
        $rater   = $this->makeUser();
        $rated   = $this->makeUser();
        $project = Project::factory()->create(['owner_id' => $rater->id]);
        Sanctum::actingAs($rater);

        CollaborationRating::factory()->create([
            'rater_id'      => $rater->id,
            'rated_user_id' => $rated->id,
            'project_id'    => $project->id,
        ]);

        $this->postJson('/api/v1/ratings', [
            'rated_user_id'  => $rated->id,
            'overall_rating' => 3,
            'project_id'     => $project->id,
        ])->assertStatus(409);
    }

    /** @test */
    public function overall_rating_must_be_between_1_and_5(): void
    {
        $rater = $this->makeUser();
        $rated = $this->makeUser();
        Sanctum::actingAs($rater);

        $this->postJson('/api/v1/ratings', [
            'rated_user_id'  => $rated->id,
            'overall_rating' => 6,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['overall_rating']);
    }

    /** @test */
    public function user_can_update_their_rating(): void
    {
        $rater  = $this->makeUser();
        $rated  = $this->makeUser();
        $rating = CollaborationRating::factory()->create([
            'rater_id'       => $rater->id,
            'rated_user_id'  => $rated->id,
            'overall_rating' => 3,
        ]);
        Sanctum::actingAs($rater);

        $this->putJson("/api/v1/ratings/$rating->id", [
            'overall_rating' => 5,
            'review_text'    => 'Actually excellent!',
        ])->assertStatus(200)
            ->assertJsonPath('data.overall_rating', 5);
    }

    /** @test */
    public function user_cannot_update_another_users_rating(): void
    {
        $rater  = $this->makeUser();
        $rated  = $this->makeUser();
        $rating = CollaborationRating::factory()->create([
            'rater_id'      => $rater->id,
            'rated_user_id' => $rated->id,
        ]);
        Sanctum::actingAs($this->makeUser());

        $this->putJson("/api/v1/ratings/$rating->id", [
            'overall_rating' => 1,
        ])->assertStatus(403);
    }

    /** @test */
    public function user_can_delete_their_rating(): void
    {
        $rater  = $this->makeUser();
        $rated  = $this->makeUser();
        $rating = CollaborationRating::factory()->create([
            'rater_id'      => $rater->id,
            'rated_user_id' => $rated->id,
        ]);
        Sanctum::actingAs($rater);

        $this->deleteJson("/api/v1/ratings/$rating->id")
            ->assertStatus(200);

        $this->assertDatabaseMissing('collaboration_ratings', ['id' => $rating->id]);
    }

    /** @test */
    public function anyone_can_view_ratings_for_a_user(): void
    {
        $rater = $this->makeUser();
        $rated = $this->makeUser();

        CollaborationRating::factory()->public()->count(3)->create([
            'rater_id'      => $rater->id,
            'rated_user_id' => $rated->id,
        ]);

        Sanctum::actingAs($this->makeUser());

        $this->getJson("/api/v1/users/$rated->id/ratings")
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}
