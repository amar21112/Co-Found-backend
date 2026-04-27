<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreReportRequest;
use App\Http\Requests\Report\UpdateReportRequest;
use App\Http\Resources\Report\UserReportResource;
use App\Services\Report\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

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
        try {
            $report = $this->reportService->showOwnReport($id, $request->user());

            return response()->json([
                'status' => 'success',
                'data'   => new UserReportResource($report),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    public function store(StoreReportRequest $request): JsonResponse
    {
        try {
            $report = $this->reportService->createReport(
                $request->getDto($request->user()->id)
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Report submitted successfully.',
                'data'    => new UserReportResource($report),
            ], 201);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function update(UpdateReportRequest $request, string $id): JsonResponse
    {
        try {
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
        } catch (InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $this->reportService->withdrawOwnReport($id, $request->user());

            return response()->json([
                'status'  => 'success',
                'message' => 'Report withdrawn successfully.',
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }
}
