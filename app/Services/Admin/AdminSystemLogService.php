<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\AdminSystemLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

readonly class AdminSystemLogService
{
    public function __construct(
        private AdminSystemLogRepositoryInterface $logRepo,
    ) {}

    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->logRepo->paginate($filters, $perPage);
    }
}
