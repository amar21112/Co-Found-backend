<?php

namespace Tests\Feature\ML;

use App\Jobs\GenerateMatchesJob;
use App\Models\User;
use App\Services\ML\MlMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use ReflectionProperty;
use RuntimeException;
use Tests\TestCase;

/**
 * Feature tests for GenerateMatchesJob.
 *
 * Verifies the job is a thin shell:
 *   - It dispatches without errors in both modes
 *   - It delegates to MlMatchingService::generateForUser() for single-user
 *   - It delegates to MlMatchingService::generateForAllUsers() for batch
 *   - It re-throws exceptions so Laravel's retry mechanism fires
 *   - Queue dispatch is correct (connection, tries, backoff)
 */
class GenerateMatchesJobTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Dispatch
    // =========================================================================

    /** @test */
    public function job_can_be_dispatched_to_queue_in_single_user_mode(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        GenerateMatchesJob::dispatch(userId: $user->id);

        Queue::assertPushed(GenerateMatchesJob::class, function ($job) use ($user) {
            // Reach into the job via reflection to assert the userId was captured
            $ref = new ReflectionProperty($job, 'userId');
            return $ref->getValue($job) === $user->id;
        });
    }

    /** @test */
    public function job_can_be_dispatched_to_queue_in_batch_mode(): void
    {
        Queue::fake();

        GenerateMatchesJob::dispatch();

        Queue::assertPushed(GenerateMatchesJob::class, function ($job) {
            $ref = new ReflectionProperty($job, 'userId');
            return $ref->getValue($job) === null;
        });
    }

    // =========================================================================
    // handle — delegation to MlMatchingService
    // =========================================================================

    /** @test */
    public function handle_calls_generate_for_user_when_user_id_is_set(): void
    {
        $user    = User::factory()->create(['role' => 'regular_user', 'account_status' => 'active']);
        $service = Mockery::mock(MlMatchingService::class);

        $service->shouldReceive('generateForUser')
            ->once()
            ->withArgs(fn ($arg) => $arg->id === $user->id)
            ->andReturn(['created' => 1, 'updated' => 0]);

        $service->shouldNotReceive('generateForAllUsers');

        $job = new GenerateMatchesJob(userId: $user->id);
        $job->handle($service);
    }

    /** @test */
    public function handle_calls_generate_for_all_users_when_user_id_is_null(): void
    {
        $service = Mockery::mock(MlMatchingService::class);

        $service->shouldReceive('generateForAllUsers')
            ->once()
            ->andReturn(['created' => 5, 'updated' => 2]);

        $service->shouldNotReceive('generateForUser');

        $job = new GenerateMatchesJob();
        $job->handle($service);
    }

    /** @test */
    public function handle_rethrows_exceptions_so_laravel_retries_the_job(): void
    {
        $user    = User::factory()->create(['role' => 'regular_user', 'account_status' => 'active']);
        $service = Mockery::mock(MlMatchingService::class);

        $service->shouldReceive('generateForUser')
            ->once()
            ->andThrow(new RuntimeException('ML timeout'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ML timeout');

        $job = new GenerateMatchesJob(userId: $user->id);
        $job->handle($service);
    }

    // =========================================================================
    // Retry policy
    // =========================================================================

    /** @test */
    public function job_has_correct_retry_configuration(): void
    {
        $job = new GenerateMatchesJob();

        $this->assertEquals(3,  $job->tries,   'Job must have 3 retry attempts');
        $this->assertEquals(60, $job->backoff,  'Backoff must be 60 seconds');
    }

    /** @test */
    public function failed_hook_does_not_throw(): void
    {
        $job = new GenerateMatchesJob(userId: 'some-id');

        // failed() must never throw — it only logs
        $job->failed(new RuntimeException('All retries exhausted'));

        $this->assertTrue(true); // reached here without exception
    }
}
