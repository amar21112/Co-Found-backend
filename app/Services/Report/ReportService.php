<?php

namespace App\Services\Report;

use App\DTOs\Report\StoreReportDTO;
use App\DTOs\Report\UpdateReportDTO;
use App\Models\Report;
use App\Models\User;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class ReportService
{
    public function __construct(
        private readonly ReportRepositoryInterface $reportRepo,
    ) {}

    public function listOwnReports(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->reportRepo->paginateForUser($user->id, $filters, $perPage);
    }

    public function showOwnReport(string $id, User $user): Report
    {
        $report = $this->reportRepo->findByIdAndUser($id, $user->id);

        if (!$report) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Report not found or not owned by user.');
        }

        return $report;
    }

    public function createReport(StoreReportDTO $dto): Report
    {
        // Don't allow reporting oneself
        if ($dto->reporterId === $dto->reportedUserId) {
            throw new InvalidArgumentException("You cannot report yourself.");
        }

        return $this->reportRepo->store($dto);
    }

    public function updateOwnReport(string $id, User $user, UpdateReportDTO $dto): Report
    {
        $report = $this->showOwnReport($id, $user);

        // Can only update if status is still pending
        if ($report->status !== 'pending') {
            throw new InvalidArgumentException("Once a report is reviewed, it cannot be edited.");
        }

        return $this->reportRepo->update($report, $dto);
    }

    public function withdrawOwnReport(string $id, User $user): void
    {
        $report = $this->showOwnReport($id, $user);

        // Optionally, only allow withdrawal if it's pending.
        // For now, let's allow withdrawal (deletion) anytime and it removes the report.
        if ($report->status === 'resolved' || $report->status === 'dismissed') {
            throw new InvalidArgumentException("This report has already been processed and cannot be withdrawn.");
        }

        $this->reportRepo->delete($report);
    }
}
