<?php

namespace App\Services\Report;

use App\DTOs\Report\StoreReportDTO;
use App\DTOs\Report\UpdateReportDTO;
use App\Exceptions\ConflictException;
use App\Models\Report;
use App\Models\User;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

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
            throw new ModelNotFoundException('Report not found or not owned by user.');
        }

        return $report;
    }

    /**
     * @throws ValidationException
     */
    public function createReport(StoreReportDTO $dto): Report
    {
        // Don't allow reporting oneself
        if ($dto->reporterId === $dto->reportedUserId) {
            throw ValidationException::withMessages([
                'reported_user_id' => ['You cannot report yourself.'],
            ]);
        }

        return $this->reportRepo->store($dto);
    }

    /**
     * @throws ConflictException
     */
    public function updateOwnReport(string $id, User $user, UpdateReportDTO $dto): Report
    {
        $report = $this->showOwnReport($id, $user);

        // Can only update if status is still pending
        if ($report->status !== 'pending') {
            throw new ConflictException('Once a report is reviewed, it cannot be edited.');
        }

        return $this->reportRepo->update($report, $dto);
    }

    /**
     * @throws ConflictException
     */
    public function withdrawOwnReport(string $id, User $user): void
    {
        $report = $this->showOwnReport($id, $user);

        if (in_array($report->status, ['under_review', 'resolved', 'dismissed'])) {
            throw new ConflictException('This report cannot be withdrawn once it is under review or processed.');
        }

        $this->reportRepo->withdraw($report);
    }

    public function deleteReport(string $id): void
    {
        $report = $this->reportRepo->findById($id);

        if (!$report) {
            throw new ModelNotFoundException('Report not found.');
        }

        $this->reportRepo->delete($report);
    }
}
