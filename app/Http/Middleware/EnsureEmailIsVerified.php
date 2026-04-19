<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Soft-blocks authenticated users whose email is not yet verified.
 *
 * Guests (role=guest) bypass this check entirely — their access tier is
 * governed by the RestrictGuestAccess middleware instead.
 *
 * Apply this middleware to any route that requires a verified email:
 *   - All profile write actions
 *   - All collaboration actions (connections, invitations, ratings)
 *   - All project write actions (create, update, apply)
 *
 * Alias: `verified`
 */
class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Guests have their own access tier — skip email verification check
        if ($user->isGuest()) {
            return $next($request);
        }

        if (! $user->isEmailVerified()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Your email address is not verified. Please check your inbox or request a new verification link.',
                'code'    => 'EMAIL_NOT_VERIFIED',
            ], 403);
        }

        return $next($request);
    }
}
