<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Repositories\Contracts\PasswordResetRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\PasswordResetRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Services\Auth\AuthService;
use App\Services\Auth\EmailVerificationService;
use App\Services\Auth\PasswordResetService;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    public function register(): void
    {
        // ── Repositories ──────────────────────────────────────────────────────
        $this->app->bind(UserRepositoryInterface::class,         UserRepository::class);
        $this->app->bind(PasswordResetRepositoryInterface::class, PasswordResetRepository::class);

        // ── Services ──────────────────────────────────────────────────────────
        $this->app->bind(EmailVerificationService::class, function ($app) {
            return new EmailVerificationService(
                $app->make(UserRepositoryInterface::class),
            );
        });

        $this->app->bind(AuthService::class, function ($app) {
            return new AuthService(
                $app->make(UserRepositoryInterface::class),
                $app->make(EmailVerificationService::class),
            );
        });

        $this->app->bind(PasswordResetService::class, function ($app) {
            return new PasswordResetService(
                $app->make(UserRepositoryInterface::class),
                $app->make(PasswordResetRepositoryInterface::class),
            );
        });
    }

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        //
    }
}
