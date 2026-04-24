<?php

namespace App\Services\Chat;

use App\Exceptions\ChatException;
use App\Firebase\FirebaseSyncService;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepo,
        private readonly FirebaseSyncService             $firebase,
    ) {}

    public function list(User $user, array $filters, int $perPage): array
    {
        $paginator    = $this->notificationRepo->paginateForUser($user->id, $filters, $perPage);
        $unreadCount  = $this->notificationRepo->unreadCount($user->id);

        return compact('paginator', 'unreadCount');
    }

    /**
     * Dispatch a notification to a user.
     * Writes to MySQL first, then syncs to Firebase.
     * Called from listeners/jobs throughout the platform.
     */
    public function send(
        string $userId,
        string $type,
        string $title,
        string $body,
        array  $data     = [],
        string $priority = 'normal',
    ): Notification {
        $notification = $this->notificationRepo->create([
            'user_id'  => $userId,
            'type'     => $type,
            'title'    => $title,
            'body'     => $body,
            'data'     => $data,
            'priority' => $priority,
            'read'     => false,
        ]);

        // Push to Firebase RTDB for real-time delivery
        $this->firebase->syncNotification($notification);

        return $notification;
    }

    public function markRead(User $user, string $notificationId): Notification
    {
        $notification = $this->notificationRepo->findById($notificationId);

        if (!$notification || $notification->user_id !== $user->id) {
            throw new ChatException('Notification not found.', 404);
        }

        $updated = $this->notificationRepo->markRead($notification);
        $this->firebase->markNotificationRead($updated);

        return $updated;
    }

    public function markAllRead(User $user): void
    {
        $ids = $this->notificationRepo->markAllRead($user->id);
        $this->firebase->markAllNotificationsRead($user->id, $ids);
    }

    public function getPreferences(User $user): ?NotificationPreference
    {
        return $this->notificationRepo->getPreferences($user->id);
    }

    public function updatePreferences(User $user, array $data): NotificationPreference
    {
        return $this->notificationRepo->upsertPreferences($user->id, $data);
    }
}
