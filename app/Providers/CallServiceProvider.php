<?php

namespace App\Providers;

use App\Repositories\Contracts\ConversationRepositoryInterface;
use App\Repositories\Contracts\ProjectTeamRepositoryInterface;
use App\Repositories\Contracts\VideoCallRepositoryInterface;
use App\Repositories\Eloquent\VideoCallRepository;
use App\Services\Call\VideoCallService;
use Illuminate\Support\ServiceProvider;

class CallServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            VideoCallRepositoryInterface::class,
            VideoCallRepository::class
        );

        $this->app->bind(VideoCallService::class, function ($app) {
            return new VideoCallService(
                $app->make(VideoCallRepositoryInterface::class),
                $app->make(ProjectTeamRepositoryInterface::class),
                $app->make(ConversationRepositoryInterface::class)
            );
        });
    }

    public function boot(): void
    {

    }
}
