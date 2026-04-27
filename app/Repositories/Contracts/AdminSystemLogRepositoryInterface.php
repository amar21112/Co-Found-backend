<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminSystemLogRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;
}
