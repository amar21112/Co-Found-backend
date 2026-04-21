<?php

namespace App\Http\Middleware;

use App\Exceptions\NotAParticipantException;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reusable middleware that aborts early if the auth user is not
 * an active participant of the {conversation} route model.
 *
 * Usage in routes/chat.php:
 *   Route::prefix('{conversation}')
 *       ->middleware('chat.participant')
 *       ->group(function () { ... });
 *
 * Register alias in app/Http/Kernel.php:
 *   'chat.participant' => \App\Http\Middleware\EnsureConversationParticipant::class,
 */
class EnsureConversationParticipant
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversationRepo,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $conversation = $request->route('conversation');

        if ($conversation && $request->user()) {
            if (!$this->conversationRepo->isParticipant($conversation->id, $request->user()->id)) {
                throw new NotAParticipantException();
            }
        }

        return $next($request);
    }
}
