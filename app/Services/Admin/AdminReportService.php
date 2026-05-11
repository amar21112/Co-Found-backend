<?php

namespace App\Services\Admin;

use App\DTOs\Admin\UpdateReportDTO;
use App\Exceptions\Admin\ReportNotFoundException;
use App\Models\Report;
use App\Models\User;
use App\Repositories\Contracts\AdminReportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

readonly class AdminReportService
{
    public function __construct(
        private AdminReportRepositoryInterface $reportRepo,
        private AdminActionLogger              $logger,
    ) {}

    // =========================================================================
    // List
    // =========================================================================

    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->reportRepo->paginate($filters, $perPage);
    }

    // =========================================================================
    // Show
    // =========================================================================

    public function show(string $id): Report
    {
        $report = $this->reportRepo->findById($id);

        if (!$report) {
            throw new ReportNotFoundException();
        }

        return $report;
    }

    // =========================================================================
    // Update (status / assign / resolve)
    // =========================================================================

    /**
     * Update report status, assignment, or resolution fields.
     *
     * Business rules:
     * - When resolution_action is provided, resolved_at is stamped automatically.
     * - resolved_by is set to the acting moderator.
     * - Every mutation is logged to admin_actions.
     */
    public function update(
        Report          $report,
        UpdateReportDTO $dto,
        User            $admin,
        string          $ip,
    ): Report {
        $payload = array_filter([
            'status'            => $dto->status,
            'priority'          => $dto->priority,
            'assigned_to'       => $dto->assignedTo,
            'resolution_action' => $dto->resolutionAction,
            'resolution_notes'  => $dto->resolutionNotes,
        ], fn($v) => $v !== null);

        // Stamp resolved_at when a resolution action is provided
        if ($dto->resolutionAction !== null) {
            $payload['resolved_by'] = $dto->resolvedBy;
            $payload['resolved_at'] = now();

            if ($dto->status === null) {
                $payload['status'] = 'resolved';
            }
        }

        $updated = $this->reportRepo->update($report, $payload);

        $this->logger->log(
            admin:      $admin,
            actionType: 'report_updated',
            targetType: 'report',
            targetId:   $report->id,
            details:    [
                'changes'          => $payload,
                'reporter_id'      => $report->reporter_id,
                'reported_user_id' => $report->reported_user_id,
            ],
            ip: $ip,
        );

        return $updated;
    }
}
