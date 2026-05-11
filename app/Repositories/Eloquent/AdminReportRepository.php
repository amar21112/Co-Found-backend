<?php

namespace App\Repositories\Eloquent;

use App\Models\Report;
use App\Repositories\Contracts\AdminReportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminReportRepository implements AdminReportRepositoryInterface
{
    // ── Shared eager-load sets ────────────────────────────────────────────────

    /**
     * Relations loaded for every item in the paginated list.
     *
     * Includes the reported user's identity verification summary so the
     * moderator can see verification status and face-match score without
     * drilling into each report individually.
     */
    private const LIST_WITH = [
        'reporter:id,username,full_name,profile_picture_url,account_status,identity_verified',
        'reportedUser:id,username,full_name,profile_picture_url,email,role,account_status,email_verified,identity_verified,identity_verification_level,created_at',
        'reportedUser.identityVerification',           // summary — no nested reviews
        'reportedUser.activeRestrictions',             // instant view of current bans
        'assignedModerator:id,username,full_name',
        'resolver:id,username,full_name',
    ];

    /**
     * Relations loaded for the single-record detail view.
     *
     * Adds full identity verification (document images + all reviews),
     * and restriction history with who issued each restriction.
     */
    private const DETAIL_WITH = [
        'reporter:id,username,full_name,profile_picture_url,account_status,identity_verified',
        'reportedUser:id,username,full_name,profile_picture_url,email,role,account_status,email_verified,identity_verified,identity_verification_level,created_at',
        'reportedUser.identityVerification.reviews.reviewer:id,username,full_name,role',
        'reportedUser.identityVerification.latestReview',
        'reportedUser.activeRestrictions.restrictedBy:id,username',
        'assignedModerator:id,username,full_name',
        'resolver:id,username,full_name',
    ];

    // ─────────────────────────────────────────────────────────────────────────

    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Report::with(self::LIST_WITH)->orderByDesc('created_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['report_type'])) {
            $query->where('report_type', $filters['report_type']);
        }

        if (!empty($filters['reported_user_id'])) {
            $query->where('reported_user_id', $filters['reported_user_id']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query->paginate($perPage);
    }

    public function findById(string $id): ?Report
    {
        return Report::with(self::DETAIL_WITH)->find($id);
    }

    public function update(Report $report, array $data): Report
    {
        $report->update($data);

        // Reload with full detail relations so the response after an update
        // is identical in richness to a fresh GET /admin/reports/{id} call.
        return $report->fresh(self::DETAIL_WITH);
    }
}