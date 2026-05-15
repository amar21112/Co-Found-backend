<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function paginate(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Project::query()->with(['owner', 'skills', 'roles']);
        if(!empty($filters['is_user_participant'])){

            if($filters['is_user_participant']){
                    $role = $filters['role'] ?? null;

                if ($role === 'owner') {
                    $query->where('owner_id', $user->id);
                } elseif ($role === 'member') {
                    $query->whereHas('teamMembers', fn ($tm) =>
                        $tm->where('user_id', $user->id)->where('is_active', true)
                    )->where('owner_id', '!=', $user->id);
                } elseif ($role === 'admin') {
                    // any project the user can see (owner or team)
                    $query->where(function ($q) use ($user) {
                        $q->where('owner_id', $user->id)
                        ->orWhereHas('teamMembers', fn ($tm) =>
                            $tm->where('user_id', $user->id)
                        );
                    });
                } else {
                    // Default: both owner and active member
                    $query->where(function ($q) use ($user) {
                        $q->where('owner_id', $user->id)
                        ->orWhereHas('teamMembers', fn ($tm) =>
                            $tm->where('user_id', $user->id)->where('is_active', true)
                        );
                    });
                }
            }
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['skill'])) {
            $query->whereHas('skills', fn($q) =>
                $q->where('skill_name', 'like', "%{$filters['skill']}%")
            );
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(fn($q) =>
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('short_description', 'like', "%{$term}%")
            );
        }

        if (isset($filters['accepting_applications'])) {
           $filter_for =  (bool) $filters['accepting_applications'];
            $query->where('is_accepting_applications', $filter_for );
        }

        // Only public projects for the public listing
        $query->where('visibility', 'public');

        $sort = in_array($filters['sort'] ?? '', ['view_count', 'application_count'])
            ? $filters['sort']
            : 'created_at';

        $query->orderByDesc($sort);

        return $query->paginate($perPage);
    }

    public function findById(string $id): ?Project
    {
        return Project::with(['owner', 'skills', 'roles', 'milestones'])
            ->find($id);
    }

    public function findBySlug(string $slug): ?Project
    {
        return Project::with(['owner', 'skills', 'roles'])
            ->where('slug', $slug)
            ->first();
    }

    public function create(array $data): Project
    {
        return Project::create($data);
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);
        return $project->fresh(['owner', 'skills', 'roles', 'milestones']);
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }

    public function incrementViewCount(Project $project): void
    {
        $project->increment('view_count');
    }

    public function existsBySlug(string $slug, ?string $excludeId = null): bool
    {
        $query = Project::where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function paginateForUser(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Project::with(['owner', 'skills', 'roles', 'milestones']);

        // ── Role scope ──────────────────────────────────────────────────────────
        $role = $filters['role'] ?? null;

        if ($role === 'owner') {
            $query->where('owner_id', $user->id);
        } elseif ($role === 'member') {
            $query->whereHas('teamMembers', fn ($tm) =>
                $tm->where('user_id', $user->id)->where('is_active', true)
            )->where('owner_id', '!=', $user->id);
        } elseif ($role === 'admin') {
            // any project the user can see (owner or team)
            $query->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                  ->orWhereHas('teamMembers', fn ($tm) =>
                      $tm->where('user_id', $user->id)
                  );
            });
        } else {
            // Default: both owner and active member
            $query->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                  ->orWhereHas('teamMembers', fn ($tm) =>
                      $tm->where('user_id', $user->id)->where('is_active', true)
                  );
            });
        }

        // ── Common filters ───────────────────────────────────────────────────────
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['skill'])) {
            $query->whereHas('skills', fn ($q) =>
                $q->where('skill_name', 'like', "%{$filters['skill']}%")
            );
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(fn ($q) =>
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('short_description', 'like', "%{$term}%")
            );
        }

        if (isset($filters['accepting_applications'])) {
            $query->where('is_accepting_applications', (bool) $filters['accepting_applications']);
        }

        $sort = in_array($filters['sort'] ?? '', ['view_count', 'application_count'])
            ? $filters['sort']
            : 'created_at';

        return $query->orderByDesc($sort)->paginate($perPage);
    }
}
