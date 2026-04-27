<?php

namespace App\Providers;

use App\Models\AdminAction;
use App\Models\ContentModeration;
use App\Models\IdentityVerification;
use App\Models\Report;
use App\Models\SystemLog;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserRestriction;
use App\Policies\AdminPolicy;

// ── Contracts ─────────────────────────────────────────────────────────────────
use App\Repositories\Contracts\AdminActionLogRepositoryInterface;
use App\Repositories\Contracts\AdminModerationRepositoryInterface;
use App\Repositories\Contracts\AdminReportRepositoryInterface;
use App\Repositories\Contracts\AdminRestrictionRepositoryInterface;
use App\Repositories\Contracts\AdminSettingRepositoryInterface;
use App\Repositories\Contracts\AdminSystemLogRepositoryInterface;
use App\Repositories\Contracts\AdminUserRepositoryInterface;
use App\Repositories\Contracts\AdminVerificationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;

// ── Eloquent implementations ──────────────────────────────────────────────────
use App\Repositories\Eloquent\AdminActionLogRepository;
use App\Repositories\Eloquent\AdminModerationRepository;
use App\Repositories\Eloquent\AdminReportRepository;
use App\Repositories\Eloquent\AdminRestrictionRepository;
use App\Repositories\Eloquent\AdminSettingRepository;
use App\Repositories\Eloquent\AdminSystemLogRepository;
use App\Repositories\Eloquent\AdminUserRepository;
use App\Repositories\Eloquent\AdminVerificationRepository;

// ── Services ──────────────────────────────────────────────────────────────────
use App\Services\Admin\AdminActionLogger;
use App\Services\Admin\AdminActionLogService;
use App\Services\Admin\AdminModerationService;
use App\Services\Admin\AdminReportService;
use App\Services\Admin\AdminRestrictionService;
use App\Services\Admin\AdminSettingService;
use App\Services\Admin\AdminSystemLogService;
use App\Services\Admin\AdminUserService;
use App\Services\Admin\AdminVerificationService;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Repositories ──────────────────────────────────────────────────────

        // Pre-existing (teammate's work)
        $this->app->bind(AdminVerificationRepositoryInterface::class,  AdminVerificationRepository::class);
        $this->app->bind(AdminRestrictionRepositoryInterface::class,   AdminRestrictionRepository::class);

        // New bindings
        $this->app->bind(AdminReportRepositoryInterface::class,        AdminReportRepository::class);
        $this->app->bind(AdminModerationRepositoryInterface::class,    AdminModerationRepository::class);
        $this->app->bind(AdminUserRepositoryInterface::class,          AdminUserRepository::class);
        $this->app->bind(AdminSettingRepositoryInterface::class,       AdminSettingRepository::class);
        $this->app->bind(AdminActionLogRepositoryInterface::class,     AdminActionLogRepository::class);
        $this->app->bind(AdminSystemLogRepositoryInterface::class,     AdminSystemLogRepository::class);

        // ── Shared logger (singleton — one instance for all admin services) ───
        $this->app->singleton(AdminActionLogger::class);

        // ── Services ──────────────────────────────────────────────────────────

        // Pre-existing (teammate's work)
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

        // New services
        $this->app->bind(AdminReportService::class, function ($app) {
            return new AdminReportService(
                $app->make(AdminReportRepositoryInterface::class),
                $app->make(AdminActionLogger::class),
            );
        });

        $this->app->bind(AdminModerationService::class, function ($app) {
            return new AdminModerationService(
                $app->make(AdminModerationRepositoryInterface::class),
                $app->make(AdminActionLogger::class),
            );
        });

        $this->app->bind(AdminUserService::class, function ($app) {
            return new AdminUserService(
                $app->make(AdminUserRepositoryInterface::class),
                $app->make(AdminActionLogger::class),
            );
        });

        $this->app->bind(AdminSettingService::class, function ($app) {
            return new AdminSettingService(
                $app->make(AdminSettingRepositoryInterface::class),
                $app->make(AdminActionLogger::class),
            );
        });

        $this->app->bind(AdminActionLogService::class, function ($app) {
            return new AdminActionLogService(
                $app->make(AdminActionLogRepositoryInterface::class),
            );
        });

        $this->app->bind(AdminSystemLogService::class, function ($app) {
            return new AdminSystemLogService(
                $app->make(AdminSystemLogRepositoryInterface::class),
            );
        });
    }

    public function boot(): void
    {
        // ── Policies ──────────────────────────────────────────────────────────
        // All admin models share the same AdminPolicy.
        // The 'moderate' ability covers moderators+; 'administrate' is admin-only.
        Gate::policy(IdentityVerification::class, AdminPolicy::class);
        Gate::policy(UserRestriction::class,      AdminPolicy::class);
        Gate::policy(Report::class,               AdminPolicy::class);
        Gate::policy(ContentModeration::class,    AdminPolicy::class);
        Gate::policy(User::class,                 AdminPolicy::class);
        Gate::policy(SystemSetting::class,        AdminPolicy::class);
        Gate::policy(AdminAction::class,          AdminPolicy::class);
        Gate::policy(SystemLog::class,            AdminPolicy::class);
    }
}
