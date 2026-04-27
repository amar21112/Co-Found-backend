<?php

namespace App\Repositories\Contracts;

use App\Models\ContentModeration;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminModerationRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;

    public function create(array $data): ContentModeration;
}
