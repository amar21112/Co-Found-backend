<?php

namespace App\Http\Controllers\Api\V1\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\CreateMilestoneRequest;
use App\Http\Requests\Project\UpdateMilestoneRequest;
use App\Http\Resources\Project\ProjectMilestoneResource;
use App\Models\Project;
use App\Services\Project\ProjectMilestoneService;
use Illuminate\Http\JsonResponse;

class ProjectMilestoneController extends Controller
{
    public function __construct(
        private readonly ProjectMilestoneService $service,
    ) {}

    /**
     * GET /api/v1/projects/{project}/milestones
     * List milestones ordered by order_index.
     */
    public function index(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $milestones = $this->service->list($project);

        return response()->json([
            'data' => ProjectMilestoneResource::collection($milestones),
        ]);
    }

    /**
     * POST /api/v1/projects/{project}/milestones
     * Create a new milestone.
     */
    public function store(CreateMilestoneRequest $request, Project $project): JsonResponse
    {
        $this->authorize('manage', $project);

        $milestone = $this->service->create($project, $request->validated());

        return response()->json([
            'message' => 'Milestone created successfully.',
            'data'    => new ProjectMilestoneResource($milestone),
        ], 201);
    }

    /**
     * PUT /api/v1/projects/{project}/milestones/{milestoneId}
     * Update a milestone.
     */
    public function update(UpdateMilestoneRequest $request, Project $project, string $milestoneId): JsonResponse
    {
        $this->authorize('manage', $project);

        $milestone = $this->service->update($project, $milestoneId, $request->validated());

        return response()->json([
            'message' => 'Milestone updated successfully.',
            'data'    => new ProjectMilestoneResource($milestone),
        ]);
    }

    /**
     * DELETE /api/v1/projects/{project}/milestones/{milestoneId}
     * Delete a milestone.
     */
    public function destroy(Project $project, string $milestoneId): JsonResponse
    {
        $this->authorize('manage', $project);

        $this->service->delete($project, $milestoneId);

        return response()->json(['message' => 'Milestone deleted successfully.'], 200);
    }
}
