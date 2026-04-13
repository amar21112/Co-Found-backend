<?php

namespace App\Services;

use App\Models\User;
use App\Models\PortfolioItem;
use App\Models\PortfolioSkill;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PortfolioService
{
    /**
     * List portfolio items for a user.
     *
     * Non-owners only see public items.
     *
     * Filters  : search (title, description), item_type, visibility (owner only),
     *            is_featured, skill (skill_name inside portfolio_skills)
     * Sort     : sort_by (title | created_at | updated_at), sort_dir (asc | desc)
     */
    public function listItems(User $viewer, User $owner, array $filters = []): Collection
    {
        $query = $owner->portfolioItems()->with('skills');

        // Non-owners can only see public items
        if ($viewer->id !== $owner->id) {
            $query->where('visibility', 'public');
        } elseif (! empty($filters['visibility'])) {
            // Owner can filter by visibility
            $query->where('visibility', $filters['visibility']);
        }

        // Search by title or description
        if (! empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'LIKE', $term)
                    ->orWhere('description', 'LIKE', $term);
            });
        }

        // Filter by item type
        if (! empty($filters['item_type'])) {
            $query->where('item_type', $filters['item_type']);
        }

        // Filter by featured status
        if (isset($filters['is_featured']) && $filters['is_featured'] !== '') {
            $query->where('is_featured', filter_var($filters['is_featured'], FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by skill tag
        if (! empty($filters['skill'])) {
            $query->whereHas('skills', function ($q) use ($filters) {
                $q->where('skill_name', 'LIKE', '%' . $filters['skill'] . '%');
            });
        }

        // Sorting — whitelist allowed columns
        $allowed = ['title', 'created_at', 'updated_at'];
        $sortBy  = in_array($filters['sort_by'] ?? '', $allowed) ? $filters['sort_by'] : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->get();
    }

    /**
     * Create a new portfolio item, optionally attaching skills.
     */
    public function store(User $user, array $data): PortfolioItem
    {
        return DB::transaction(function () use ($user, $data) {
            $skills = $data['skills'] ?? [];
            unset($data['skills']);

            $item = $user->portfolioItems()->create($data);
            $this->syncSkills($item, $skills);

            return $item->load('skills');
        });
    }

    /**
     * Update an owned portfolio item.
     */
    public function update(User $user, PortfolioItem $item, array $data): PortfolioItem
    {
        $this->authorizeOwnership($user, $item);

        return DB::transaction(function () use ($item, $data) {
            $skills = $data['skills'] ?? null;
            unset($data['skills']);

            $item->update($data);

            if ($skills !== null) {
                $this->syncSkills($item, $skills);
            }

            return $item->load('skills');
        });
    }

    /**
     * Delete an owned portfolio item along with its skill tags.
     */
    public function delete(User $user, PortfolioItem $item): void
    {
        $this->authorizeOwnership($user, $item);

        DB::transaction(function () use ($item) {
            $item->skills()->delete();
            $item->delete();
        });
    }

    private function syncSkills(PortfolioItem $item, array $skillNames): void
    {
        $item->skills()->delete();

        foreach (array_unique($skillNames) as $name) {
            PortfolioSkill::create([
                'portfolio_item_id' => $item->id,
                'skill_name'        => $name,
            ]);
        }
    }

    private function authorizeOwnership(User $user, PortfolioItem $item): void
    {
        if ($item->user_id !== $user->id) {
            abort(403, 'You do not own this portfolio item.');
        }
    }
}
