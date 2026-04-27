<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Http\Resources\Admin\AdminUserResource;
use App\Http\Resources\Admin\IdentityVerificationDetailResource;
use App\Http\Resources\Admin\ReportResource;
use App\Models\User;
use App\Services\Admin\AdminUserService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly AdminUserService $userService,
    ) {}

    // GET /api/v1/admin/users

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('administrate', User::class);

        $users = $this->userService->list(
            filters: $request->only([
                'search',
                'role',
                'account_status',
                'identity_verified',
                'email_verified',
            ]),
            perPage: min((int) $request->input('per_page', 15), 50),
        );

        return response()->json([
            'status' => 'success',
            'data'   => AdminUserResource::collection($users->items()),
            'meta'   => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
                'from'         => $users->firstItem(),
                'to'           => $users->lastItem(),
            ],
            'links'  => [
                'first' => $users->url(1),
                'last'  => $users->url($users->lastPage()),
                'prev'  => $users->previousPageUrl(),
                'next'  => $users->nextPageUrl(),
            ],
        ]);
    }

    // GET /api/v1/admin/users/{userId}

    /**
     * @throws AuthorizationException
     */
    public function show(string $userId): JsonResponse
    {
        $this->authorize('administrate', User::class);

        $user = $this->userService->show($userId);

        return response()->json([
            'status' => 'success',
            'data'   => new AdminUserResource($user),
        ]);
    }

    // PATCH /api/v1/admin/users/{userId}

    /**
     * @throws AuthorizationException
     */
    public function update(UpdateAdminUserRequest $request, string $userId): JsonResponse
    {
        $this->authorize('administrate', User::class);

        $target  = $this->userService->show($userId);
        $updated = $this->userService->update(
            target: $target,
            dto: $request->getDto(),
            admin: $request->user(),
            ip: $request->ip(),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'User updated successfully.',
            'data'    => new AdminUserResource($updated),
        ]);
    }

    // DELETE /api/v1/admin/users/{userId}

    /**
     * @throws AuthorizationException
     */
    public function destroy(Request $request, string $userId): JsonResponse
    {
        $this->authorize('administrate', User::class);

        $target = $this->userService->show($userId);

        $this->userService->delete(
            target: $target,
            admin: $request->user(),
            ip: $request->ip(),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'User deleted successfully.',
        ]);
    }

    // GET /api/v1/admin/users/{userId}/verification

    /**
     * Return the full identity verification record for any user.
     *
     * Exposes document images, biometric results, and full review history.
     * Returns data: null when the user has never submitted verification.
     *
     * @throws AuthorizationException
     */
    public function verification(string $userId): JsonResponse
    {
        $this->authorize('moderate', User::class);

        $verification = $this->userService->getVerification($userId);

        return response()->json([
            'status' => 'success',
            'data'   => $verification
                ? new IdentityVerificationDetailResource($verification)
                : null,
        ]);
    }

    // GET /api/v1/admin/users/{userId}/reports

    /**
     * Return all reports filed against a specific user, paginated.
     *
     * @throws AuthorizationException
     */
    public function reports(Request $request, string $userId): JsonResponse
    {
        $this->authorize('moderate', User::class);

        $reports = $this->userService->listReports(
            userId: $userId,
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
}
