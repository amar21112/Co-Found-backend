<?php

namespace App\Http\Controllers\Api\V1\Profile;

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

    // GET /api/v1/profile/skills
    public function index(Request $request): JsonResponse
    {
        $user   = $this->resolveUser($request);
        $skills = $this->skillService->listSkills($user, $request->query());
        return response()->json(['status' => 'success', 'data' => SkillResource::collection($skills)]);
    }

    // POST /api/v1/profile/skills
    public function store(StoreSkillRequest $request): JsonResponse
    {
        $user  = $this->resolveUser($request);
        $skill = $this->skillService->store($user, $request->validated());
        return response()->json(['status' => 'success', 'message' => 'Skill added successfully.', 'data' => new SkillResource($skill->load('endorsements.endorser'))], 201);
    }

    // PUT /api/v1/profile/skills/{skill}
    public function update(UpdateSkillRequest $request, UserSkill $skill): JsonResponse
    {
        $user    = $this->resolveUser($request);
        $updated = $this->skillService->update($user, $skill, $request->validated());
        return response()->json(['status' => 'success', 'message' => 'Skill updated successfully.', 'data' => new SkillResource($updated->load('endorsements.endorser'))]);
    }

    // DELETE /api/v1/profile/skills/{skill}
    public function destroy(Request $request, UserSkill $skill): JsonResponse
    {
        $user = $this->resolveUser($request);
        $this->skillService->delete($user, $skill);
        return response()->json(['status' => 'success', 'message' => 'Skill removed successfully.']);
    }

    // POST /api/v1/skills/{skill}/endorse
    public function endorse(Request $request, UserSkill $skill): JsonResponse
    {
        $user        = $this->resolveUser($request);
        $endorsement = $this->skillService->endorse($user, $skill);
        return response()->json(['status' => 'success', 'message' => 'Skill endorsed successfully.', 'data' => ['endorsement_id' => $endorsement->id, 'skill_id' => $skill->id, 'endorsed_by' => $user->id]], 201);
    }

    // DELETE /api/v1/skills/{skill}/endorse
    public function unendorse(Request $request, UserSkill $skill): JsonResponse
    {
        $user = $this->resolveUser($request);
        $this->skillService->unendorse($user, $skill);
        return response()->json(['status' => 'success', 'message' => 'Endorsement removed successfully.']);
    }
}
