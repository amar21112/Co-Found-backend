<?php

namespace App\Providers;

use App\Events\MessageSentEvent;
use App\Firebase\FirebaseService;
use App\Firebase\FirebaseSyncService;
use App\Listeners\NotifyConversationParticipantsListener;
use App\Models\Conversation;
use App\Models\File;
use App\Models\Message;
use App\Policies\ConversationPolicy;
use App\Policies\FilePolicy;
use App\Policies\MessagePolicy;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use App\Repositories\Contracts\FileRepositoryInterface;
use App\Repositories\Contracts\MessageRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Eloquent\ConversationRepository;
use App\Repositories\Eloquent\FileRepository;
use App\Repositories\Eloquent\MessageRepository;
use App\Repositories\Eloquent\NotificationRepository;
use App\Services\Chat\ConversationService;
use App\Services\Chat\FileService;
use App\Services\Chat\MessageService;
use App\Services\Chat\NotificationService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Firebase ─────────────────────────────────────────────────────────
        $this->app->singleton(FirebaseService::class);
        $this->app->singleton(FirebaseSyncService::class);

        // ── Repositories ──────────────────────────────────────────────────────
        $this->app->bind(ConversationRepositoryInterface::class, ConversationRepository::class);
        $this->app->bind(MessageRepositoryInterface::class,      MessageRepository::class);
        $this->app->bind(FileRepositoryInterface::class,         FileRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);

        // ── Services ──────────────────────────────────────────────────────────
        $this->app->bind(ConversationService::class, function ($app) {
            return new ConversationService(
                $app->make(ConversationRepositoryInterface::class),
                $app->make(FirebaseSyncService::class),
            );
        });

        $this->app->bind(MessageService::class, function ($app) {
            return new MessageService(
                $app->make(MessageRepositoryInterface::class),
                $app->make(ConversationRepositoryInterface::class),
                $app->make(FirebaseSyncService::class),
            );
        });

        $this->app->bind(FileService::class, function ($app) {
            return new FileService(
                $app->make(FileRepositoryInterface::class),
                $app->make(ConversationRepositoryInterface::class),
                $app->make(FirebaseSyncService::class),
            );
        });

        $this->app->bind(NotificationService::class, function ($app) {
            return new NotificationService(
                $app->make(NotificationRepositoryInterface::class),
                $app->make(FirebaseSyncService::class),
            );
        });
    }

    public function boot(): void
    {
        // ── Policies ──────────────────────────────────────────────────────────
        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(Message::class,      MessagePolicy::class);
        Gate::policy(File::class,         FilePolicy::class);

        // ── Events ────────────────────────────────────────────────────────────
        Event::listen(
            MessageSentEvent::class,
            NotifyConversationParticipantsListener::class,
        );
    }
}
