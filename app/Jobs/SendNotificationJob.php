<?php

namespace App\Jobs;

use App\Services\Chat\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched by any part of the platform that needs to notify a user.
 * Writes to MySQL then syncs to Firebase RTDB off the request cycle.
 *
 * Usage:
 *   SendNotificationJob::dispatch(
 *       userId:   $user->id,
 *       type:     'new_message',
 *       title:    'New message from Ahmed',
 *       body:     'Hey, are you available?',
 *       data:     ['conversation_id' => $conv->id],
 *       priority: 'high',
 *   );
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly string $userId,
        private readonly string $type,
        private readonly string $title,
        private readonly string $body,
        private readonly array  $data     = [],
        private readonly string $priority = 'normal',
    ) {}

    public function handle(NotificationService $service): void
    {
        $service->send(
            userId:   $this->userId,
            type:     $this->type,
            title:    $this->title,
            body:     $this->body,
            data:     $this->data,
            priority: $this->priority,
        );
    }
}
