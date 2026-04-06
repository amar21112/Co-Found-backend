<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StoreSkillRequest;
use App\Http\Requests\Profile\UpdateSkillRequest;
use App\Http\Resources\SkillResource;
use App\Models\UserSkill;
use App\Services\SkillService;
use App\Traits\ResolvesUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    use ResolvesUser;

    public function __construct(private readonly SkillService $skillService) {}

    // =========================================================================
    // GET /api/profile/skills
    // =========================================================================

    /**
     * List all skills belonging to the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user   = $this->resolveUser($request);
        $skills = $this->skillService->listSkills($user);

        return response()->json([
            'status' => 'success',
            'data'   => SkillResource::collection($skills),
        ]);
    }

    // =========================================================================
    // POST /api/profile/skills
    // =========================================================================

    /**
     * Add a new skill to the authenticated user's profile.
     */
    public function store(StoreSkillRequest $request): JsonResponse
    {
        $user  = $this->resolveUser($request);
        $skill = $this->skillService->store($user, $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Skill added successfully.',
            'data'    => new SkillResource($skill->load('endorsements.endorser')),
        ], 201);
    }

    // =========================================================================
    // PUT /api/profile/skills/{skill}
    // =========================================================================

    /**
     * Update one of the authenticated user's skills.
     */
    public function update(UpdateSkillRequest $request, UserSkill $skill): JsonResponse
    {
        $user    = $this->resolveUser($request);
        $updated = $this->skillService->update($user, $skill, $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Skill updated successfully.',
            'data'    => new SkillResource($updated->load('endorsements.endorser')),
        ]);
    }

    // =========================================================================
    // DELETE /api/profile/skills/{skill}
    // =========================================================================

    /**
     * Remove one of the authenticated user's skills.
     */
    public function destroy(Request $request, UserSkill $skill): JsonResponse
    {
        $user = $this->resolveUser($request);
        $this->skillService->delete($user, $skill);

        return response()->json([
            'status'  => 'success',
            'message' => 'Skill removed successfully.',
        ]);
    }

    // =========================================================================
    // POST /api/skills/{skill}/endorse
    // =========================================================================

    /**
     * Endorse another user's skill.
     */
    public function endorse(Request $request, UserSkill $skill): JsonResponse
    {
        $user        = $this->resolveUser($request);
        $endorsement = $this->skillService->endorse($user, $skill);

        return response()->json([
            'status'  => 'success',
            'message' => 'Skill endorsed successfully.',
            'data'    => [
                'endorsement_id'  => $endorsement->id,
                'skill_id'        => $skill->id,
                'endorsed_by'     => $user->id,
            ],
        ], 201);
    }

    // =========================================================================
    // DELETE /api/skills/{skill}/endorse
    // =========================================================================

    /**
     * Remove the authenticated user's endorsement from a skill.
     */
    public function unendorse(Request $request, UserSkill $skill): JsonResponse
    {
        $user = $this->resolveUser($request);
        $this->skillService->unendorse($user, $skill);

        return response()->json([
            'status'  => 'success',
            'message' => 'Endorsement removed successfully.',
        ]);
    }
}
