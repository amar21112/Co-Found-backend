<?php

namespace App\Services\Project;

use App\Exceptions\ProjectNotFoundException;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\ProjectSkillRepositoryInterface;
use App\Repositories\Contracts\ProjectRoleRepositoryInterface;
use App\Repositories\Contracts\ProjectTeamRepositoryInterface;
use App\Traits\SendsNotifications;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ProjectService
{
    use SendsNotifications;
    public function __construct(
        private readonly ProjectRepositoryInterface      $projectRepo,
        private readonly ProjectSkillRepositoryInterface $skillRepo,
        private readonly ProjectRoleRepositoryInterface  $roleRepo,
        private readonly ProjectTeamRepositoryInterface  $teamRepo,
    ) {}

    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->projectRepo->paginate($filters, $perPage);
    }

    public function show(string $id): Project
    {
        $project = $this->projectRepo->findById($id);

        if (!$project) {
            throw new ProjectNotFoundException();
        }

        $this->projectRepo->incrementViewCount($project);

        return $project;
    }

    public function create(User $owner, array $data): Project
    {
        $data['owner_id'] = $owner->id;
        $data['slug']     = $this->generateUniqueSlug($data['title']);

        $skills = $data['skills'] ?? [];
        $roles  = $data['roles']  ?? [];

        unset($data['skills'], $data['roles']);

        $project = $this->projectRepo->create($data);

        // Add owner as the first team member
        $this->teamRepo->addMember($project->id, $owner->id, [
            'position'    => 'Founder',
            'permissions' => 'owner',
        ]);

        // Sync required skills
        foreach ($skills as $skill) {
            $this->skillRepo->create($project->id, $skill);
        }

        // Sync defined roles
        foreach ($roles as $role) {
            $this->roleRepo->create($project->id, $role);
        }
        $this->notify(
            userId:   $owner->id,
            type:     'application_accepted',
            title:    'Your application was accepted!',
            body:     "You've joined {$project->title}",
            data:     ['project_id' => $project->id],
            priority: 'high',
        );
        return $this->projectRepo->findById($project->id);
    }

    public function update(Project $project, array $data): Project
    {
        // Re-generate slug only when the title changes
        if (isset($data['title']) && $data['title'] !== $project->title) {
            $data['slug'] = $this->generateUniqueSlug($data['title'], $project->id);
        }

        return $this->projectRepo->update($project, $data);
    }

    public function delete(Project $project): void
    {
        $this->projectRepo->delete($project);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function generateUniqueSlug(string $title, ?string $excludeId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i    = 1;

        while ($this->projectRepo->existsBySlug($slug, $excludeId)) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
