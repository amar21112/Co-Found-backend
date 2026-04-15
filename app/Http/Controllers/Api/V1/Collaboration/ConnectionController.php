<?php

namespace App\Http\Controllers\Api\V1\Collaboration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Collaboration\SendConnectionRequest;
use App\Http\Resources\ConnectionResource;
use App\Models\UserConnection;
use App\Services\ConnectionService;
use App\Traits\ResolvesUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConnectionController extends Controller
{
    use ResolvesUser;

    public function __construct(private readonly ConnectionService $connectionService) {}

    // =========================================================================
    // GET /api/v1/connections
    // =========================================================================

    public function index(Request $request): JsonResponse
    {
        $user        = $this->resolveUser($request);
        $connections = $this->connectionService->list($user, $request->query());

        return response()->json([
            'status' => 'success',
            'data'   => ConnectionResource::collection($connections),
        ]);
    }

    // =========================================================================
    // POST /api/v1/connections
    // =========================================================================

    public function store(SendConnectionRequest $request): JsonResponse
    {
        $user       = $this->resolveUser($request);
        $connection = $this->connectionService->send($user, $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Connection request sent successfully.',
            'data'    => new ConnectionResource($connection->load(['requester', 'recipient'])),
        ], 201);
    }

    // =========================================================================
    // PATCH /api/v1/connections/{connection}/accept
    // =========================================================================

    public function accept(Request $request, UserConnection $connection): JsonResponse
    {
        $user       = $this->resolveUser($request);
        $connection = $this->connectionService->accept($user, $connection);

        return response()->json([
            'status'  => 'success',
            'message' => 'Connection accepted successfully.',
            'data'    => new ConnectionResource($connection),
        ]);
    }

    // =========================================================================
    // PATCH /api/v1/connections/{connection}/reject
    // Recipient rejects a pending request → record is deleted automatically.
    // =========================================================================

    public function reject(Request $request, UserConnection $connection): JsonResponse
    {
        $user = $this->resolveUser($request);
        $this->connectionService->reject($user, $connection);

        return response()->json([
            'status'  => 'success',
            'message' => 'Connection request rejected and removed.',
        ]);
    }

    // =========================================================================
    // PATCH /api/v1/connections/{connection}/block
    // Either party blocks — updates status to blocked,
    // re-assigns requester_id = blocker, recipient_id = blocked user.
    // =========================================================================

    public function block(Request $request, UserConnection $connection): JsonResponse
    {
        $user       = $this->resolveUser($request);
        $connection = $this->connectionService->block($user, $connection);

        return response()->json([
            'status'  => 'success',
            'message' => 'User blocked successfully.',
            'data'    => new ConnectionResource($connection),
        ]);
    }

    // =========================================================================
    // DELETE /api/v1/connections/{connection}
    // =========================================================================

    public function destroy(Request $request, UserConnection $connection): JsonResponse
    {
        $user = $this->resolveUser($request);
        $this->connectionService->remove($user, $connection);

        return response()->json([
            'status'  => 'success',
            'message' => 'Connection removed successfully.',
        ]);
    }
}
