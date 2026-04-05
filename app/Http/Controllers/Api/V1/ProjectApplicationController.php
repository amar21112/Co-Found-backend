<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\ListApplicationsRequest;
use App\Http\Requests\Project\ReviewApplicationRequest;
use App\Http\Requests\Project\SubmitApplicationRequest;
use App\Http\Resources\Project\ProjectApplicationResource;
use App\Models\Project;
use App\Services\Project\ProjectApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectApplicationController extends Controller
{
    public function __construct(
        private readonly ProjectApplicationService $service,
    ) {}

    /**
     * GET /api/v1/projects/{project}/applications
     * List all applications for a project. Owner only.
     */
    public function index(ListApplicationsRequest $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewApplications', $project);

        $applications = $this->service->listForProject(
            project: $project,
            filters: $request->validated(),
            perPage: (int) $request->input('per_page', 15),
        );

        return ProjectApplicationResource::collection($applications);
    }

    /**
     * POST /api/v1/projects/{project}/applications
     * Submit an application to join the project.
     */
    public function store(SubmitApplicationRequest $request, Project $project): JsonResponse
    {
        $this->authorize('apply', $project);

        $application = $this->service->apply(
            project:   $project,
            applicant: $request->user(),
            data:      $request->validated(),
        );

        return response()->json([
            'message' => 'Application submitted successfully.',
            'data'    => new ProjectApplicationResource($application),
        ], 201);
    }

    /**
     * GET /api/v1/projects/{project}/applications/{applicationId}
     * Get a single application. Accessible to the applicant and the project owner.
     */
    public function show(Request $request, Project $project, string $applicationId): JsonResponse
    {
        $application = $this->service->show($applicationId);

        $this->authorize('view', $application);

        return response()->json([
            'data' => new ProjectApplicationResource($application),
        ]);
    }

    /**
     * PATCH /api/v1/projects/{project}/applications/{applicationId}/review
     * Accept, reject or mark an application as reviewing. Owner only.
     */
    public function review(ReviewApplicationRequest $request, Project $project, string $applicationId): JsonResponse
    {
        $this->authorize('reviewApplications', $project);

        $application = $this->service->review(
            project:       $project,
            applicationId: $applicationId,
            newStatus:     $request->validated('status'),
            reviewer:      $request->user(),
        );

        return response()->json([
            'message' => 'Application status updated.',
            'data'    => new ProjectApplicationResource($application),
        ]);
    }
}
