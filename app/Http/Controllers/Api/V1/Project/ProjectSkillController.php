<?php

namespace App\Http\Controllers\Api\V1\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\AddProjectSkillRequest;
use App\Http\Requests\Project\UpdateProjectSkillRequest;
use App\Http\Resources\Project\ProjectSkillResource;
use App\Models\Project;
use App\Services\Project\ProjectSkillService;
use Illuminate\Http\JsonResponse;

class ProjectSkillController extends Controller
{
    public function __construct(
        private readonly ProjectSkillService $service,
    ) {}

    /**
     * POST /api/v1/projects/{project}/skills
     * Add a new skill requirement to the project.
     */
    public function store(AddProjectSkillRequest $request, Project $project): JsonResponse
    {
//        $this->authorize('manage', $project);

        $skill = $this->service->add($project, $request->validated());

        return response()->json([
            'message' => 'Skill requirement added.',
            'data'    => new ProjectSkillResource($skill),
        ], 201);
    }

    /**
     * PUT /api/v1/projects/{project}/skills/{skillId}
     * Update an existing skill requirement.
     */
    public function update(UpdateProjectSkillRequest $request, Project $project, string $skillId): JsonResponse
    {
//        $this->authorize('manage', $project);

        $skill = $this->service->update($project, $skillId, $request->validated());

        return response()->json([
            'message' => 'Skill requirement updated.',
            'data'    => new ProjectSkillResource($skill),
        ]);
    }

    /**
     * DELETE /api/v1/projects/{project}/skills/{skillId}
     * Remove a skill requirement from the project.
     */
    public function destroy(Project $project, string $skillId): JsonResponse
    {
//        $this->authorize('manage', $project);

        $this->service->remove($project, $skillId);

        return response()->json(['message' => 'Skill requirement removed.'], 200);
    }
}
