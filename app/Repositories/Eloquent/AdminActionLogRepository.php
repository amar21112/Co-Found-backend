<?php

namespace App\Repositories\Eloquent;

use App\Models\AdminAction;
use App\Repositories\Contracts\AdminActionLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminActionLogRepository implements AdminActionLogRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = AdminAction::with('admin:id,username,full_name,profile_picture_url')
            ->orderByDesc('created_at');

        if (!empty($filters['admin_id'])) {
            $query->where('admin_id', $filters['admin_id']);
        }

        if (!empty($filters['action_type'])) {
            $query->where('action_type', $filters['action_type']);
        }

        if (!empty($filters['target_type'])) {
            $query->where('target_type', $filters['target_type']);
        }

        if (!empty($filters['target_id'])) {
            $query->where('target_id', $filters['target_id']);
        }

        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        return $query->paginate($perPage);
    }
}
