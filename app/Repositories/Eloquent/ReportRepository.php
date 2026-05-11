<?php

namespace App\Repositories\Eloquent;

use App\DTOs\Report\StoreReportDTO;
use App\DTOs\Report\UpdateReportDTO;
use App\Models\Report;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ReportRepository implements ReportRepositoryInterface
{
    public function paginateForUser(string $userId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Report::where('reporter_id', $userId)
            ->with([
                'reportedUser:id,full_name,username,profile_picture_url',
            ])
            ->orderByDesc('created_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['report_type'])) {
            $query->where('report_type', $filters['report_type']);
        }

        return $query->paginate($perPage);
    }

    public function findByIdAndUser(string $id, string $userId): ?Report
    {
        return Report::with([
                'reportedUser:id,full_name,username,profile_picture_url',
            ])
            ->where('id', $id)
            ->where('reporter_id', $userId)
            ->first();
    }

    public function findById(string $id): ?Report
    {
        return Report::where('id', $id)->first();
    }

    public function store(StoreReportDTO $dto): Report
    {
        return Report::create([
            'id'                    => Str::uuid(),
            'reporter_id'           => $dto->reporterId,
            'reported_user_id'      => $dto->reportedUserId,
            'reported_content_type' => $dto->reportedContentType,
            'reported_content_id'   => $dto->reportedContentId,
            'report_type'           => $dto->reportType,
            'description'           => $dto->description,
            'evidence'              => $dto->evidence,
            'status'                => 'pending',
            'priority'              => 'medium', // Default priority, can be adjusted by admins
        ]);
    }

    public function update(Report $report, UpdateReportDTO $dto): Report
    {
        $payload = [];

        if ($dto->description !== null) {
            $payload['description'] = $dto->description;
        }

        if ($dto->evidence !== null) {
            $existingEvidence = $report->evidence ?? [];
            // Merge existing and new evidence, then remove duplicates and re-index
            $payload['evidence'] = array_values(array_unique(array_merge($existingEvidence, $dto->evidence)));
        }

        if (!empty($payload)) {
            $report->update($payload);
        }

        return $report->fresh();
    }

    public function delete(Report $report): void
    {
        $report->delete();
    }

    public function withdraw(Report $report): void
    {
        $report->update(['status' => 'withdrawn']);
    }
}
