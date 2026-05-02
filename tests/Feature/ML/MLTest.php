<?php

namespace Tests\Feature\ML;

use App\Models\MatchFeedback;
use App\Models\MatchModel;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MLTest extends TestCase
{
    use RefreshDatabase;

    private const ML_SECRET = 'test-ml-secret-1234';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.ml.secret', self::ML_SECRET);
    }

    // ── Auth helpers ──────────────────────────────────────────────────────────

    private function mlHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . self::ML_SECRET];
    }

    private function makeIngestPayload(array $overrides = []): array
    {
        $user    = User::factory()->create();
        $matched = User::factory()->create();

        return array_merge([
            'user_id'             => $user->id,
            'match_type'          => 'collaborator',
            'matched_user_id'     => $matched->id,
            'matched_project_id'  => null,
            'compatibility_score' => 0.82,
            'match_reasons'       => [
                'skill_overlap'          => 0.6,
                'complementarity'        => 0.8,
                'location_match'         => 1,
                'both_identity_verified' => 1,
            ],
            'expires_at' => now()->addDays(30)->toIso8601String(),
        ], $overrides);
    }

    private function makeProjectIngestPayload(array $overrides = []): array
    {
        $user    = User::factory()->create();
        $project = Project::factory()->create();

        return array_merge([
            'user_id'             => $user->id,
            'match_type'          => 'project',
            'matched_user_id'     => null,
            'matched_project_id'  => $project->id,
            'compatibility_score' => 0.74,
            'match_reasons'       => [
                'skill_coverage'         => 0.75,
                'team_openness'          => 0.5,
                'project_accepting'      => 1,
                'user_identity_verified' => 1,
            ],
            'expires_at' => now()->addDays(30)->toIso8601String(),
        ], $overrides);
    }

    // =========================================================================
    // Auth — all endpoints
    // =========================================================================

    /** @test */
    public function all_ml_endpoints_reject_missing_token(): void
    {
        $this->getJson('/api/v1/ml/dataset/stats')         ->assertStatus(401);
        $this->postJson('/api/v1/ml/dataset/generate')     ->assertStatus(401);
        $this->getJson('/api/v1/ml/dataset/export')        ->assertStatus(401);
        $this->postJson('/api/v1/ml/matches/ingest')       ->assertStatus(401);
    }

    /** @test */
    public function all_ml_endpoints_reject_wrong_token(): void
    {
        $headers = ['Authorization' => 'Bearer wrong-token'];

        $this->getJson('/api/v1/ml/dataset/stats',       $headers)->assertStatus(401);
        $this->postJson('/api/v1/ml/dataset/generate',  [], $headers)->assertStatus(401);
        $this->getJson('/api/v1/ml/dataset/export',      $headers)->assertStatus(401);
        $this->postJson('/api/v1/ml/matches/ingest', [], $headers)->assertStatus(401);
    }

    /** @test */
    public function sanctum_user_token_is_rejected_by_ml_endpoints(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $headers = ['Authorization' => "Bearer $token"];

        $this->getJson('/api/v1/ml/dataset/stats', $headers)->assertStatus(401);
    }

    // =========================================================================
    // GET /api/v1/ml/dataset/stats
    // =========================================================================

    /** @test */
    public function stats_returns_correct_structure_on_empty_database(): void
    {
        $this->getJson('/api/v1/ml/dataset/stats', $this->mlHeaders())
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'total_matches',
                    'by_type',
                    'total_feedback',
                    'feedback_rate',
                    'feedback_distribution',
                    'score_stats' => ['avg_score', 'min_score', 'max_score'],
                ],
            ])
            ->assertJsonPath('data.total_matches', 0)
            ->assertJsonPath('data.total_feedback', 0)
            ->assertJsonPath('data.feedback_rate', 0);
    }

    /** @test */
    public function stats_returns_accurate_counts(): void
    {
        $user = User::factory()->create();

        MatchModel::factory()->collaborator()->create(['user_id' => $user->id]);
        MatchModel::factory()->collaborator()->create(['user_id' => $user->id]);
        MatchModel::factory()->project()->create(['user_id' => $user->id]);

        $this->getJson('/api/v1/ml/dataset/stats', $this->mlHeaders())
            ->assertStatus(200)
            ->assertJsonPath('data.total_matches', 3)
            ->assertJsonPath('data.by_type.collaborator', 2)
            ->assertJsonPath('data.by_type.project', 1);
    }

    /** @test */
    public function stats_feedback_rate_is_computed_correctly(): void
    {
        $user = User::factory()->create();

        $withFeedback = MatchModel::factory()->collaborator()->create(['user_id' => $user->id]);
        MatchModel::factory()->collaborator()->create(['user_id' => $user->id]);

        MatchFeedback::create([
            'match_id'      => $withFeedback->id,
            'user_id'       => $user->id,
            'feedback_type' => 'relevant',
        ]);

        $response = $this->getJson('/api/v1/ml/dataset/stats', $this->mlHeaders())
            ->assertStatus(200);

        // 1 feedback / 2 matches = 0.5
        $this->assertEquals(0.5, $response->json('data.feedback_rate'));
        $this->assertEquals(1, $response->json('data.feedback_distribution.relevant'));
    }

    // =========================================================================
    // POST /api/v1/ml/dataset/generate
    // =========================================================================

    /** @test */
    public function generate_uses_defaults_when_no_body_is_sent(): void
    {
        $this->postJson('/api/v1/ml/dataset/generate', [], $this->mlHeaders())
            ->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data' => ['users', 'projects', 'collaborator_matches', 'project_matches'],
            ]);
    }

    /** @test */
    public function generate_respects_custom_pair_counts(): void
    {
        $response = $this->postJson('/api/v1/ml/dataset/generate', [
            'users'              => 10,
            'projects'           => 5,
            'collaborator_pairs' => 20,
            'project_pairs'      => 15,
        ], $this->mlHeaders())
            ->assertStatus(201);

        $this->assertEquals(20, $response->json('data.collaborator_matches'));
        $this->assertEquals(15, $response->json('data.project_matches'));
    }

    /** @test */
    public function generate_with_fresh_true_clears_existing_data(): void
    {
        $user = User::factory()->create();
        MatchModel::factory()->collaborator()->count(5)->create(['user_id' => $user->id]);

        $this->assertEquals(5, MatchModel::count());

        $this->postJson('/api/v1/ml/dataset/generate', [
            'users'              => 10,
            'projects'           => 5,
            'collaborator_pairs' => 10,
            'project_pairs'      => 10,
            'fresh'              => true,
        ], $this->mlHeaders())
            ->assertStatus(201);

        // Fresh wipe — old 5 are gone, only generated ones remain
        $this->assertEquals(20, MatchModel::count());
    }

    /** @test */
    public function generate_without_fresh_appends_to_existing_data(): void
    {
        $user = User::factory()->create();
        MatchModel::factory()->collaborator()->count(3)->create(['user_id' => $user->id]);

        $this->postJson('/api/v1/ml/dataset/generate', [
            'users'              => 10,
            'projects'           => 5,
            'collaborator_pairs' => 10,
            'project_pairs'      => 10,
            'fresh'              => false,
        ], $this->mlHeaders())
            ->assertStatus(201);

        // 3 original + 20 generated
        $this->assertGreaterThan(3, MatchModel::count());
    }

    /** @test */
    public function generate_rejects_invalid_pair_counts(): void
    {
        $this->postJson('/api/v1/ml/dataset/generate', [
            'users'    => 5, // below minimum of 10
            'projects' => 0, // below minimum of 5
        ], $this->mlHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['users', 'projects']);
    }

    /** @test */
    public function generate_rejects_pair_counts_above_maximum(): void
    {
        $this->postJson('/api/v1/ml/dataset/generate', [
            'collaborator_pairs' => 99999,
        ], $this->mlHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['collaborator_pairs']);
    }

    // =========================================================================
    // GET /api/v1/ml/dataset/export
    // =========================================================================

    /** @test */
    public function export_returns_404_when_no_matches_exist(): void
    {
        $this->getJson('/api/v1/ml/dataset/export', $this->mlHeaders())
            ->assertStatus(404);
    }

    /** @test */
    public function export_returns_all_matches_as_json_by_default(): void
    {
        $user = User::factory()->create();
        MatchModel::factory()->collaborator()->count(3)->create(['user_id' => $user->id]);
        MatchModel::factory()->project()->count(2)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/v1/ml/dataset/export', $this->mlHeaders())
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.type', 'all')
            ->assertJsonStructure([
                'status',
                'meta' => ['total', 'type', 'min_score', 'with_feedback_only'],
                'data' => [[
                    'id', 'match_type', 'compatibility_score',
                    'viewed', 'saved', 'action_taken',
                    'feedback_type', 'label_relevant', 'label_not_relevant',
                    'user_identity_verified', 'same_location',
                ]],
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function export_filters_by_match_type(): void
    {
        $user = User::factory()->create();
        MatchModel::factory()->collaborator()->count(2)->create(['user_id' => $user->id]);
        MatchModel::factory()->project()->count(3)->create(['user_id' => $user->id]);

        $this->getJson('/api/v1/ml/dataset/export?type=collaborator', $this->mlHeaders())
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.type', 'collaborator');

        $this->getJson('/api/v1/ml/dataset/export?type=project', $this->mlHeaders())
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 3);
    }

    /** @test */
    public function export_filters_by_minimum_score(): void
    {
        $user = User::factory()->create();
        MatchModel::factory()->collaborator()->create(['user_id' => $user->id, 'compatibility_score' => 0.90]);
        MatchModel::factory()->collaborator()->create(['user_id' => $user->id, 'compatibility_score' => 0.55]);

        $this->getJson('/api/v1/ml/dataset/export?min_score=0.80', $this->mlHeaders())
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    /** @test */
    public function export_with_feedback_only_excludes_unlabelled_rows(): void
    {
        $user = User::factory()->create();

        $withFeedback = MatchModel::factory()->collaborator()->create(['user_id' => $user->id]);
        MatchModel::factory()->collaborator()->create(['user_id' => $user->id]); // no feedback

        MatchFeedback::create([
            'match_id'      => $withFeedback->id,
            'user_id'       => $user->id,
            'feedback_type' => 'relevant',
        ]);

        $this->getJson('/api/v1/ml/dataset/export?with_feedback_only=1', $this->mlHeaders())
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    /** @test */
    public function export_label_columns_are_correct_for_relevant_feedback(): void
    {
        $user  = User::factory()->create();
        $match = MatchModel::factory()->collaborator()->create(['user_id' => $user->id]);

        MatchFeedback::create([
            'match_id'      => $match->id,
            'user_id'       => $user->id,
            'feedback_type' => 'relevant',
        ]);

        $response = $this->getJson('/api/v1/ml/dataset/export?with_feedback_only=1', $this->mlHeaders())
            ->assertStatus(200);

        $row = $response->json('data.0');
        $this->assertEquals('relevant', $row['feedback_type']);
        $this->assertEquals(1, $row['label_relevant']);
        $this->assertEquals(0, $row['label_not_relevant']);
    }

    /** @test */
    public function export_label_columns_are_correct_for_not_relevant_feedback(): void
    {
        $user  = User::factory()->create();
        $match = MatchModel::factory()->collaborator()->create(['user_id' => $user->id]);

        MatchFeedback::create([
            'match_id'      => $match->id,
            'user_id'       => $user->id,
            'feedback_type' => 'not_relevant',
        ]);

        $response = $this->getJson('/api/v1/ml/dataset/export?with_feedback_only=1', $this->mlHeaders())
            ->assertStatus(200);

        $row = $response->json('data.0');
        $this->assertEquals(0, $row['label_relevant']);
        $this->assertEquals(1, $row['label_not_relevant']);
    }

    /** @test */
    public function export_rows_without_feedback_have_empty_feedback_type_and_zero_labels(): void
    {
        $user = User::factory()->create();
        MatchModel::factory()->collaborator()->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/v1/ml/dataset/export', $this->mlHeaders())
            ->assertStatus(200);

        $row = $response->json('data.0');
        $this->assertEquals('', $row['feedback_type']);
        $this->assertEquals(0, $row['label_relevant']);
        $this->assertEquals(0, $row['label_not_relevant']);
    }

    /** @test */
    public function export_returns_csv_download_when_format_is_csv(): void
    {
        $user = User::factory()->create();
        MatchModel::factory()->collaborator()->create(['user_id' => $user->id]);

        $response = $this->get(
            '/api/v1/ml/dataset/export?format=csv',
            $this->mlHeaders()
        );

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));
    }

    /** @test */
    public function export_csv_contains_header_row_and_data(): void
    {
        $user = User::factory()->create();
        MatchModel::factory()->collaborator()->count(2)->create(['user_id' => $user->id]);

        $response = $this->withHeaders($this->mlHeaders())
            ->get('/api/v1/ml/dataset/export?format=csv');

        // Assert headers
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition');

        // Capture streamed content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $lines = array_filter(explode("\n", trim($content)));
        $this->assertCount(3, $lines);
        $this->assertStringContainsString('match_type', $lines[0]);
    }

    /** @test */
    public function export_rejects_invalid_format(): void
    {
        $user = User::factory()->create();
        MatchModel::factory()->collaborator()->create(['user_id' => $user->id]);

        $this->getJson('/api/v1/ml/dataset/export?format=xml', $this->mlHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['format']);
    }

    /** @test */
    public function export_rejects_invalid_type(): void
    {
        $this->getJson('/api/v1/ml/dataset/export?type=invalid', $this->mlHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    // =========================================================================
    // POST /api/v1/ml/matches/ingest
    // =========================================================================

    /** @test */
    public function ingest_creates_new_collaborator_match(): void
    {
        $payload = $this->makeIngestPayload();

        $this->postJson('/api/v1/ml/matches/ingest', [
            'matches' => [$payload],
        ], $this->mlHeaders())
            ->assertStatus(201)
            ->assertJsonPath('data.created', 1)
            ->assertJsonPath('data.updated', 0);

        $this->assertDatabaseHas('matches', [
            'user_id'             => $payload['user_id'],
            'matched_user_id'     => $payload['matched_user_id'],
            'match_type'          => 'collaborator',
            'compatibility_score' => 0.82,
        ]);
    }

    /** @test */
    public function ingest_creates_new_project_match(): void
    {
        $payload = $this->makeProjectIngestPayload();

        $this->postJson('/api/v1/ml/matches/ingest', [
            'matches' => [$payload],
        ], $this->mlHeaders())
            ->assertStatus(201)
            ->assertJsonPath('data.created', 1);

        $this->assertDatabaseHas('matches', [
            'user_id'            => $payload['user_id'],
            'matched_project_id' => $payload['matched_project_id'],
            'match_type'         => 'project',
        ]);
    }

    /** @test */
    public function ingest_updates_existing_non_expired_match(): void
    {
        $user    = User::factory()->create();
        $matched = User::factory()->create();

        // Existing match for the same pair
        $existing = MatchModel::factory()->collaborator()->create([
            'user_id'             => $user->id,
            'matched_user_id'     => $matched->id,
            'compatibility_score' => 0.60,
            'expires_at'          => now()->addDays(10),
        ]);

        $this->postJson('/api/v1/ml/matches/ingest', [
            'matches' => [[
                'user_id'             => $user->id,
                'match_type'          => 'collaborator',
                'matched_user_id'     => $matched->id,
                'compatibility_score' => 0.90,
                'match_reasons'       => ['skill_overlap' => 0.9],
                'expires_at'          => now()->addDays(30)->toIso8601String(),
            ]],
        ], $this->mlHeaders())
            ->assertStatus(201)
            ->assertJsonPath('data.created', 0)
            ->assertJsonPath('data.updated', 1);

        $this->assertDatabaseHas('matches', [
            'id'                  => $existing->id,
            'compatibility_score' => 0.90,
        ]);
        $this->assertDatabaseCount('matches', 1);
    }

    /** @test */
    public function ingest_creates_new_match_for_expired_pair(): void
    {
        $user    = User::factory()->create();
        $matched = User::factory()->create();

        // Expired match — should NOT be updated, new record should be created
        MatchModel::factory()->collaborator()->expired()->create([
            'user_id'         => $user->id,
            'matched_user_id' => $matched->id,
        ]);

        $this->postJson('/api/v1/ml/matches/ingest', [
            'matches' => [[
                'user_id'             => $user->id,
                'match_type'          => 'collaborator',
                'matched_user_id'     => $matched->id,
                'compatibility_score' => 0.85,
                'match_reasons'       => ['skill_overlap' => 0.8],
                'expires_at'          => now()->addDays(30)->toIso8601String(),
            ]],
        ], $this->mlHeaders())
            ->assertStatus(201)
            ->assertJsonPath('data.created', 1)
            ->assertJsonPath('data.updated', 0);

        $this->assertDatabaseCount('matches', 2);
    }

    /** @test */
    public function ingest_does_not_reset_viewed_or_saved_on_update(): void
    {
        $user    = User::factory()->create();
        $matched = User::factory()->create();

        $existing = MatchModel::factory()->collaborator()->viewed()->saved()->create([
            'user_id'         => $user->id,
            'matched_user_id' => $matched->id,
            'expires_at'      => now()->addDays(10),
        ]);

        $this->postJson('/api/v1/ml/matches/ingest', [
            'matches' => [[
                'user_id'             => $user->id,
                'match_type'          => 'collaborator',
                'matched_user_id'     => $matched->id,
                'compatibility_score' => 0.95,
                'match_reasons'       => ['skill_overlap' => 0.9],
                'expires_at'          => now()->addDays(30)->toIso8601String(),
            ]],
        ], $this->mlHeaders());

        $this->assertDatabaseHas('matches', [
            'id'     => $existing->id,
            'viewed' => true,
            'saved'  => true,
        ]);
    }

    /** @test */
    public function ingest_handles_a_batch_of_mixed_types(): void
    {
        $collab  = $this->makeIngestPayload(['compatibility_score' => 0.80]);
        $project = $this->makeProjectIngestPayload(['compatibility_score' => 0.75]);

        $this->postJson('/api/v1/ml/matches/ingest', [
            'matches' => [$collab, $project],
        ], $this->mlHeaders())
            ->assertStatus(201)
            ->assertJsonPath('data.created', 2)
            ->assertJsonPath('data.updated', 0);

        $this->assertDatabaseCount('matches', 2);
    }

    /** @test */
    public function ingest_is_atomic_one_bad_record_rolls_back_entire_batch(): void
    {
        $good = $this->makeIngestPayload();
        $bad  = $this->makeIngestPayload([
            'user_id' => '00000000-0000-0000-0000-000000000000', // non-existent
        ]);

        $this->postJson('/api/v1/ml/matches/ingest', [
            'matches' => [$good, $bad],
        ], $this->mlHeaders())
            ->assertStatus(422);

        // Nothing written — transaction rolled back
        $this->assertDatabaseCount('matches', 0);
    }

    /** @test */
    public function ingest_rejects_collaborator_match_without_matched_user_id(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/ml/matches/ingest', [
            'matches' => [[
                'user_id'             => $user->id,
                'match_type'          => 'collaborator',
                'matched_user_id'     => null,
                'compatibility_score' => 0.80,
                'match_reasons'       => [],
                'expires_at'          => now()->addDays(30)->toIso8601String(),
            ]],
        ], $this->mlHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['matches.0.matched_user_id']);
    }

    /** @test */
    public function ingest_rejects_project_match_without_matched_project_id(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/ml/matches/ingest', [
            'matches' => [[
                'user_id'             => $user->id,
                'match_type'          => 'project',
                'matched_project_id'  => null,
                'compatibility_score' => 0.80,
                'match_reasons'       => [],
                'expires_at'          => now()->addDays(30)->toIso8601String(),
            ]],
        ], $this->mlHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['matches.0.matched_project_id']);
    }

    /** @test */
    public function ingest_rejects_score_above_1(): void
    {
        $this->postJson('/api/v1/ml/matches/ingest', [
            'matches' => [$this->makeIngestPayload(['compatibility_score' => 1.5])],
        ], $this->mlHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['matches.0.compatibility_score']);
    }

    /** @test */
    public function ingest_rejects_score_below_0(): void
    {
        $this->postJson('/api/v1/ml/matches/ingest', [
            'matches' => [$this->makeIngestPayload(['compatibility_score' => -0.1])],
        ], $this->mlHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['matches.0.compatibility_score']);
    }

    /** @test */
    public function ingest_rejects_expires_at_in_the_past(): void
    {
        $this->postJson('/api/v1/ml/matches/ingest', [
            'matches' => [$this->makeIngestPayload(['expires_at' => now()->subDay()->toIso8601String()])],
        ], $this->mlHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['matches.0.expires_at']);
    }

    /** @test */
    public function ingest_rejects_non_existent_user_id(): void
    {
        $this->postJson('/api/v1/ml/matches/ingest', [
            'matches' => [$this->makeIngestPayload([
                'user_id' => '00000000-0000-0000-0000-000000000000',
            ])],
        ], $this->mlHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['matches.0.user_id']);
    }

    /** @test */
    public function ingest_rejects_empty_matches_array(): void
    {
        $this->postJson('/api/v1/ml/matches/ingest', [
            'matches' => [],
        ], $this->mlHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['matches']);
    }

    /** @test */
    public function ingest_rejects_invalid_match_type(): void
    {
        $this->postJson('/api/v1/ml/matches/ingest', [
            'matches' => [$this->makeIngestPayload(['match_type' => 'friendship'])],
        ], $this->mlHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['matches.0.match_type']);
    }
}
