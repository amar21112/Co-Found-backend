<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRestrictionRequest;
use App\Http\Resources\Admin\UserRestrictionResource;
use App\Models\UserRestriction;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Admin\AdminRestrictionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRestrictionController extends Controller
{
    public function __construct(
        private readonly AdminRestrictionService $restrictionService,
        private readonly UserRepositoryInterface $userRepo,
    ) {}

    // GET /api/v1/admin/restrictions

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('moderate', UserRestriction::class);

        $restrictions = $this->restrictionService->list(
            filters: $request->only(['is_active', 'restriction_type', 'user_id']),
            perPage: min((int) $request->input('per_page', 15), 50),
        );

        return response()->json([
            'status' => 'success',
            'data'   => UserRestrictionResource::collection($restrictions->items()),
            'meta'   => [
                'current_page' => $restrictions->currentPage(),
                'per_page'     => $restrictions->perPage(),
                'total'        => $restrictions->total(),
                'last_page'    => $restrictions->lastPage(),
            ],
        ]);
    }

    // GET /api/v1/admin/restrictions/{id}

    /**
     * @throws AuthorizationException
     */
    public function show(string $id): JsonResponse
    {
        $this->authorize('moderate', UserRestriction::class);

        $restriction = $this->restrictionService->show($id);

        return response()->json([
            'status' => 'success',
            'data'   => new UserRestrictionResource($restriction),
        ]);
    }

    // GET /api/v1/admin/users/{userId}/restrictions

    /**
     * @throws AuthorizationException
     */
    public function userRestrictions(Request $request, string $userId): JsonResponse
    {
        $this->authorize('moderate', UserRestriction::class);

        $restrictions = $this->restrictionService->listForUser(
            userId:  $userId,
            filters: $request->only(['is_active']),
            perPage: min((int) $request->input('per_page', 15), 50),
        );

        return response()->json([
            'status' => 'success',
            'data'   => UserRestrictionResource::collection($restrictions->items()),
            'meta'   => [
                'current_page' => $restrictions->currentPage(),
                'per_page'     => $restrictions->perPage(),
                'total'        => $restrictions->total(),
                'last_page'    => $restrictions->lastPage(),
            ],
        ]);
    }

    // POST /api/v1/admin/restrictions

    /**
     * @throws AuthorizationException
     */
    public function store(StoreRestrictionRequest $request): JsonResponse
    {
        $this->authorize('moderate', UserRestriction::class);

        $dto    = $request->getDto();
        $target = $this->userRepo->findById($dto->userId);

        $restriction = $this->restrictionService->store(
            target: $target,
            admin:  $request->user(),
            dto:    $dto,
            ip:     $request->ip(),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Restriction applied successfully.',
            'data'    => new UserRestrictionResource($restriction),
        ], 201);
    }

    // PATCH /api/v1/admin/restrictions/{id}/lift

    /**
     * @throws AuthorizationException
     */
    public function lift(Request $request, string $id): JsonResponse
    {
        $this->authorize('moderate', UserRestriction::class);

        $lifted = $this->restrictionService->lift(
            restrictionId: $id,
            admin:         $request->user(),
            ip:            $request->ip(),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Restriction lifted successfully.',
            'data'    => new UserRestrictionResource($lifted),
        ]);
    }
}
