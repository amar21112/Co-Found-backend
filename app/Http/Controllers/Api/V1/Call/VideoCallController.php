<?php

namespace App\Http\Controllers\Api\V1\Call;

use App\Http\Controllers\Controller;
use App\Http\Requests\Call\InitiateCallRequest;
use App\Http\Resources\Call\VideoCallResource;
use App\Services\Call\VideoCallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoCallController extends Controller
{
    public function __construct(
        private readonly VideoCallService $callService,
    ) {}

    // GET /api/v1/calls
    public function index(Request $request): JsonResponse
    {
        $calls = $this->callService->listForUser(
            user:    $request->user(),
            filters: $request->only(['status']),
            perPage: min((int) $request->input('per_page', 15), 50),
        );

        return response()->json([
            'status' => 'success',
            'data'   => VideoCallResource::collection($calls->items()),
            'meta'   => [
                'current_page' => $calls->currentPage(),
                'per_page'     => $calls->perPage(),
                'total'        => $calls->total(),
                'last_page'    => $calls->lastPage(),
            ],
        ]);
    }

    // POST /api/v1/calls
    public function initiate(InitiateCallRequest $request): JsonResponse
    {
        $call = $this->callService->initiate(
            initiator: $request->user(),
            dto:       $request->getDto(),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Call initiated successfully.',
            'data'    => new VideoCallResource($call),
        ], 201);
    }

    // GET /api/v1/calls/{id}
    public function show(string $id): JsonResponse
    {
        $call = $this->callService->show($id);

        return response()->json([
            'status' => 'success',
            'data'   => new VideoCallResource($call),
        ]);
    }

    // POST /api/v1/calls/{id}/join
    public function join(Request $request, string $id): JsonResponse
    {
        $call    = $this->callService->show($id);
        $updated = $this->callService->join($call, $request->user());

        return response()->json([
            'status'  => 'success',
            'message' => 'Joined the call successfully.',
            'data'    => new VideoCallResource($updated),
        ]);
    }

    // POST /api/v1/calls/{id}/leave
    public function leave(Request $request, string $id): JsonResponse
    {
        $call    = $this->callService->show($id);
        $updated = $this->callService->leave($call, $request->user());

        return response()->json([
            'status'  => 'success',
            'message' => 'You have left the call.',
            'data'    => new VideoCallResource($updated),
        ]);
    }

    // PATCH /api/v1/calls/{id}/end
    public function end(Request $request, string $id): JsonResponse
    {
        $call    = $this->callService->show($id);
        $updated = $this->callService->end($call, $request->user());

        return response()->json([
            'status'  => 'success',
            'message' => 'Call ended.',
            'data'    => new VideoCallResource($updated),
        ]);
    }

    // PATCH /api/v1/calls/{id}/cancel
    public function cancel(Request $request, string $id): JsonResponse
    {
        $call    = $this->callService->show($id);
        $updated = $this->callService->cancel($call, $request->user());

        return response()->json([
            'status'  => 'success',
            'message' => 'Call cancelled.',
            'data'    => new VideoCallResource($updated),
        ]);
    }
}
