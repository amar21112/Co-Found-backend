<?php

namespace Tests\Feature\Verification;

use App\Models\IdentityVerification;
use App\Models\User;
use App\Models\VerificationAttempt;
use App\Repositories\Contracts\IdentityVerificationRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IdentityVerificationTest extends TestCase
{
    use RefreshDatabase;

    private const OCR_URL = 'https://ocr.example.com';

    // -------------------------------------------------------------------------
    // OCR response stubs — match the exact ScanResponse shape from api.py
    // -------------------------------------------------------------------------

    private static function decodedStub(array $overrides = []): array
    {
        return array_merge([
            'valid'               => true,
            'national_id'         => '29901011234567',
            'birth_date'          => '01/01/1999',
            'gender'              => 'ذكر',
            'gender_en'           => 'male',
            'governorate'         => 'القاهرة',
            'governorate_code'    => '01',
            'nationality'         => 'مصري',
            'nationality_en'      => 'Egyptian',
            'expiry_date'         => '01/06/2033',
            'expiry_date_en'      => '01/06/2033',
            'expiry_is_permanent' => false,
            'full_name_on_card'   => 'أحمد محمد حسن',
            'sequence'            => '1234',
            'checksum_digit'      => '7',
            'century'             => '١٩٠٠',
            'birth_year'          => '1999',
            'birth_month'         => '01',
            'birth_day'           => '01',
            'segments'            => null,
            'error'               => null,
        ], $overrides);
    }

    private static function scanSuccess(array $decodedOverrides = []): array
    {
        return [
            'success'             => true,
            'processing_time_ms'  => 2100.0,
            'ocr_tokens'          => [
                ['text' => '29901011234567', 'confidence' => 0.97, 'confidence_pct' => 97],
                ['text' => 'أحمد محمد حسن', 'confidence' => 0.91, 'confidence_pct' => 91],
            ],
            'ocr_text_count'      => 2,
            'all_extracted_digits'=> '29901011234567',
            'national_id'         => '29901011234567',
            'decoded'             => self::decodedStub($decodedOverrides),
            'error'               => null,
        ];
    }

    private static function scanNotFound(): array
    {
        return [
            'success'             => false,
            'processing_time_ms'  => 1800.0,
            'ocr_tokens'          => [],
            'ocr_text_count'      => 0,
            'all_extracted_digits'=> null,
            'national_id'         => null,
            'decoded'             => null,
            'error'               => 'OCR لم يستخرج أي نص — تأكد من جودة الصورة وإضاءتها',
        ];
    }

    // =========================================================================
    // Setup
    // =========================================================================

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        Config::set('services.ocr.url',     self::OCR_URL);
        Config::set('services.ocr.secret',  'test-secret');
        Config::set('services.ocr.timeout', 10);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge(
            ['password' => Hash::make('Secret123!')],
            $overrides,
        ));
    }

    private function actingAsUser(): User
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);
        return $user;
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'id_card_image_front' => UploadedFile::fake()->image('front.jpg', 800, 600),
            'id_card_image_back'  => UploadedFile::fake()->image('back.jpg',  800, 600),
            'submission_method'   => 'webcam',
        ], $overrides);
    }

    private function makeVerification(User $user, string $status = 'pending'): IdentityVerification
    {
        return IdentityVerification::factory()->create([
            'user_id'             => $user->id,
            'verification_status' => $status,
        ]);
    }

    private function fakeScan(array $body, int $status = 200): void
    {
        Http::fake([self::OCR_URL . '/scan' => Http::response($body, $status)]);
    }

    private function fakeConnectionFailure(): void
    {
        Http::fake([
            self::OCR_URL . '/scan' => function () {
                throw new ConnectionException('Connection refused');
            },
        ]);
    }

    /**
     * Read the id_card_number from the DB.
     * The repository stores it with encrypt(), so we decrypt before asserting.
     */
    private function decryptNid(IdentityVerification $v): ?string
    {
        return $v->id_card_number ? decrypt($v->id_card_number) : null;
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

        $this->getJson('/api/v1/verification')->assertStatus(404);
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

        $data = $this->getJson('/api/v1/verification')
            ->assertStatus(200)
            ->json('data');

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
    // POST /api/v1/verification — happy path
    // =========================================================================

    /** @test */
    public function user_can_submit_verification_with_images_only(): void
    {
        $this->fakeScan(self::scanSuccess());
        $user = $this->actingAsUser();

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(201)
            ->assertJsonPath('data.verification_status', 'pending');

        $this->assertDatabaseHas('identity_verifications', [
            'user_id'             => $user->id,
            'verification_status' => 'pending',
        ]);

        $this->assertDatabaseHas('verification_attempts', [
            'user_id' => $user->id,
            'result'  => 'success',
        ]);
    }

    /** @test */
    public function document_images_are_stored_on_local_disk(): void
    {
        $this->fakeScan(self::scanSuccess());
        $user = $this->actingAsUser();

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(201);

        $v = IdentityVerification::where('user_id', $user->id)->firstOrFail();

        Storage::disk('local')->assertExists($v->id_card_image_front);
        Storage::disk('local')->assertExists($v->id_card_image_back);
    }

    // =========================================================================
    // POST /api/v1/verification — OCR field mapping
    // =========================================================================

    /** @test */
    public function ocr_fills_all_card_fields_from_decoded_block(): void
    {
        $this->fakeScan(self::scanSuccess());
        $user = $this->actingAsUser();

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(201);

        $v = IdentityVerification::where('user_id', $user->id)->firstOrFail();

        // id_card_number is stored encrypted — decrypt before asserting
        $this->assertEquals('29901011234567', $this->decryptNid($v));
        $this->assertEquals('1999-01-01',     $v->date_of_birth->toDateString());
        $this->assertEquals('أحمد محمد حسن', $v->full_name_on_card);
        $this->assertEquals('مصري',           $v->nationality);
        $this->assertEquals('2033-06-01',     $v->expiry_date->toDateString());
    }

    /** @test */
    public function expiry_date_is_null_for_permanent_cards(): void
    {
        $this->fakeScan(self::scanSuccess([
            'expiry_date'         => 'دائمة',
            'expiry_is_permanent' => true,
        ]));
        $user = $this->actingAsUser();

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(201);

        $this->assertNull(
            IdentityVerification::where('user_id', $user->id)->firstOrFail()->expiry_date
        );
    }

    /** @test */
    public function full_name_is_empty_when_heuristic_finds_no_name(): void
    {
        // OCR found no name → OcrEnricher sets fullNameOnCard = null
        // Service coerces null → '' to satisfy StoredVerificationDTO (non-nullable string)
        $this->fakeScan(self::scanSuccess(['full_name_on_card' => null]));
        $user = $this->actingAsUser();

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(201);

        $this->assertSame(
            '',
            IdentityVerification::where('user_id', $user->id)->firstOrFail()->full_name_on_card
        );
    }

    /** @test */
    public function nationality_is_always_egyptian_for_national_id_cards(): void
    {
        $this->fakeScan(self::scanSuccess());
        $user = $this->actingAsUser();

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(201);

        $this->assertEquals(
            'مصري',
            IdentityVerification::where('user_id', $user->id)->firstOrFail()->nationality
        );
    }

    /** @test */
    public function ocr_metadata_is_stored_in_liveness_check_data(): void
    {
        $this->fakeScan(self::scanSuccess());
        $user = $this->actingAsUser();

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(201);

        $ocr = IdentityVerification::where('user_id', $user->id)->firstOrFail()
            ->liveness_check_data['ocr'];

        $this->assertTrue($ocr['attempted']);
        $this->assertTrue($ocr['success']);
        $this->assertEquals('29901011234567', $ocr['national_id']);
        $this->assertEquals(2100.0, $ocr['processing_ms']);
    }

    /** @test */
    public function invalid_birth_date_from_nid_is_stored_as_empty_string(): void
    {
        // parseBirthDate rejects future dates → dateOfBirth = null → coerced to ''
        $futureDate = now()->addYear()->format('d/m/Y');

        $this->fakeScan(self::scanSuccess(['birth_date' => $futureDate]));
        $user = $this->actingAsUser();

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(201);

        // date_of_birth cast is 'date' — empty string '' is stored as null by Laravel date cast
        $this->assertNull(
            IdentityVerification::where('user_id', $user->id)->firstOrFail()->date_of_birth
        );
    }

    // =========================================================================
    // POST /api/v1/verification — OCR graceful degradation
    // =========================================================================

    /** @test */
    public function submission_succeeds_when_ocr_finds_no_nid(): void
    {
        $this->fakeScan(self::scanNotFound());
        $user = $this->actingAsUser();

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(201);

        $v = IdentityVerification::where('user_id', $user->id)->firstOrFail();

        $this->assertNull($this->decryptNid($v));
        $this->assertSame('', $v->full_name_on_card);
        $this->assertNull($v->date_of_birth);
        $this->assertFalse($v->liveness_check_data['ocr']['success']);
    }

    /** @test */
    public function submission_succeeds_when_ocr_service_is_unreachable(): void
    {
        $this->fakeConnectionFailure();
        $user = $this->actingAsUser();

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(201);

        $ocr = IdentityVerification::where('user_id', $user->id)->firstOrFail()
            ->liveness_check_data['ocr'];

        $this->assertTrue($ocr['attempted']);
        $this->assertFalse($ocr['success']);
    }

    /** @test */
    public function submission_succeeds_when_ocr_service_returns_server_error(): void
    {
        $this->fakeScan(['detail' => 'Internal server error.'], 500);
        $user = $this->actingAsUser();

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(201);

        $ocr = IdentityVerification::where('user_id', $user->id)->firstOrFail()
            ->liveness_check_data['ocr'];

        $this->assertFalse($ocr['success']);
        $this->assertEquals('service_error', $ocr['error']);
    }

    /** @test */
    public function ocr_failure_is_logged_as_error(): void
    {
        $this->fakeScan(['detail' => 'Internal server error.'], 500);
        Log::spy();

        $this->actingAsUser();
        $this->postJson('/api/v1/verification', $this->validPayload());

        Log::shouldHaveReceived('error')
            ->withArgs(fn($msg) => str_contains($msg, 'OCR enrichment failed'))
            ->once();
    }

    /** @test */
    public function ocr_not_found_is_logged_as_info(): void
    {
        $this->fakeScan(self::scanNotFound());
        Log::spy();

        $this->actingAsUser();
        $this->postJson('/api/v1/verification', $this->validPayload());

        Log::shouldHaveReceived('info')
            ->withArgs(fn($msg) => str_contains($msg, 'OCR enrichment: NID not detected'))
            ->once();
    }

    /** @test */
    public function ocr_success_is_logged_as_info(): void
    {
        $this->fakeScan(self::scanSuccess());
        Log::spy();

        $this->actingAsUser();
        $this->postJson('/api/v1/verification', $this->validPayload());

        Log::shouldHaveReceived('info')
            ->withArgs(fn($msg) => str_contains($msg, 'OCR enrichment: NID extracted'))
            ->once();
    }

    // =========================================================================
    // POST /api/v1/verification — business rules
    // =========================================================================

    /** @test */
    public function cannot_submit_when_verification_is_already_pending(): void
    {
        Http::fake();
        $user = $this->makeUser();
        $this->makeVerification($user);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(409);

        $this->assertDatabaseHas('verification_attempts', [
            'user_id' => $user->id,
            'result'  => 'failure',
        ]);
    }

    /** @test */
    public function cannot_submit_when_verification_is_under_review(): void
    {
        Http::fake();
        $user = $this->makeUser();
        $this->makeVerification($user, 'under_review');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/verification', $this->validPayload())->assertStatus(409);
    }

    /** @test */
    public function cannot_submit_when_already_verified(): void
    {
        Http::fake();
        $user = $this->makeUser();
        $this->makeVerification($user, 'verified');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/verification', $this->validPayload())->assertStatus(409);
    }

    /** @test */
    public function can_resubmit_after_rejection(): void
    {
        $this->fakeScan(self::scanSuccess());
        $user = $this->makeUser();
        $this->makeVerification($user, 'rejected');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(201)
            ->assertJsonPath('data.verification_status', 'pending');

        $this->assertDatabaseCount('identity_verifications', 1);
    }

    /** @test */
    public function resubmission_resets_rejection_reason(): void
    {
        $this->fakeScan(self::scanSuccess());
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
        Http::fake();
        $user = $this->makeUser();
        VerificationAttempt::factory()->count(3)->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/verification', $this->validPayload())->assertStatus(429);
    }

    /** @test */
    public function ocr_is_not_called_when_attempt_limit_is_reached(): void
    {
        Http::fake();
        $user = $this->makeUser();
        VerificationAttempt::factory()->count(3)->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/verification', $this->validPayload());

        Http::assertNothingSent();
    }

    /** @test */
    public function ocr_is_not_called_when_status_guard_blocks_submission(): void
    {
        Http::fake();
        $user = $this->makeUser();
        $this->makeVerification($user);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/verification', $this->validPayload());

        Http::assertNothingSent();
    }

    /** @test */
    public function cannot_submit_if_nid_already_verified_by_another_user(): void
    {
        // OCR returns '29901011234567' — use that same NID for the existing hash
        $this->fakeScan(self::scanSuccess());

        $otherUser = $this->makeUser();
        $hash = app(IdentityVerificationRepositoryInterface::class)
            ->hashCardNumber('29901011234567');

        IdentityVerification::factory()->create([
            'user_id'             => $otherUser->id,
            'verification_status' => 'verified',
            'id_card_number_hash' => $hash,
        ]);

        $user = $this->actingAsUser();

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(409);

        $this->assertDatabaseHas('verification_attempts', [
            'user_id'        => $user->id,
            'result'         => 'failure',
            'failure_reason' => 'duplicate_id_card_number',
        ]);
    }

    /** @test */
    public function can_resubmit_with_same_card_if_own_previous_was_rejected(): void
    {
        // OCR returns '29901011234567' — hash the same NID for the own rejected record
        $this->fakeScan(self::scanSuccess());

        $user = $this->makeUser();
        $hash = app(IdentityVerificationRepositoryInterface::class)
            ->hashCardNumber('29901011234567');

        IdentityVerification::factory()->create([
            'user_id'             => $user->id,
            'verification_status' => 'rejected',
            'id_card_number_hash' => $hash,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/verification', $this->validPayload())
            ->assertStatus(201);
    }

    // =========================================================================
    // POST /api/v1/verification — request validation
    // =========================================================================

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
    public function submission_method_is_required(): void
    {
        Sanctum::actingAs($this->makeUser());

        $payload = $this->validPayload();
        unset($payload['submission_method']);

        $this->postJson('/api/v1/verification', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['submission_method']);
    }

    /** @test */
    public function invalid_submission_method_is_rejected(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/v1/verification', $this->validPayload([
            'submission_method' => 'fax_machine',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['submission_method']);
    }

    /** @test */
    public function card_data_sent_by_frontend_is_ignored(): void
    {
        $this->fakeScan(self::scanSuccess());
        $user = $this->actingAsUser();

        // Frontend attempts to supply card data directly — request ignores all of it
        $this->postJson('/api/v1/verification', $this->validPayload([
            'id_card_number'    => 'CLIENT_NID',
            'full_name_on_card' => 'Wrong Name',
            'date_of_birth'     => '1985-01-01',
            'nationality'       => 'British',
        ]))->assertStatus(201);

        $v = IdentityVerification::where('user_id', $user->id)->firstOrFail();

        // All values must come from OCR, not from the client payload
        $this->assertEquals('29901011234567', $this->decryptNid($v));
        $this->assertEquals('أحمد محمد حسن', $v->full_name_on_card);
        $this->assertEquals('1999-01-01',     $v->date_of_birth->toDateString());
        $this->assertEquals('مصري',           $v->nationality);
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
}
