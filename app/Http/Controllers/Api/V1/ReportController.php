<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreReportRequest;
use App\Http\Requests\Report\UpdateReportRequest;
use App\Http\Resources\Report\UserReportResource;
use App\Models\Report;
use App\Services\Report\ReportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $reports = $this->reportService->listOwnReports(
            user: $request->user(),
            filters: $request->only(['status', 'report_type']),
            perPage: min((int) $request->input('per_page', 15), 50),
        );

        return response()->json([
            'status' => 'success',
            'data'   => UserReportResource::collection($reports->items()),
            'meta'   => [
                'current_page' => $reports->currentPage(),
                'last_page'    => $reports->lastPage(),
                'per_page'     => $reports->perPage(),
                'total'        => $reports->total(),
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $report = $this->reportService->showOwnReport($id, $request->user());

        return response()->json([
            'status' => 'success',
            'data'   => new UserReportResource($report),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        $report = $this->reportService->createReport(
            $request->getDto($request->user()->id)
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Report submitted successfully.',
            'data'    => new UserReportResource($report),
        ], Response::HTTP_CREATED);
    }

    /**
     * @throws ConflictException
     */
    public function update(UpdateReportRequest $request, string $id): JsonResponse
    {
        $report = $this->reportService->updateOwnReport(
            $id,
            $request->user(),
            $request->getDto()
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Report updated successfully.',
            'data'    => new UserReportResource($report),
        ]);
    }

    /**
     * Soft withdraw — any report owner
     *
     * @throws ConflictException
     */
    public function withdraw(Request $request, string $id): JsonResponse
    {
        $this->reportService->withdrawOwnReport($id, $request->user());

        return response()->json([
            'status'  => 'success',
            'message' => 'Report withdrawn successfully.',
        ]);
    }

    /**
     * Hard delete — admin only
     * @throws AuthorizationException
     */
    public function destroy(string $id): JsonResponse
    {
        $this->authorize('moderate', Report::class);

        $this->reportService->deleteReport($id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Report permanently deleted.',
        ]);
    }
}
