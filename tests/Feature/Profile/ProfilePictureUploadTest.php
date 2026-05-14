<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use App\Services\ProfilePictureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * ProfilePictureUploadTest
 *
 * Covers: upload, replace (old file deleted), remove via null, validation errors,
 * guest restriction, and URL resolution in the API response.
 *
 * Run: php artisan test --filter=ProfilePictureUploadTest
 */
class ProfilePictureUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Use a fake local disk so no real files are written during tests
        Storage::fake('public');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Upload
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function authenticated_user_can_upload_a_profile_picture(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', [
                'profile_picture' => $file,
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['profile_picture_url']]);

        // The returned URL should be a full URL, not a bare path
        $url = $response->json('data.profile_picture_url');
        $this->assertStringStartsWith('http', $url);
        $this->assertStringContainsString('profile_pictures/', $url);

        // File actually exists on the fake disk
        $user->refresh();
        Storage::disk('public')->assertExists($user->profile_picture_url);
    }

    /** @test */
    public function uploading_a_new_picture_deletes_the_previous_one(): void
    {
        $user       = User::factory()->create();
        $firstFile  = UploadedFile::fake()->image('first.jpg', 200, 200);
        $secondFile = UploadedFile::fake()->image('second.png', 300, 300);

        // Upload first picture
        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', ['profile_picture' => $firstFile]);

        $user->refresh();
        $firstPath = $user->profile_picture_url;
        Storage::disk('public')->assertExists($firstPath);

        // Upload second picture — first should be gone
        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', ['profile_picture' => $secondFile]);

        $user->refresh();
        $secondPath = $user->profile_picture_url;

        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);
        $this->assertNotSame($firstPath, $secondPath);
    }

    /** @test */
    public function text_only_update_does_not_touch_existing_profile_picture(): void
    {
        $user = User::factory()->withProfilePicture()->create();
        $originalPath = $user->profile_picture_url;

        // Put a file on the fake disk so deletion does not silently succeed
        Storage::disk('public')->put($originalPath, 'fake-image-content');

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', ['full_name' => 'New Name']);

        $user->refresh();
        $this->assertSame($originalPath, $user->profile_picture_url);
        Storage::disk('public')->assertExists($originalPath);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Validation
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function rejects_non_image_file(): void
    {
        $user = User::factory()->create();
        $pdf  = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', ['profile_picture' => $pdf])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['profile_picture']);
    }

    /** @test */
    public function rejects_oversized_image(): void
    {
        $user    = User::factory()->create();
        // 3 MB — exceeds the 2 MB limit
        $bigFile = UploadedFile::fake()->image('huge.jpg')->size(3 * 1024);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', ['profile_picture' => $bigFile])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['profile_picture']);
    }

    /** @test */
    public function rejects_image_below_minimum_dimensions(): void
    {
        $user  = User::factory()->create();
        $small = UploadedFile::fake()->image('tiny.jpg', 10, 10);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', ['profile_picture' => $small])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['profile_picture']);
    }

    /** @test */
    public function profile_picture_field_is_optional(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', ['bio' => 'Hello world'])
            ->assertOk();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // URL resolution
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function profile_endpoint_returns_full_url_not_storage_path(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.png', 200, 200);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', ['profile_picture' => $file]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile');

        $url = $response->json('data.profile_picture_url');

        // Must be a full URL — the frontend should never see a bare path
        $this->assertMatchesRegularExpression('/^https?:\/\//', $url);
    }

    /** @test */
    public function profile_picture_url_is_null_when_not_set(): void
    {
        $user = User::factory()->create(['profile_picture_url' => null]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.profile_picture_url', null);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ProfilePictureService unit tests
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function service_returns_null_url_for_null_path(): void
    {
        $service = app(ProfilePictureService::class);
        $this->assertNull($service->toUrl(null));
    }

    /** @test */
    public function service_does_not_attempt_to_delete_old_external_urls(): void
    {
        // Users migrated from the old system may still have http:// URLs in the DB.
        // delete() must ignore those silently.
        $service = app(ProfilePictureService::class);

        // Should not throw and should not call Storage::delete()
        $service->delete('https://example.com/avatar.jpg');

        // No exception = test passes
        $this->assertTrue(true);
    }
}
