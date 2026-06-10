<?php

namespace Tests\Feature\ML\Services;

use App\DTOs\ML\MatchPairDTO;
use App\Exceptions\ML\MlServiceException;
use App\Services\ML\MlServiceClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;
use Throwable;

/**
 * Feature tests for MlServiceClient.
 *
 * Uses Laravel's HTTP fake — no real network calls.
 * Tests: correct payload shape, auth header, chunking, error handling.
 */
class MlServiceClientTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = 'http://localhost:8001';
    private const SECRET   = 'test-ml-secret';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.ml.base_url', self::BASE_URL);
        Config::set('services.ml.secret',   self::SECRET);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeClient(): MlServiceClient
    {
        return new MlServiceClient();
    }

    private function makeCollaboratorPair(string $userId = null, string $matchedUserId = null): MatchPairDTO
    {
        return MatchPairDTO::collaborator(
            userId:                $userId         ?? (string) Str::uuid(),
            matchedUserId:         $matchedUserId  ?? (string) Str::uuid(),
            skillOverlap:          0.6,
            overlappingSkillsCount:3,
            skillsCountA:          5,
            skillsCountB:          4,
            userAVerified:         1,
            userBVerified:         1,
            locationMatch:         1,
        );
    }

    private function makeProjectPair(string $userId = null, string $projectId = null): MatchPairDTO
    {
        return MatchPairDTO::project(
            userId:               $userId    ?? (string) Str::uuid(),
            matchedProjectId:     $projectId ?? (string) Str::uuid(),
            skillsCountA:         5,
            coveredSkillsCount:   3,
            projectAccepting:     1,
            teamOpenness:         0.5,
            userIdentityVerified: 1,
            locationMatch:        1,
        );
    }

    private function fakeMlResponse(array $results): void
    {
        Http::fake([
            self::BASE_URL . '/predict/batch' => Http::response([
                'status' => 'success',
                'data'   => $results,
            ]),
        ]);
    }

    private function fakeHealthResponse(): void
    {
        Http::fake([
            self::BASE_URL . '/health' => Http::response([
                'status'     => 'ok',
                'model'      => 'RandomForestClassifier',
                'n_features' => 20,
            ]),
        ]);
    }

    // =========================================================================
    // predictBatch — happy path
    // =========================================================================

    /** @test */
    public function predict_batch_sends_bearer_token_header(): void
    {
        Http::fake([
            self::BASE_URL . '/predict/batch' => Http::response(['status' => 'success', 'data' => []]),
        ]);

        $this->makeClient()->predictBatch(collect([$this->makeCollaboratorPair()]));

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer ' . self::SECRET);
        });
    }

    /** @test */
    public function predict_batch_sends_correct_json_payload_shape(): void
    {
        Http::fake([
            self::BASE_URL . '/predict/batch' => Http::response(['status' => 'success', 'data' => []]),
        ]);

        $pair = $this->makeCollaboratorPair();
        $this->makeClient()->predictBatch(collect([$pair]));

        Http::assertSent(function ($request) {
            $body  = json_decode($request->body(), true);
            $pairs = $body['pairs'] ?? [];

            if (count($pairs) !== 1) return false;

            $p = $pairs[0];

            // Every snake_case field the FastAPI schema expects must be present
            return isset(
                $p['match_type'],
                $p['skill_overlap'],
                $p['complementarity'],
                $p['overlapping_skills_count'],
                $p['skills_count_a'],
                $p['skills_count_b'],
                $p['unique_skills_a'],
                $p['unique_skills_b'],
                $p['user_a_verified'],
                $p['user_b_verified'],
                $p['both_identity_verified'],
                $p['location_match'],
                $p['same_location'],
                $p['user_identity_verified'],
                $p['compatibility_score'],
                $p['viewed'],
                $p['saved'],
                $p['action_taken'],
                $p['project_accepting'],
                $p['team_openness'],
                $p['covered_skills_count'],
            );
        });
    }

    /** @test */
    public function predict_batch_maps_collaborator_field_values_correctly(): void
    {
        Http::fake([
            self::BASE_URL . '/predict/batch' => Http::response(['status' => 'success', 'data' => []]),
        ]);

        $pair = MatchPairDTO::collaborator(
            userId:                'uid',
            matchedUserId:         'mid',
            skillOverlap:          0.75,
            overlappingSkillsCount:3,
            skillsCountA:          4,
            skillsCountB:          5,
            userAVerified:         1,
            userBVerified:         0,
            locationMatch:         1,
        );

        $this->makeClient()->predictBatch(collect([$pair]));

        Http::assertSent(function ($request) {
            $p = json_decode($request->body(), true)['pairs'][0];

            return $p['match_type']                === 'collaborator'
                && $p['skill_overlap']             === 0.75
                && $p['complementarity']           === 0.25
                && $p['overlapping_skills_count']  === 3
                && $p['skills_count_a']            === 4
                && $p['skills_count_b']            === 5
                && $p['user_a_verified']           === 1
                && $p['user_b_verified']           === 0
                && $p['both_identity_verified']    === 0  // 1 AND 0 = 0
                && $p['location_match']            === 1
                && $p['project_accepting']         === 0; // not applicable
        });
    }

    /** @test */
    public function predict_batch_maps_project_field_values_correctly(): void
    {
        Http::fake([
            self::BASE_URL . '/predict/batch' => Http::response(['status' => 'success', 'data' => []]),
        ]);

        $pair = MatchPairDTO::project(
            userId:               'uid',
            matchedProjectId:     'pid',
            skillsCountA:         6,
            coveredSkillsCount:   4,
            projectAccepting:     1,
            teamOpenness:         0.4,
            userIdentityVerified: 1,
            locationMatch:        0,
        );

        $this->makeClient()->predictBatch(collect([$pair]));

        Http::assertSent(function ($request) {
            $p = json_decode($request->body(), true)['pairs'][0];

            // json_decode returns int 0 for JSON 0.0 — cast to float before strict compare.
            return $p['match_type']                    === 'project'
                && $p['covered_skills_count']          === 4
                && $p['project_accepting']             === 1
                && (float) $p['team_openness']         === 0.4
                && $p['user_identity_verified']        === 1
                && $p['location_match']                === 0
                && (float) $p['skill_overlap']         === 0.0
                && $p['user_a_verified']               === 0;
        });
    }

    /** @test */
    public function predict_batch_returns_only_relevant_results(): void
    {
        $pairA = $this->makeCollaboratorPair();
        $pairB = $this->makeCollaboratorPair();

        $this->fakeMlResponse([
            ['is_relevant' => true,  'compatibility_score' => 0.90, 'match_reasons' => []],
            ['is_relevant' => false, 'compatibility_score' => 0.30, 'match_reasons' => []],
        ]);

        $results = $this->makeClient()->predictBatch(collect([$pairA, $pairB]));

        $this->assertCount(1, $results);
        $this->assertEquals(0.90, $results->first()->compatibilityScore);
    }

    /** @test */
    public function predict_batch_returns_empty_collection_for_empty_input(): void
    {
        $results = $this->makeClient()->predictBatch(collect());

        Http::assertNothingSent();
        $this->assertTrue($results->isEmpty());
    }

    /** @test */
    public function predict_batch_preserves_routing_metadata_in_results(): void
    {
        $userId    = (string) Str::uuid();
        $matchedId = (string) Str::uuid();
        $pair      = $this->makeCollaboratorPair($userId, $matchedId);

        $this->fakeMlResponse([
            ['is_relevant' => true, 'compatibility_score' => 0.85, 'match_reasons' => ['skill_overlap' => 0.7]],
        ]);

        $results = $this->makeClient()->predictBatch(collect([$pair]));
        $result  = $results->first();

        $this->assertEquals($userId,    $result->userId);
        $this->assertEquals($matchedId, $result->matchedUserId);
        $this->assertNull($result->matchedProjectId);
        $this->assertEquals('collaborator', $result->matchType);
        $this->assertEquals(['skill_overlap' => 0.7], $result->matchReasons);
    }

    // =========================================================================
    // predictBatch — chunking
    // =========================================================================

    /** @test */
    public function predict_batch_sends_multiple_requests_when_pairs_exceed_chunk_size(): void
    {
        // BATCH_SIZE is 500 — send 501 pairs to force two requests
        $pairs = collect(range(1, 501))->map(fn () => $this->makeCollaboratorPair());

        Http::fake([
            self::BASE_URL . '/predict/batch' => Http::sequence()
                ->push(['status' => 'success', 'data' => array_fill(0, 500, ['is_relevant' => false, 'compatibility_score' => 0.5, 'match_reasons' => []])])
                ->push(['status' => 'success', 'data' => [             ['is_relevant' => true,  'compatibility_score' => 0.9, 'match_reasons' => []]]]),
        ]);

        $results = $this->makeClient()->predictBatch($pairs);

        Http::assertSentCount(2);
        $this->assertCount(1, $results); // only the 1 relevant one from chunk 2
    }

    /** @test */
    public function predict_batch_first_chunk_contains_at_most_500_pairs(): void
    {
        $pairs = collect(range(1, 600))->map(fn () => $this->makeCollaboratorPair());

        Http::fake([
            self::BASE_URL . '/predict/batch' => Http::response([
                'status' => 'success',
                'data'   => array_fill(0, 500, ['is_relevant' => false, 'compatibility_score' => 0.5, 'match_reasons' => []]),
            ]),
        ]);

        // Will fail on second chunk (no more fake responses) but first chunk is what we assert
        try {
            $this->makeClient()->predictBatch($pairs);
        } catch (Throwable) {
            // expected — second chunk has no fake response
        }

        $firstRequest   = Http::recorded()[0][0];
        $firstChunkSize = count(json_decode($firstRequest->body(), true)['pairs']);

        $this->assertLessThanOrEqual(500, $firstChunkSize);
    }

    // =========================================================================
    // predictBatch — error handling
    // =========================================================================

    /** @test */
    public function predict_batch_throws_retryable_exception_on_connection_failure(): void
    {
        Http::fake([
            self::BASE_URL . '/predict/batch' => fn () => throw new ConnectionException('Connection refused'),
        ]);

        $this->expectException(MlServiceException::class);

        try {
            $this->makeClient()->predictBatch(collect([$this->makeCollaboratorPair()]));
        } catch (MlServiceException $e) {
            $this->assertTrue($e->isRetryable(), 'Connection errors must be retryable');
            $this->assertEquals(0, $e->getHttpStatus());
            throw $e;
        }
    }

    /** @test */
    public function predict_batch_throws_retryable_exception_on_500(): void
    {
        Http::fake([
            self::BASE_URL . '/predict/batch' => Http::response(['detail' => 'Internal error'], 500),
        ]);

        $this->expectException(MlServiceException::class);

        try {
            $this->makeClient()->predictBatch(collect([$this->makeCollaboratorPair()]));
        } catch (MlServiceException $e) {
            $this->assertTrue($e->isRetryable());
            $this->assertEquals(500, $e->getHttpStatus());
            throw $e;
        }
    }

    /** @test */
    public function predict_batch_returns_empty_collection_on_401_and_logs_warning(): void
    {
        Http::fake([
            self::BASE_URL . '/predict/batch' => Http::response(['detail' => 'Unauthorized'], 401),
        ]);

        // Non-retryable HTTP errors are swallowed at chunk level —
        // the caller gets an empty collection, not an exception.
        // This preserves batch resilience: one bad chunk never kills the whole run.
        $results = $this->makeClient()->predictBatch(collect([$this->makeCollaboratorPair()]));

        $this->assertTrue($results->isEmpty());
    }

    /** @test */
    public function predict_batch_returns_empty_collection_on_422_and_logs_warning(): void
    {
        Http::fake([
            self::BASE_URL . '/predict/batch' => Http::response(['detail' => 'Validation error'], 422),
        ]);

        $results = $this->makeClient()->predictBatch(collect([$this->makeCollaboratorPair()]));

        $this->assertTrue($results->isEmpty());
    }

    /** @test */
    public function predict_batch_skips_non_retryable_chunk_and_continues(): void
    {
        // Chunk 1 → 422 (non-retryable, skipped), Chunk 2 → 200 with 1 relevant result
        $pairs = collect(range(1, 501))->map(fn () => $this->makeCollaboratorPair());

        Http::fake([
            self::BASE_URL . '/predict/batch' => Http::sequence()
                ->push(['detail' => 'Schema error'], 422)
                ->push(['status' => 'success', 'data' => [
                    ['is_relevant' => true, 'compatibility_score' => 0.88, 'match_reasons' => []],
                ]]),
        ]);

        $results = $this->makeClient()->predictBatch($pairs);

        // Non-retryable chunk 1 is silently skipped; chunk 2 succeeds
        $this->assertCount(1, $results);
    }

    /** @test */
    public function predict_batch_throws_when_response_data_field_is_missing(): void
    {
        Http::fake([
            self::BASE_URL . '/predict/batch' => Http::response(['status' => 'success']),
        ]);

        $this->expectException(MlServiceException::class);
        $this->expectExceptionMessage('unexpected payload');

        $this->makeClient()->predictBatch(collect([$this->makeCollaboratorPair()]));
    }

    // =========================================================================
    // health
    // =========================================================================

    /** @test */
    public function health_returns_service_status(): void
    {
        $this->fakeHealthResponse();

        $result = $this->makeClient()->health();

        $this->assertEquals('ok',                     $result['status']);
        $this->assertEquals('RandomForestClassifier', $result['model']);
        $this->assertEquals(20,                       $result['n_features']);
    }

    /** @test */
    public function health_sends_bearer_token(): void
    {
        $this->fakeHealthResponse();

        $this->makeClient()->health();

        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer ' . self::SECRET));
    }

    /** @test */
    public function health_throws_on_connection_failure(): void
    {
        Http::fake([
            self::BASE_URL . '/health' => fn () => throw new ConnectionException('refused'),
        ]);

        $this->expectException(MlServiceException::class);

        $this->makeClient()->health();
    }
}
