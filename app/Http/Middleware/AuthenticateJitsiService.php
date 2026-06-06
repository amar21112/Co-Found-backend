<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticate requests from Prosody's mod_reservations module.
 *
 * Prosody sends:  Authorization: Bearer <JITSI_RESERVATION_SECRET>
 *
 * Deliberately separate from Sanctum — Prosody needs no user account
 * and the token never appears in personal_access_tokens.
 */
class AuthenticateJitsiService
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('jitsi.reservation_secret');

        if (! $secret) {
            abort(500, 'Jitsi reservation secret is not configured.');
        }

        $token = $request->bearerToken();

        if (! $token || ! hash_equals($secret, $token)) {
            abort(401, 'Invalid or missing Jitsi reservation token.');
        }

        return $next($request);
    }
}
