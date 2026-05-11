<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\AdminActionLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

readonly class AdminActionLogService
{
    public function __construct(
        private AdminActionLogRepositoryInterface $logRepo,
    ) {}

    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->logRepo->paginate($filters, $perPage);
    }
}
