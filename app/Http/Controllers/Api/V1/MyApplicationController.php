<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Project\ProjectApplicationResource;
use App\Services\Project\ProjectApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MyApplicationController extends Controller
{
    public function __construct(
        private readonly ProjectApplicationService $service,
    ) {}

    /**
     * GET /api/v1/applications/mine
     * List the authenticated user's own applications.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $applications = $this->service->listForUser(
            user:    $request->user(),
            filters: $request->only(['status']),
            perPage: (int) $request->input('per_page', 15),
        );

        return ProjectApplicationResource::collection($applications);
    }

    /**
     * PATCH /api/v1/applications/{applicationId}/withdraw
     * Withdraw own application. Cannot withdraw if already terminal.
     */
    public function withdraw(Request $request, string $applicationId): JsonResponse
    {
        $application = $this->service->withdraw(
            applicationId: $applicationId,
            applicant:     $request->user(),
        );

        return response()->json([
            'message' => 'Application withdrawn.',
            'data'    => new ProjectApplicationResource($application),
        ]);
    }
}
