<?php

namespace Tests\Feature\File;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge(
            ['password' => Hash::make('Secret123')],
            $overrides
        ));
    }

    private function makeConversation(User $creator, User $participant): Conversation
    {
        $conv = Conversation::factory()->create([
            'created_by'        => $creator->id,
            'conversation_type' => 'direct',
        ]);

        ConversationParticipant::factory()->create([
            'conversation_id' => $conv->id,
            'user_id'         => $creator->id,
            'is_admin'        => true,
        ]);

        ConversationParticipant::factory()->create([
            'conversation_id' => $conv->id,
            'user_id'         => $participant->id,
            'is_admin'        => false,
        ]);

        return $conv;
    }

    private function makeFile(User $uploader, array $overrides = []): File
    {
        return File::factory()->create(array_merge([
            'uploader_id'      => $uploader->id,
            'upload_completed' => true,
        ], $overrides));
    }

    // =========================================================================
    // POST /api/v1/files  (upload)
    // =========================================================================

    /** @test */
    public function verified_user_can_upload_a_file(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create('document.pdf', 512, 'application/pdf');

        $this->postJson('/api/v1/files', ['file' => $file])
            ->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'file_name', 'file_size', 'mime_type']]);

        $this->assertDatabaseHas('files', [
            'uploader_id' => $user->id,
            'file_name'   => 'document.pdf',
        ]);
    }

    /** @test */
    public function uploading_identical_file_returns_existing_record(): void
    {
        $user    = $this->makeUser();
        Sanctum::actingAs($user);

        $fakeFile = UploadedFile::fake()->create('same.pdf', 100, 'application/pdf');

        $first  = $this->postJson('/api/v1/files', ['file' => $fakeFile])->assertStatus(201);
        $second = $this->postJson('/api/v1/files', ['file' => $fakeFile])->assertStatus(201);

        // Same file ID returned — deduplication
        $this->assertEquals($first->json('data.id'), $second->json('data.id'));
        $this->assertDatabaseCount('files', 1);
    }

    /** @test */
    public function file_field_is_required(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/v1/files')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function file_size_limit_is_enforced(): void
    {
        Sanctum::actingAs($this->makeUser());

        // Create a file that exceeds max size (e.g., over 50MB)
        $largeFile = UploadedFile::fake()->create('huge.pdf', 51201, 'application/pdf');

        $this->postJson('/api/v1/files', ['file' => $largeFile])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function guest_cannot_upload_file(): void
    {
        Sanctum::actingAs(User::factory()->guest()->create());

        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $this->postJson('/api/v1/files', ['file' => $file])
            ->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_upload(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $this->postJson('/api/v1/files', ['file' => $file])
            ->assertStatus(401);
    }

    // =========================================================================
    // GET /api/v1/files/{file}  (show)
    // =========================================================================

    /** @test */
    public function uploader_can_view_their_file(): void
    {
        $user = $this->makeUser();
        $file = $this->makeFile($user);
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/files/$file->id")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $file->id);
    }

    /** @test */
    public function show_returns_404_for_unknown_file(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->getJson('/api/v1/files/00000000-0000-0000-0000-000000000001')
            ->assertStatus(404);
    }

    // =========================================================================
    // DELETE /api/v1/files/{file}  (delete)
    // =========================================================================

    /** @test */
    public function uploader_can_delete_their_file(): void
    {
        $user = $this->makeUser();
        $file = $this->makeFile($user);
        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/files/$file->id")
            ->assertStatus(200);

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }

    /** @test */
    public function user_cannot_delete_another_users_file(): void
    {
        $owner = $this->makeUser();
        $file  = $this->makeFile($owner);
        Sanctum::actingAs($this->makeUser());

        $this->deleteJson("/api/v1/files/$file->id")
            ->assertStatus(403);
    }

    // =========================================================================
    // POST /api/v1/conversations/{id}/files  (share in conversation)
    // =========================================================================

    /** @test */
    public function participant_can_share_a_file_in_conversation(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $conv  = $this->makeConversation($userA, $userB);
        $file  = $this->makeFile($userA);
        Sanctum::actingAs($userA);

        $this->postJson("/api/v1/conversations/$conv->id/files", [
            'file_id' => $file->id,
        ])->assertStatus(201);

        $this->assertDatabaseHas('shared_files', [
            'conversation_id' => $conv->id,
            'file_id'         => $file->id,
        ]);
    }

    /** @test */
    public function non_participant_cannot_share_file_in_conversation(): void
    {
        $userA    = $this->makeUser();
        $userB    = $this->makeUser();
        $outsider = $this->makeUser();
        $conv     = $this->makeConversation($userA, $userB);
        $file     = $this->makeFile($outsider);
        Sanctum::actingAs($outsider);

        $this->postJson("/api/v1/conversations/$conv->id/files", [
            'file_id' => $file->id,
        ])->assertStatus(403);
    }

    /** @test */
    public function file_id_must_exist_to_share(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $conv  = $this->makeConversation($userA, $userB);
        Sanctum::actingAs($userA);

        $this->postJson("/api/v1/conversations/$conv->id/files", [
            'file_id' => '00000000-0000-0000-0000-000000000001',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['file_id']);
    }

    // =========================================================================
    // GET /api/v1/conversations/{id}/files  (list shared)
    // =========================================================================

    /** @test */
    public function participant_can_list_shared_files_in_conversation(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $conv  = $this->makeConversation($userA, $userB);
        Sanctum::actingAs($userA);

        $this->getJson("/api/v1/conversations/$conv->id/files")
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function non_participant_cannot_list_shared_files(): void
    {
        $userA    = $this->makeUser();
        $userB    = $this->makeUser();
        $outsider = $this->makeUser();
        $conv     = $this->makeConversation($userA, $userB);
        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/conversations/$conv->id/files")
            ->assertStatus(403);
    }
}
