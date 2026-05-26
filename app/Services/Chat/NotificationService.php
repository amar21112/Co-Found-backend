<?php

namespace App\Services\Chat;

use App\Exceptions\ChatException;
use App\Firebase\FirebaseService;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Kreait\Firebase\Exception\DatabaseException;

class NotificationService
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepo,
        private readonly FirebaseService                 $firebase,
    ) {}

    public function list(User $user, array $filters, int $perPage): array
    {
        $paginator   = $this->notificationRepo->paginateForUser($user->id, $filters, $perPage);
        $unreadCount = $this->notificationRepo->unreadCount($user->id);

        return compact('paginator', 'unreadCount');
    }

    /**
     * Persist a notification to MySQL then push it to Firebase RTDB
     * for real-time delivery to the client.
     * @throws DatabaseException
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

        $this->firebase->set(
            $this->firebase->notificationPath($notification->user_id, $notification->id),
            [
                'id'           => $notification->id,
                'type'         => $notification->type,
                'title'        => $notification->title,
                'body'         => $notification->body,
                'data'         => $notification->data,
                'priority'     => $notification->priority,
                'read'         => false,
                'read_at'      => null,
                'created_at'   => $notification->created_at?->toISOString(),
            ]
        );

        return $notification;
    }

    /**
     * @throws ChatException
     * @throws DatabaseException
     */
    public function markRead(User $user, string $notificationId): Notification
    {
        $notification = $this->notificationRepo->findById($notificationId);

        if (! $notification || $notification->user_id !== $user->id) {
            throw new ChatException('Notification not found.', 404);
        }

        $updated = $this->notificationRepo->markRead($notification);

        $this->firebase->update(
            $this->firebase->notificationPath($updated->user_id, $updated->id),
            [
                'read'    => true,
                'read_at' => $updated->read_at?->toISOString(),
            ]
        );

        return $updated;
    }

    /**
     * @throws DatabaseException
     */
    public function markAllRead(User $user): void
    {
        $ids = $this->notificationRepo->markAllRead($user->id);

        if (empty($ids)) {
            return;
        }

        $readAt  = now()->toISOString();
        $updates = [];

        foreach ($ids as $id) {
            $updates["$id/read"]    = true;
            $updates["$id/read_at"] = $readAt;
        }

        $this->firebase->update(
            $this->firebase->notificationsPath($user->id),
            $updates
        );
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
