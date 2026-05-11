<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticate requests from the internal ML service via a shared secret.
 *
 * The ML service must send:  Authorization: Bearer <ML_SERVICE_SECRET>
 *
 * Deliberately separate from Sanctum — the ML service needs no user account
 * and the token never appears in personal_access_tokens.
 */
class AuthenticateMlService
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.ml.secret');

        if (! $secret) {
            abort(500, 'ML service secret is not configured.');
        }

        $token = $request->bearerToken();

        if (! $token || ! hash_equals($secret, $token)) {
            abort(401, 'Invalid or missing ML service token.');
        }

        return $next($request);
    }
}
