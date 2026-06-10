<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ML\MlMatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued wrapper around MlMatchingService.
 *
 * This job contains NO business logic. Its only job is to receive parameters
 * from the queue, resolve the service from the container, and delegate.
 *
 * Two dispatch modes:
 *   - Single-user (userId set)  → called from AdminVerificationService on approval
 *   - Batch (userId null)       → called from Console\Kernel nightly schedule
 *
 * Usage:
 *   GenerateMatchesJob::dispatch(userId: $user->id);  // single user
 *   GenerateMatchesJob::dispatch();                   // all users
 */
class GenerateMatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly ?string $userId = null,
    ) {}

    public function handle(MlMatchingService $service): void
    {
        if ($this->userId !== null) {
            $user = User::findOrFail($this->userId);
            $service->generateForUser($user);
        } else {
            $service->generateForAllUsers();
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('GenerateMatchesJob: all retries exhausted', [
            'user_id' => $this->userId ?? 'batch',
            'error'   => $e->getMessage(),
        ]);
    }
}
