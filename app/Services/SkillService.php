<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSkill;
use App\Models\SkillEndorsement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class SkillService
{
    /**
     * List skills for a user.
     *
     * Filters  : search (skill_name), proficiency_level, is_approved
     * Sort     : sort_by (skill_name | proficiency_level | years_experience | created_at),
     *            sort_dir (asc | desc)
     */
    public function listSkills(User $user, array $filters = []): Collection
    {
        $query = $user->skills()->with(['endorsements.endorser']);

        // Search by skill name
        if (! empty($filters['search'])) {
            $query->where('skill_name', 'LIKE', '%' . $filters['search'] . '%');
        }

        // Filter by exact proficiency level (1–5)
        if (isset($filters['proficiency_level']) && $filters['proficiency_level'] !== '') {
            $query->where('proficiency_level', (int) $filters['proficiency_level']);
        }

        // Filter by approval status
        if (isset($filters['is_approved']) && $filters['is_approved'] !== '') {
            $query->where('is_approved', filter_var($filters['is_approved'], FILTER_VALIDATE_BOOLEAN));
        }

        // Sorting — whitelist allowed columns
        $allowed = ['skill_name', 'proficiency_level', 'years_experience', 'created_at'];
        $sortBy  = in_array($filters['sort_by'] ?? '', $allowed) ? $filters['sort_by'] : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->get();
    }

    /**
     * Add a new skill to the user's profile.
     *
     * @throws ValidationException
     */
    public function store(User $user, array $data): UserSkill
    {
        $exists = $user->skills()
            ->whereRaw('LOWER(skill_name) = ?', [strtolower($data['skill_name'])])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'skill_name' => ['You already have this skill on your profile.'],
            ]);
        }

        $data['is_approved'] = true;

        return $user->skills()->create($data);
    }

    /**
     * Update an owned skill.
     */
    public function update(User $user, UserSkill $skill, array $data): UserSkill
    {
        $this->authorizeOwnership($user, $skill);
        $skill->update($data);
        return $skill->fresh();
    }

    /**
     * Delete an owned skill along with its endorsements.
     */
    public function delete(User $user, UserSkill $skill): void
    {
        $this->authorizeOwnership($user, $skill);
        $skill->endorsements()->delete();
        $skill->delete();
    }

    /**
     * Endorse a skill.
     *
     * @throws ValidationException
     */
    public function endorse(User $endorser, UserSkill $skill): SkillEndorsement
    {
        if ($endorser->id === $skill->user_id) {
            throw ValidationException::withMessages([
                'skill' => ['You cannot endorse your own skill.'],
            ]);
        }

        $already = SkillEndorsement::where('user_skill_id', $skill->id)
            ->where('endorsed_by_user_id', $endorser->id)
            ->exists();

        if ($already) {
            throw ValidationException::withMessages([
                'skill' => ['You have already endorsed this skill.'],
            ]);
        }

        $endorsement = SkillEndorsement::create([
            'user_skill_id'       => $skill->id,
            'endorsed_by_user_id' => $endorser->id,
        ]);

        // Auto-approve when endorsements reach 5+
        if (! $skill->is_approved) {
            $count = SkillEndorsement::where('user_skill_id', $skill->id)->count();
            if ($count >= 5) {
                $skill->update(['is_approved' => true]);
            }
        }

        return $endorsement;
    }

    /**
     * Remove an endorsement placed by the given user.
     *
     * @throws ValidationException
     */
    public function unendorse(User $endorser, UserSkill $skill): void
    {
        $deleted = SkillEndorsement::where('user_skill_id', $skill->id)
            ->where('endorsed_by_user_id', $endorser->id)
            ->delete();

        if (! $deleted) {
            throw ValidationException::withMessages([
                'skill' => ['You have not endorsed this skill.'],
            ]);
        }
    }

    private function authorizeOwnership(User $user, UserSkill $skill): void
    {
        if ($skill->user_id !== $user->id) {
            abort(403, 'You do not own this skill.');
        }
    }
}
