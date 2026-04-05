<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\UpdateTeamMemberRequest;
use App\Http\Resources\Project\TeamMemberResource;
use App\Models\Project;
use App\Services\Project\ProjectTeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectTeamController extends Controller
{
    public function __construct(
        private readonly ProjectTeamService $service,
    ) {}

    /**
     * GET /api/v1/projects/{project}/team
     * List team members. Accessible to all project members.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $activeOnly = filter_var($request->input('active_only', true), FILTER_VALIDATE_BOOLEAN);

        $members = $this->service->list($project, $activeOnly);

        return response()->json([
            'data' => TeamMemberResource::collection($members),
        ]);
    }

    /**
     * PUT /api/v1/projects/{project}/team/{userId}
     * Update a team member's role or permissions. Admin/Owner only.
     */
    public function update(UpdateTeamMemberRequest $request, Project $project, string $userId): JsonResponse
    {
        $this->authorize('manageTeam', $project);

        $member = $this->service->updateMember($project, $userId, $request->validated());

        return response()->json([
            'message' => 'Team member updated.',
            'data'    => new TeamMemberResource($member),
        ]);
    }

    /**
     * DELETE /api/v1/projects/{project}/team/{userId}
     * Remove a team member. Admin/Owner only. Cannot remove the project owner.
     */
    public function destroy(Request $request, Project $project, string $userId): JsonResponse
    {
        $this->authorize('manageTeam', $project);

        $this->service->removeMember($project, $userId, $request->user());

        return response()->json(['message' => 'Team member removed.'], 200);
    }

    /**
     * POST /api/v1/projects/{project}/team/leave
     * Leave the project. Owner must transfer ownership before leaving.
     */
    public function leave(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $this->service->leave($project, $request->user());

        return response()->json(['message' => 'You have left the project.'], 200);
    }
}
