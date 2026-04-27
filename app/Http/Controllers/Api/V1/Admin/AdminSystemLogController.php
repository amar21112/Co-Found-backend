<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\SystemLogResource;
use App\Models\SystemLog;
use App\Services\Admin\AdminSystemLogService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSystemLogController extends Controller
{
    public function __construct(
        private readonly AdminSystemLogService $logService,
    ) {}

    // GET /api/v1/admin/system-logs

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('moderate', SystemLog::class);

        $logs = $this->logService->list(
            filters: $request->only([
                'log_level',
                'component',
                'event_type',
                'user_id',
                'search',
                'from',
                'to',
            ]),
            perPage: min((int) $request->input('per_page', 15), 50),
        );

        return response()->json([
            'status' => 'success',
            'data'   => SystemLogResource::collection($logs->items()),
            'meta'   => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
                'from'         => $logs->firstItem(),
                'to'           => $logs->lastItem(),
            ],
            'links'  => [
                'first' => $logs->url(1),
                'last'  => $logs->url($logs->lastPage()),
                'prev'  => $logs->previousPageUrl(),
                'next'  => $logs->nextPageUrl(),
            ],
        ]);
    }
}
