<?php

namespace Tests\Feature\Admin;

use App\Models\ContentModeration;
use Laravel\Sanctum\Sanctum;

class AdminModerationTest extends AdminTestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeLogEntry(array $overrides = []): ContentModeration
    {
        return ContentModeration::factory()->create($overrides);
    }

    private function validLogPayload(array $overrides = []): array
    {
        return array_merge([
            'content_type'    => 'message',
            'content_id'      => fake()->uuid(),
            'moderation_type' => 'reported',
            'action_taken'    => 'removed',
            'reason'          => 'Violated community guidelines.',
        ], $overrides);
    }

    // =========================================================================
    // GET /api/v1/admin/moderation
    // =========================================================================

    /** @test */
    public function moderator_can_list_moderation_log(): void
    {
        $mod = $this->makeModerator();
        Sanctum::actingAs($mod);

        $this->makeLogEntry(['moderator_id' => $mod->id]);
        $this->makeLogEntry(['moderator_id' => $mod->id]);

        $this->getJson('/api/v1/admin/moderation')
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function log_can_be_filtered_by_content_type(): void
    {
        $mod = $this->makeModerator();
        Sanctum::actingAs($mod);

        $this->makeLogEntry(['moderator_id' => $mod->id, 'content_type' => 'message']);
        $this->makeLogEntry(['moderator_id' => $mod->id, 'content_type' => 'message']);
        $this->makeLogEntry(['moderator_id' => $mod->id, 'content_type' => 'project']);

        $this->getJson('/api/v1/admin/moderation?content_type=message')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function log_can_be_filtered_by_action_taken(): void
    {
        $mod = $this->makeModerator();
        Sanctum::actingAs($mod);

        $this->makeLogEntry(['moderator_id' => $mod->id, 'action_taken' => 'removed']);
        $this->makeLogEntry(['moderator_id' => $mod->id, 'action_taken' => 'approved']);

        $this->getJson('/api/v1/admin/moderation?action_taken=removed')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function regular_user_cannot_list_moderation_log(): void
    {
        Sanctum::actingAs($this->makeRegularUser());

        $this->getJson('/api/v1/admin/moderation')
            ->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/admin/moderation')
            ->assertStatus(401);
    }

    // =========================================================================
    // POST /api/v1/admin/moderation
    // =========================================================================

    /** @test */
    public function moderator_can_log_a_moderation_action(): void
    {
        $mod = $this->makeModerator();
        $contentId = fake()->uuid();
        Sanctum::actingAs($mod);

        $this->postJson('/api/v1/admin/moderation', $this->validLogPayload([
            'content_id' => $contentId,
        ]))->assertStatus(201)
            ->assertJsonStructure(['status', 'data' => ['id', 'content_type', 'action_taken']]);

        $this->assertDatabaseHas('content_moderation', [
            'moderator_id'    => $mod->id,
            'content_type'    => 'message',
            'content_id'      => $contentId,
            'moderation_type' => 'reported',
            'action_taken'    => 'removed',
        ]);
    }

    /** @test */
    public function logging_moderation_action_also_writes_admin_action_log(): void
    {
        $mod = $this->makeModerator();
        Sanctum::actingAs($mod);

        $this->postJson('/api/v1/admin/moderation', $this->validLogPayload())
            ->assertStatus(201);

        $this->assertDatabaseHas('admin_actions', [
            'admin_id'    => $mod->id,
            'action_type' => 'moderation_action_logged',
        ]);
    }

    /** @test */
    public function content_type_is_required(): void
    {
        Sanctum::actingAs($this->makeModerator());

        $payload = $this->validLogPayload();
        unset($payload['content_type']);

        $this->postJson('/api/v1/admin/moderation', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['content_type']);
    }

    /** @test */
    public function moderation_type_is_required(): void
    {
        Sanctum::actingAs($this->makeModerator());

        $payload = $this->validLogPayload();
        unset($payload['moderation_type']);

        $this->postJson('/api/v1/admin/moderation', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['moderation_type']);
    }

    /** @test */
    public function action_taken_is_required(): void
    {
        Sanctum::actingAs($this->makeModerator());

        $payload = $this->validLogPayload();
        unset($payload['action_taken']);

        $this->postJson('/api/v1/admin/moderation', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['action_taken']);
    }

    /** @test */
    public function regular_user_cannot_log_moderation_action(): void
    {
        Sanctum::actingAs($this->makeRegularUser());

        $this->postJson('/api/v1/admin/moderation', $this->validLogPayload())
            ->assertStatus(403);
    }
}
