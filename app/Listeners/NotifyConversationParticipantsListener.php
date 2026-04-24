<?php

namespace App\Listeners;

use App\Jobs\SendNotificationJob;
use App\Repositories\Contracts\ConversationRepositoryInterface;

/**
 * Listens to message sent events and fans out notifications to all
 * conversation participants except the sender.
 *
 * Wire this in EventServiceProvider:
 *   \App\Events\MessageSentEvent::class => [
 *       \App\Listeners\NotifyConversationParticipantsListener::class,
 *   ],
 */
class NotifyConversationParticipantsListener
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversationRepo,
    ) {}

    public function handle(object $event): void
    {
        $message      = $event->message;
        $conversation = $event->conversation;
        $sender       = $event->sender;

        $participantIds = $this->conversationRepo
            ->getParticipantIds($conversation->id)
            ->reject(fn($id) => $id === $sender->id);

        foreach ($participantIds as $userId) {
            SendNotificationJob::dispatch(
                userId:   $userId,
                type:     'new_message',
                title:    $sender->full_name,
                body:     mb_substr($message->content ?? '📎 File', 0, 80),
                data:     [
                    'conversation_id' => $conversation->id,
                    'message_id'      => $message->id,
                    'sender_id'       => $sender->id,
                ],
                priority: 'high',
            );
        }
    }
}
