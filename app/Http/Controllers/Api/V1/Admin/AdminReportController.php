<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateReportRequest;
use App\Http\Resources\Admin\ReportResource;
use App\Models\Report;
use App\Services\Admin\AdminReportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function __construct(
        private readonly AdminReportService $reportService,
    ) {}

    // GET /api/v1/admin/reports

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('moderate', Report::class);

        $reports = $this->reportService->list(
            filters: $request->only(['status', 'priority', 'report_type', 'reported_user_id', 'assigned_to']),
            perPage: min((int) $request->input('per_page', 15), 50),
        );

        return response()->json([
            'status' => 'success',
            'data'   => ReportResource::collection($reports->items()),
            'meta'   => [
                'current_page' => $reports->currentPage(),
                'last_page'    => $reports->lastPage(),
                'per_page'     => $reports->perPage(),
                'total'        => $reports->total(),
                'from'         => $reports->firstItem(),
                'to'           => $reports->lastItem(),
            ],
            'links'  => [
                'first' => $reports->url(1),
                'last'  => $reports->url($reports->lastPage()),
                'prev'  => $reports->previousPageUrl(),
                'next'  => $reports->nextPageUrl(),
            ],
        ]);
    }

    // GET /api/v1/admin/reports/{id}

    /**
     * @throws AuthorizationException
     */
    public function show(string $id): JsonResponse
    {
        $this->authorize('moderate', Report::class);

        $report = $this->reportService->show($id);

        return response()->json([
            'status' => 'success',
            'data'   => new ReportResource($report),
        ]);
    }

    // PATCH /api/v1/admin/reports/{id}

    /**
     * @throws AuthorizationException
     */
    public function update(UpdateReportRequest $request, string $id): JsonResponse
    {
        $this->authorize('moderate', Report::class);

        $report  = $this->reportService->show($id);
        $updated = $this->reportService->update(
            report: $report,
            dto:    $request->getDto($request->user()->id),
            admin:  $request->user(),
            ip:     $request->ip(),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Report updated successfully.',
            'data'    => new ReportResource($updated),
        ]);
    }
}