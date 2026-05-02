<?php

namespace App\Repositories\Eloquent;

use App\Models\SystemLog;
use App\Repositories\Contracts\AdminSystemLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminSystemLogRepository implements AdminSystemLogRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = SystemLog::with('user:id,username,full_name')
            ->orderByDesc('created_at');

        if (!empty($filters['log_level'])) {
            $query->where('log_level', $filters['log_level']);
        }

        if (!empty($filters['component'])) {
            $query->where('component', 'LIKE', '%' . $filters['component'] . '%');
        }

        if (!empty($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['search'])) {
            $query->where('message', 'LIKE', '%' . $filters['search'] . '%');
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
