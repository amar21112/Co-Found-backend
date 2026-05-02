<?php

namespace App\Repositories\Contracts;

use App\DTOs\Report\StoreReportDTO;
use App\DTOs\Report\UpdateReportDTO;
use App\Models\Report;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ReportRepositoryInterface
{
    public function paginateForUser(string $userId, array $filters, int $perPage): LengthAwarePaginator;

    public function findByIdAndUser(string $id, string $userId): ?Report;

    public function store(StoreReportDTO $dto): Report;

    public function update(Report $report, UpdateReportDTO $dto): Report;

    public function delete(Report $report): void;
}
