<?php

namespace App\Http\Controllers\Api\V1\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\CreateProjectRequest;
use App\Http\Requests\Project\ListProjectsRequest;
use App\Http\Requests\Project\MyProjectsRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\Project\ProjectBriefResource;
use App\Http\Resources\Project\ProjectResource;
use App\Models\Project;
use App\Services\Project\ProjectService;
use App\Traits\ResolvesUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    use ResolvesUser; // just to resolve actual user
    public function __construct(
        private readonly ProjectService $service,
    ) {}

    /**
     * GET /api/v1/projects
     * List and search public projects with filters.
     */
    public function index(ListProjectsRequest $request): AnonymousResourceCollection
    {
        if($request->validated('is_user_participant') ){
            $user = $request->user();
           if($user->role == 'guest' || !$user->is_active){
              $request->merge([
                'is_user_participant' => false,
                'role' => 'all'
            ]);
           }
        }

        $projects = $this->service->list(
            user: $request->user(),
            filters: $request->validated(),
            perPage: (int) $request->input('per_page', 15),
        );

        return ProjectBriefResource::collection($projects);
    }

    /**
     * GET /api/v1/my-projects
     * List projects where the authenticated user is owner or active member,
     * with optional filters: role, status, category, skill, search,
     * accepting_applications, sort, per_page.
     */
    public function myProjects(MyProjectsRequest $request): AnonymousResourceCollection
    {
        $filters  = $request->validated();
        $perPage  = (int) ($filters['per_page'] ?? 15);
        $projects = $this->service->myProjects($request->user(), $filters, $perPage);

        return ProjectBriefResource::collection($projects);
    }

    /**
     * POST /api/v1/projects
     * Create a new project. Authenticated user becomes the owner and first team member.
     */
    public function store(CreateProjectRequest $request): JsonResponse
    {
        $project = $this->service->create(
            owner: $request->user(),
            data:  $request->validated(),
        );

        return response()->json([
            'message' => 'Project created successfully.',
            'data'    => new ProjectResource($project),
        ], 201);
    }

    /**
     * GET /api/v1/projects/{id}
     * Get project details. Increments view count on each visit.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $project = $this->service->show($id);
        $this->authorize('view', $project);

        return response()->json([
            'data' => new ProjectResource($project),
        ]);
    }

    /**
     * PUT /api/v1/projects/{id}
     * Update project settings. Owner only.
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $updated = $this->service->update($project, $request->validated());

        return response()->json([
            'message' => 'Project updated successfully.',
            'data'    => new ProjectResource($updated),
        ]);
    }

    /**
     * DELETE /api/v1/projects/{id}
     * Delete the project. Owner only.
     */
    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $this->service->delete($project);

        return response()->json(['message' => 'Project deleted successfully.'], 200);
    }
}
