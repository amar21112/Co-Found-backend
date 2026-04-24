<?php

namespace App\Http\Middleware;

use App\Firebase\FirebaseSyncService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the authenticated user as online in Firebase on every API request
 * and registers a deferred offline update via Laravel's terminating callback.
 *
 * Register in app/Http/Kernel.php api middleware group:
 *   \App\Http\Middleware\TrackUserPresence::class,
 *
 * NOTE: presence is best-effort. The Firebase client SDK's onDisconnect()
 * on the frontend is the primary mechanism for going offline when a user
 * closes the app. This middleware handles REST-only clients and the
 * initial online signal on page load.
 */
class TrackUserPresence
{
    public function __construct(
        private readonly FirebaseSyncService $firebase,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user()) {
            $this->firebase->setPresenceOnline($request->user()->id);
        }

        return $response;
    }
}
