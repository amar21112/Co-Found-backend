<?php

namespace App\Providers;

use App\Models\IdentityVerification;
use App\Models\UserRestriction;
use App\Policies\AdminPolicy;
use App\Repositories\Contracts\AdminRestrictionRepositoryInterface;
use App\Repositories\Contracts\AdminVerificationRepositoryInterface;
use App\Repositories\Eloquent\AdminRestrictionRepository;
use App\Repositories\Eloquent\AdminVerificationRepository;
use App\Services\Admin\AdminActionLogger;
use App\Services\Admin\AdminRestrictionService;
use App\Services\Admin\AdminVerificationService;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Repositories ──────────────────────────────────────────────────────
        $this->app->bind(
            AdminVerificationRepositoryInterface::class,
            AdminVerificationRepository::class
        );

        $this->app->bind(
            AdminRestrictionRepositoryInterface::class,
            AdminRestrictionRepository::class
        );

        // ── Logger (shared across admin services) ─────────────────────────────
        $this->app->singleton(AdminActionLogger::class);

        // ── Services ──────────────────────────────────────────────────────────
        $this->app->bind(AdminVerificationService::class, function ($app) {
            return new AdminVerificationService(
                $app->make(AdminVerificationRepositoryInterface::class),
                $app->make(UserRepositoryInterface::class),
                $app->make(AdminActionLogger::class),
            );
        });

        $this->app->bind(AdminRestrictionService::class, function ($app) {
            return new AdminRestrictionService(
                $app->make(AdminRestrictionRepositoryInterface::class),
                $app->make(UserRepositoryInterface::class),
                $app->make(AdminActionLogger::class),
            );
        });
    }

    public function boot(): void
    {
        // ── Policies ──────────────────────────────────────────────────────────
        // AdminPolicy gates both models using the same `moderate` ability.
        Gate::policy(IdentityVerification::class, AdminPolicy::class);
        Gate::policy(UserRestriction::class,      AdminPolicy::class);
    }
}
