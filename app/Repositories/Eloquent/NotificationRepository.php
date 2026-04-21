<?php

namespace App\Repositories\Eloquent;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function paginateForUser(string $userId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Notification::where('user_id', $userId);

        if (isset($filters['read'])) {
            $query->where('read', filter_var($filters['read'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->latest('created_at')->paginate($perPage);
    }

    public function findById(string $id): ?Notification
    {
        return Notification::find($id);
    }

    public function create(array $data): Notification
    {
        return Notification::create(array_merge($data, [
            'delivered_at' => now(),
        ]));
    }

    public function markRead(Notification $notification): Notification
    {
        $notification->update([
            'read'    => true,
            'read_at' => now(),
        ]);
        return $notification->fresh();
    }

    public function markAllRead(string $userId): array
    {
        $ids = Notification::where('user_id', $userId)
            ->where('read', false)
            ->pluck('id')
            ->all();

        if (!empty($ids)) {
            Notification::whereIn('id', $ids)->update([
                'read'    => true,
                'read_at' => now(),
            ]);
        }

        return $ids;
    }

    public function unreadCount(string $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('read', false)
            ->count();
    }

    public function getPreferences(string $userId): ?NotificationPreference
    {
        return NotificationPreference::where('user_id', $userId)->first();
    }

    public function upsertPreferences(string $userId, array $data): NotificationPreference
    {
        return NotificationPreference::updateOrCreate(
            ['user_id' => $userId],
            $data
        );
    }
}
