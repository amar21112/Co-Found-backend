<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Models\User;

interface ProjectRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;

    public function findById(string $id): ?Project;

    public function findBySlug(string $slug): ?Project;

    public function create(array $data): Project;

    public function update(Project $project, array $data): Project;

    public function delete(Project $project): void;

    public function incrementViewCount(Project $project): void;

    public function existsBySlug(string $slug, ?string $excludeId = null): bool;

    public function paginateForUser(User $user, array $filters, int $perPage): LengthAwarePaginator;
}
