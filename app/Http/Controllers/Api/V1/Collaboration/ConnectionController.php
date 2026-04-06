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
    // GET /api/connections
    // =========================================================================

    /**
     * List all accepted connections for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user        = $this->resolveUser($request);
        $connections = $this->connectionService->list($user);

        return response()->json([
            'status' => 'success',
            'data'   => ConnectionResource::collection($connections),
        ]);
    }

    // =========================================================================
    // POST /api/connections
    // =========================================================================

    /**
     * Send a connection request to another user.
     */
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
    // PATCH /api/connections/{connection}/accept
    // =========================================================================

    /**
     * Accept a pending connection request.
     */
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
    // DELETE /api/connections/{connection}
    // =========================================================================

    /**
     * Remove a connection (works for both pending and accepted).
     */
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
