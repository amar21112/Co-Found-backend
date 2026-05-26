<?php

namespace App\Providers;

use App\Firebase\FirebaseService;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Eloquent\NotificationRepository;
use App\Services\Chat\NotificationService;
use Illuminate\Support\ServiceProvider;

class ChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Firebase ─────────────────────────────────────────────────────────
        $this->app->singleton(FirebaseService::class);

        // ── Repositories ──────────────────────────────────────────────────────
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);

        // ── Services ──────────────────────────────────────────────────────────

        $this->app->bind(NotificationService::class, function ($app) {
            return new NotificationService(
                $app->make(NotificationRepositoryInterface::class),
                $app->make(FirebaseService::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
