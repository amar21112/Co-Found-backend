<?php

namespace App\Repositories\Contracts;

use App\Models\Notification;
use App\Models\NotificationPreference;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NotificationRepositoryInterface
{
    public function paginateForUser(string $userId, array $filters, int $perPage): LengthAwarePaginator;

    public function findById(string $id): ?Notification;

    public function create(array $data): Notification;

    public function markRead(Notification $notification): Notification;

    public function markAllRead(string $userId): array;

    public function unreadCount(string $userId): int;

    public function getPreferences(string $userId): ?NotificationPreference;

    public function upsertPreferences(string $userId, array $data): NotificationPreference;
}
