<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminActionResource;
use App\Models\AdminAction;
use App\Services\Admin\AdminActionLogService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminActionLogController extends Controller
{
    public function __construct(
        private readonly AdminActionLogService $logService,
    ) {}

    // GET /api/v1/admin/action-logs

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('moderate', AdminAction::class);

        $logs = $this->logService->list(
            filters: $request->only([
                'admin_id',
                'action_type',
                'target_type',
                'target_id',
                'from',
                'to',
            ]),
            perPage: min((int) $request->input('per_page', 15), 50),
        );

        return response()->json([
            'status' => 'success',
            'data'   => AdminActionResource::collection($logs->items()),
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
