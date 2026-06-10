<?php

namespace App\Providers;

use App\Services\ML\MlMatchingService;
use App\Services\ML\MlServiceClient;
use App\Services\MatchService;
use Illuminate\Support\ServiceProvider;

/**
 * Binds all ML-layer classes into the container.
 */
class MlServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MlServiceClient::class);

        $this->app->bind(MlMatchingService::class, function ($app) {
            return new MlMatchingService(
                $app->make(MlServiceClient::class),
                $app->make(MatchService::class),
            );
        });
    }

    public function boot(): void {}
}
