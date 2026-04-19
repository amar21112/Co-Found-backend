<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks guest-role users from write and social action endpoints.
 *
 * Guests may browse public projects, user listings, and read profiles.
 * Any endpoint that creates, modifies, or acts on data must be protected
 * by this middleware so guests are prompted to register.
 *
 * Alias: `no.guest`
 */
class RestrictGuestAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isGuest()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Please create an account to access this feature.',
                'code'    => 'GUEST_ACCESS_RESTRICTED',
            ], 403);
        }

        return $next($request);
    }
}
