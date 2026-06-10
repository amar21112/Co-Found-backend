<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticate inbound requests from the self-hosted OCR service.
 *
 * The OCR service must send:  Authorization: Bearer <OCR_SERVICE_SECRET>
 *
 * This is intentionally separate from AuthenticateMlService so that each
 * internal service keeps its own secret and can be rotated independently.
 *
 */
class AuthenticateOcrService
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.ocr.secret');

        if (! $secret) {
            abort(500, 'OCR service secret is not configured.');
        }

        $token = $request->bearerToken();

        if (! $token || ! hash_equals($secret, $token)) {
            abort(401, 'Invalid or missing OCR service token.');
        }

        return $next($request);
    }
}
