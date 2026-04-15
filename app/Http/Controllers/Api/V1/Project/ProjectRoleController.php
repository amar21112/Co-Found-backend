<?php

namespace App\Http\Controllers\Api\V1\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\CreateProjectRoleRequest;
use App\Http\Requests\Project\UpdateProjectRoleRequest;
use App\Http\Resources\Project\ProjectRoleResource;
use App\Models\Project;
use App\Services\Project\ProjectRoleService;
use Illuminate\Http\JsonResponse;

class ProjectRoleController extends Controller
{
    public function __construct(
        private readonly ProjectRoleService $service,
    ) {}

    /**
     * GET /api/v1/projects/{project}/roles
     * List all defined roles for the project.
     */
    public function index(Project $project): JsonResponse
    {
//        $this->authorize('view', $project);

        $roles = $this->service->list($project);

        return response()->json([
            'data' => ProjectRoleResource::collection($roles),
        ]);
    }

    /**
     * POST /api/v1/projects/{project}/roles
     * Add a new role to the project.
     */
    public function store(CreateProjectRoleRequest $request, Project $project): JsonResponse
    {
//        $this->authorize('manage', $project);

        $role = $this->service->create($project, $request->validated());

        return response()->json([
            'message' => 'Role created successfully.',
            'data'    => new ProjectRoleResource($role),
        ], 201);
    }

    /**
     * PUT /api/v1/projects/{project}/roles/{roleId}
     * Update a project role.
     */
    public function update(UpdateProjectRoleRequest $request, Project $project, string $roleId): JsonResponse
    {
//        $this->authorize('manage', $project);

        $role = $this->service->update($project, $roleId, $request->validated());

        return response()->json([
            'message' => 'Role updated successfully.',
            'data'    => new ProjectRoleResource($role),
        ]);
    }

    /**
     * DELETE /api/v1/projects/{project}/roles/{roleId}
     * Delete a role. Fails if positions are filled.
     */
    public function destroy(Project $project, string $roleId): JsonResponse
    {
//        $this->authorize('manage', $project);

        $this->service->delete($project, $roleId);

        return response()->json(['message' => 'Role deleted successfully.'], 200);
    }
}
