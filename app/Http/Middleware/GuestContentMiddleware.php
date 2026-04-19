<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces the guest content tier — limited browsing before requiring registration.
 *
 * This middleware runs on browse-only routes that guests can access.
 * It does two things:
 *
 * 1. PAGE CAP — Guests may only browse the first N pages of any paginated list.
 *    Requesting page > GUEST_PAGE_LIMIT returns 403 with a register prompt.
 *    (LinkedIn caps at ~3 pages of search results before requiring login.)
 *
 * 2. RESPONSE HEADER — Tags every guest response with X-Guest-Mode: true so
 *    the frontend knows to render the "sign up to see more" UI affordances.
 *
 * Data-field stripping (emails, contact links, etc.) is handled in the
 * resources themselves via $request->user()->isGuest() checks, NOT here,
 * so the stripping is consistent regardless of how the resource is called.
 *
 * Alias: guest.content
 */
class GuestContentMiddleware
{
    /** Maximum page number a guest may request on any paginated endpoint. */
    private const GUEST_PAGE_LIMIT = 2;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only apply to authenticated guest-role users.
        // Unauthenticated requests are handled by auth middleware before this.
        if (! $user || ! $user->isGuest()) {
            return $next($request);
        }

        // ── Page cap enforcement ───────────────────────────────────────────────
        $page = (int) $request->query('page', 1);

        if ($page > self::GUEST_PAGE_LIMIT) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Create a free account to browse more results.',
                'code'    => 'GUEST_PAGE_LIMIT_REACHED',
                'meta'    => [
                    'page_limit'   => self::GUEST_PAGE_LIMIT,
                    'register_url' => '/auth/register',
                ],
            ], 403);
        }

        /** @var Response $response */
        $response = $next($request);

        // ── Tag the response so the frontend can show register prompts ─────────
        $response->headers->set('X-Guest-Mode', 'true');
        $response->headers->set('X-Guest-Page-Limit', (string) self::GUEST_PAGE_LIMIT);

        return $response;
    }
}
