<?php

namespace App\Providers;

use App\Generators\MatchDatasetGenerator;
use App\Repositories\Contracts\MatchRepositoryInterface;
use App\Repositories\Eloquent\MatchRepository;
use App\Services\MatchService;
use Illuminate\Support\ServiceProvider;

class MatchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            MatchRepositoryInterface::class,
            MatchRepository::class
        );

        $this->app->bind(MatchDatasetGenerator::class, fn() => new MatchDatasetGenerator());

        $this->app->bind(MatchService::class, function ($app) {
            return new MatchService(
                $app->make(MatchRepositoryInterface::class),
                $app->make(MatchDatasetGenerator::class),
            );
        });
    }

    public function boot(): void {}
}
