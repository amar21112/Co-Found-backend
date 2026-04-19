<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Console\Command;

class PruneGuestAccounts extends Command
{
    /**
     * php artisan auth:prune-guests {--days=7}
     */
    protected $signature = 'auth:prune-guests
                            {--days=7 : Delete guest accounts older than this many days}';

    protected $description = 'Hard-delete stale ephemeral guest accounts that have exceeded their TTL.';

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $days      = (int) $this->option('days');
        $threshold = now()->subDays($days);

        $this->info("Pruning guest accounts created before {$threshold->toDateTimeString()} ...");

        $deleted = $this->userRepository->deleteGuestOlderThan($threshold);

        $this->info("Done. $deleted guest account(s) removed.");

        return self::SUCCESS;
    }
}
