<?php

namespace App\Services;

use App\Models\User;
use App\Models\PortfolioItem;
use App\Models\PortfolioSkill;
use Illuminate\Support\Facades\DB;

class PortfolioService
{
    /**
     * List all portfolio items for a user.
     * Non-owners only see public items.
     */
    public function listItems(User $viewer, User $owner): \Illuminate\Database\Eloquent\Collection
    {
        $query = $owner->portfolioItems()->with('skills');

        if ($viewer->id !== $owner->id) {
            $query->where('visibility', 'public');
        }

        return $query->latest()->get();
    }

    /**
     * Create a new portfolio item, optionally attaching skills.
     */
    public function store(User $user, array $data): PortfolioItem
    {
        return DB::transaction(function () use ($user, $data) {
            $skills = $data['skills'] ?? [];
            unset($data['skills']);

            /** @var PortfolioItem $item */
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

    // ── Private helpers ──────────────────────────────────────────────────────

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
