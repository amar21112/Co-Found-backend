<?php

namespace App\Http\Controllers\Api\Profile;

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

    // =========================================================================
    // GET /api/profile
    // =========================================================================

    /**
     * Return the authenticated user's own profile with skills and portfolio.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);

        $user->load([
            'skills.endorsements.endorser',
            'portfolioItems.skills',
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => new UserResource($user),
        ]);
    }

    // =========================================================================
    // PUT /api/profile
    // =========================================================================

    /**
     * Update the authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user    = $this->resolveUser($request);
        $updated = $this->profileService->updateProfile($user, $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Profile updated successfully.',
            'data'    => new UserResource($updated),
        ]);
    }

    // =========================================================================
    // POST /api/profile/change-password
    // =========================================================================

    /**
     * Change the authenticated user's password.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $this->resolveUser($request);

        $this->profileService->changePassword(
            $user,
            $request->validated('current_password'),
            $request->validated('password'),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Password changed successfully.',
        ]);
    }

    // =========================================================================
    // GET /api/users/{user}
    // =========================================================================

    /**
     * View any user's public profile.
     * The authenticated user's visibility rules apply to portfolio items.
     */
    public function showUser(Request $request, User $user): JsonResponse
    {
        $viewer  = $this->resolveUser($request);
        $profile = $this->profileService->getPublicProfile($viewer, $user);

        return response()->json([
            'status' => 'success',
            'data'   => new UserResource($profile),
        ]);
    }
}
