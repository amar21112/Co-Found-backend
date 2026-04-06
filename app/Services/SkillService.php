<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSkill;
use App\Models\SkillEndorsement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SkillService
{
    /**
     * List all skills for a user, with endorsement counts.
     */
    public function listSkills(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return $user->skills()->with(['endorsements.endorser'])->get();
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

        return $user->skills()->create($data);
    }

    /**
     * Update an owned skill.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(User $user, UserSkill $skill, array $data): UserSkill
    {
        $this->authorizeOwnership($user, $skill);
        $skill->update($data);
        return $skill->fresh();
    }

    /**
     * Delete an owned skill along with its endorsements.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function delete(User $user, UserSkill $skill): void
    {
        $this->authorizeOwnership($user, $skill);
        $skill->endorsements()->delete();
        $skill->delete();
    }

    /**
     * Endorse a skill. A user cannot endorse their own skill,
     * and can only endorse each skill once.
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

        // Auto-approve if endorsements reach 5 or more
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

    // ── Private helpers ──────────────────────────────────────────────────────

    /**
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function authorizeOwnership(User $user, UserSkill $skill): void
    {
        if ($skill->user_id !== $user->id) {
            abort(403, 'You do not own this skill.');
        }
    }
}
