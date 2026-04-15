<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ProfileService;
use App\Traits\ResolvesUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ResolvesUser;

    public function __construct(private readonly ProfileService $profileService) {}

    // GET /api/v1/profile
    public function show(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        $user->load(['skills.endorsements.endorser', 'portfolioItems.skills']);
        return response()->json(['status' => 'success', 'data' => new UserResource($user)]);
    }

    // PUT /api/v1/profile
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user    = $this->resolveUser($request);
        $updated = $this->profileService->updateProfile($user, $request->validated());
        return response()->json(['status' => 'success', 'message' => 'Profile updated successfully.', 'data' => new UserResource($updated)]);
    }

    // POST /api/v1/profile/change-password
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        $this->profileService->changePassword($user, $request->validated('current_password'), $request->validated('password'));
        return response()->json(['status' => 'success', 'message' => 'Password changed successfully.']);
    }

    // GET /api/v1/users — searchable directory
    public function index(Request $request): JsonResponse
    {
        $users = $this->profileService->searchUsers($request->query());
        return response()->json([
            'status' => 'success',
            'data'   => UserResource::collection($users->items()),
            'meta'   => [
                'current_page' => $users->currentPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
                'last_page'    => $users->lastPage(),
            ],
        ]);
    }

    // GET /api/v1/users/{user}
    public function showUser(Request $request, User $user): JsonResponse
    {
        $viewer  = $this->resolveUser($request);
        $profile = $this->profileService->getPublicProfile($viewer, $user);
        return response()->json(['status' => 'success', 'data' => new UserResource($profile)]);
    }
}
