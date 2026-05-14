<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\ProjectApplication;
use App\Policies\ProjectApplicationPolicy;
use App\Policies\ProjectPolicy;
use App\Repositories\Contracts\ProjectApplicationRepositoryInterface;
use App\Repositories\Contracts\ProjectMilestoneRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\ProjectRoleRepositoryInterface;
use App\Repositories\Contracts\ProjectSkillRepositoryInterface;
use App\Repositories\Contracts\ProjectTeamRepositoryInterface;
use App\Repositories\Eloquent\ProjectApplicationRepository;
use App\Repositories\Eloquent\ProjectMilestoneRepository;
use App\Repositories\Eloquent\ProjectRepository;
use App\Repositories\Eloquent\ProjectRoleRepository;
use App\Repositories\Eloquent\ProjectSkillRepository;
use App\Repositories\Eloquent\ProjectTeamRepository;
use App\Services\Project\ProjectApplicationService;
use App\Services\Project\ProjectMilestoneService;
use App\Services\Project\ProjectRoleService;
use App\Services\Project\ProjectService;
use App\Services\Project\ProjectSkillService;
use App\Services\Project\ProjectTeamService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ProjectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Repositories ──────────────────────────────────────────────────────
        $this->app->bind(ProjectRepositoryInterface::class,            ProjectRepository::class);
        $this->app->bind(ProjectSkillRepositoryInterface::class,       ProjectSkillRepository::class);
        $this->app->bind(ProjectRoleRepositoryInterface::class,        ProjectRoleRepository::class);
        $this->app->bind(ProjectMilestoneRepositoryInterface::class,   ProjectMilestoneRepository::class);
        $this->app->bind(ProjectTeamRepositoryInterface::class,        ProjectTeamRepository::class);
        $this->app->bind(ProjectApplicationRepositoryInterface::class, ProjectApplicationRepository::class);

        // ── Services ──────────────────────────────────────────────────────────
        $this->app->bind(ProjectService::class, function ($app) {
            return new ProjectService(
                $app->make(ProjectRepositoryInterface::class),
                $app->make(ProjectSkillRepositoryInterface::class),
                $app->make(ProjectRoleRepositoryInterface::class),
                $app->make(ProjectTeamRepositoryInterface::class),
            );
        });

        $this->app->bind(ProjectSkillService::class, function ($app) {
            return new ProjectSkillService(
                $app->make(ProjectSkillRepositoryInterface::class),
            );
        });

        $this->app->bind(ProjectRoleService::class, function ($app) {
            return new ProjectRoleService(
                $app->make(ProjectRoleRepositoryInterface::class),
            );
        });

        $this->app->bind(ProjectMilestoneService::class, function ($app) {
            return new ProjectMilestoneService(
                $app->make(ProjectMilestoneRepositoryInterface::class),
                $app->make(ProjectTeamRepositoryInterface::class),
            );
        });

        $this->app->bind(ProjectTeamService::class, function ($app) {
            return new ProjectTeamService(
                $app->make(ProjectTeamRepositoryInterface::class),
            );
        });

        $this->app->bind(ProjectApplicationService::class, function ($app) {
            return new ProjectApplicationService(
                $app->make(ProjectApplicationRepositoryInterface::class),
                $app->make(ProjectRoleRepositoryInterface::class),
                $app->make(ProjectTeamRepositoryInterface::class),
            );
        });
    }

    public function boot(): void
    {
        // ── Policies ──────────────────────────────────────────────────────────
        Gate::policy(Project::class,            ProjectPolicy::class);
        Gate::policy(ProjectApplication::class, ProjectApplicationPolicy::class);
    }
}
