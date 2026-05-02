<?php

namespace Tests\Feature\Verification;

use App\Models\IdentityVerification;
use App\Models\User;
use App\Models\VerificationAttempt;
use App\Repositories\Contracts\IdentityVerificationRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IdentityVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge(
            ['password' => Hash::make('Secret123')],
            $overrides
        ));
    }

    private function validPayload(): array
    {
        return [
            'id_card_image_front' => UploadedFile::fake()->image('front.jpg', 800, 600),
            'id_card_image_back'  => UploadedFile::fake()->image('back.jpg',  800, 600),
            'id_card_number'      => 'A123456789',
            'full_name_on_card'   => 'John Doe',
            'date_of_birth'       => '1990-06-15',
            'nationality'         => 'Egyptian',
            'expiry_date'         => '2028-12-31',
            'submission_method'   => 'webcam',
        ];
    }

    private function makeVerification(User $user, string $status = 'pending'): IdentityVerification
    {
        return IdentityVerification::factory()->create([
            'user_id'             => $user->id,
            'verification_status' => $status,
        ]);
    }

    // =========================================================================
    // GET /api/v1/verification
    // =========================================================================

    /** @test */
    public function user_can_view_their_verification_status(): void
    {
        $user = $this->makeUser();
        $this->makeVerification($user);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/verification')
            ->assertStatus(200)
            ->assertJsonPath('data.verification_status', 'pending')
            ->assertJsonStructure([
                'data' => [
                    'id', 'verification_status', 'status_label',
                    'full_name_on_card', 'date_of_birth',
                    'liveness_check_passed', 'created_at',
                ],
            ]);
    }

    /** @test */
    public function returns_404_when_no_verification_submitted(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->getJson('/api/v1/verification')
            ->assertStatus(404);
    }

    /** @test */
    public function rejection_reason_is_shown_when_rejected(): void
    {
        $user = $this->makeUser();
        IdentityVerification::factory()->create([
            'user_id'             => $user->id,
            'verification_status' => 'rejected',
            'rejection_reason'    => 'Document image is too blurry.',
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/verification')
            ->assertStatus(200)
            ->assertJsonPath('data.rejection_reason', 'Document image is too blurry.');
    }

    /** @test */
    public function rejection_reason_is_null_when_pending(): void
    {
        $user = $this->makeUser();
        $this->makeVerification($user);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/verification')
            ->assertStatus(200)
            ->assertJsonPath('data.rejection_reason', null);
    }

    /** @test */
    public function document_image_paths_are_not_exposed_to_client(): void
    {
        $user = $this->makeUser();
        $this->makeVerification($user);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/verification')
            ->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayNotHasKey('id_card_image_front', $data);
        $this->assertArrayNotHasKey('id_card_image_back', $data);
        $this->assertArrayNotHasKey('id_card_number', $data);
    }

    /** @test */
    public function unauthenticated_user_cannot_view_verification(): void
    {
        $this->getJson('/api/v1/verification')->assertStatus(401);
    }

    // =========================================================================
    // POST /api/v1/verification
    // =========================================================================

    /** @test */
    public function verified_user_can_submit_identity_verification(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(201)
            ->assertJsonPath('data.verification_status', 'pending')
            ->assertJsonPath('data.full_name_on_card', 'John Doe');

        $this->assertDatabaseHas('identity_verifications', [
            'user_id'             => $user->id,
            'full_name_on_card'   => 'John Doe',
            'verification_status' => 'pending',
        ]);

        // Attempt logged
        $this->assertDatabaseHas('verification_attempts', [
            'user_id' => $user->id,
            'result'  => 'success',
        ]);
    }

    /** @test */
    public function document_images_are_stored_on_local_disk(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(201);

        $verification = IdentityVerification::where('user_id', $user->id)->first();

        Storage::disk('local')->assertExists($verification->id_card_image_front);
        Storage::disk('local')->assertExists($verification->id_card_image_back);
    }

    /** @test */
    public function cannot_submit_when_verification_is_already_pending(): void
    {
        $user = $this->makeUser();
        $this->makeVerification($user);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(409);

        // Failure attempt logged
        $this->assertDatabaseHas('verification_attempts', [
            'user_id' => $user->id,
            'result'  => 'failure',
        ]);
    }

    /** @test */
    public function cannot_submit_when_verification_is_under_review(): void
    {
        $user = $this->makeUser();
        $this->makeVerification($user, 'under_review');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(409);
    }

    /** @test */
    public function cannot_submit_when_already_verified(): void
    {
        $user = $this->makeUser();
        $this->makeVerification($user, 'verified');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(409);
    }

    /** @test */
    public function can_resubmit_after_rejection(): void
    {
        $user = $this->makeUser();
        $this->makeVerification($user, 'rejected');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(201)
            ->assertJsonPath('data.verification_status', 'pending');

        // Status reset to pending — same record
        $this->assertDatabaseCount('identity_verifications', 1);
    }

    /** @test */
    public function resubmission_resets_rejection_reason(): void
    {
        $user = $this->makeUser();
        IdentityVerification::factory()->create([
            'user_id'             => $user->id,
            'verification_status' => 'rejected',
            'rejection_reason'    => 'Previous rejection reason.',
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(201)
            ->assertJsonPath('data.rejection_reason', null);
    }

    /** @test */
    public function blocked_after_max_attempts(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        // Exhaust all 3 attempts
        VerificationAttempt::factory()->count(3)->create(['user_id' => $user->id]);

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(429);
    }

    /** @test */
    public function front_image_is_required(): void
    {
        Sanctum::actingAs($this->makeUser());

        $payload = $this->validPayload();
        unset($payload['id_card_image_front']);

        $this->postJson('/api/v1/verification', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['id_card_image_front']);
    }

    /** @test */
    public function back_image_is_required(): void
    {
        Sanctum::actingAs($this->makeUser());

        $payload = $this->validPayload();
        unset($payload['id_card_image_back']);

        $this->postJson('/api/v1/verification', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['id_card_image_back']);
    }

    /** @test */
    public function full_name_on_card_is_required(): void
    {
        Sanctum::actingAs($this->makeUser());

        $payload = $this->validPayload();
        unset($payload['full_name_on_card']);

        $this->postJson('/api/v1/verification', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['full_name_on_card']);
    }

    /** @test */
    public function date_of_birth_must_be_in_the_past(): void
    {
        Sanctum::actingAs($this->makeUser());

        $payload                 = $this->validPayload();
        $payload['date_of_birth']= now()->addDay()->toDateString();

        $this->postJson('/api/v1/verification', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date_of_birth']);
    }

    /** @test */
    public function invalid_submission_method_is_rejected(): void
    {
        Sanctum::actingAs($this->makeUser());

        $payload                      = $this->validPayload();
        $payload['submission_method'] = 'fax_machine';

        $this->postJson('/api/v1/verification', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['submission_method']);
    }

    /** @test */
    public function guest_cannot_submit_verification(): void
    {
        $guest = User::factory()->guest()->create();
        Sanctum::actingAs($guest);

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_submit(): void
    {
        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(401);
    }

    /** @test */
    public function cannot_submit_if_id_card_number_is_already_verified_by_another_user(): void
    {
        // Another user already verified with this card number
        $otherUser = $this->makeUser();
        $hash = app(IdentityVerificationRepositoryInterface::class)
            ->hashCardNumber('A123456789');

        IdentityVerification::factory()->create([
            'user_id'              => $otherUser->id,
            'verification_status'  => 'verified',
            'id_card_number_hash'  => $hash,
        ]);

        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(409);

        // Failure attempt logged
        $this->assertDatabaseHas('verification_attempts', [
            'user_id'        => $user->id,
            'result'         => 'failure',
            'failure_reason' => 'duplicate_id_card_number',
        ]);
    }

    /** @test */
    public function can_resubmit_with_same_card_number_if_own_previous_rejected(): void
    {
        $user = $this->makeUser();
        $hash = app(IdentityVerificationRepositoryInterface::class)
            ->hashCardNumber('A123456789');

        // Own rejected verification — resubmission allowed
        IdentityVerification::factory()->create([
            'user_id'             => $user->id,
            'verification_status' => 'rejected',
            'id_card_number_hash' => $hash,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(201);
    }
}
