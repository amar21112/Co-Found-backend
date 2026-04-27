<?php

use App\Http\Controllers\Api\V1\Collaboration\ConnectionController;
use App\Http\Controllers\Api\V1\Collaboration\InvitationController;
use App\Http\Controllers\Api\V1\Collaboration\MatchController;
use App\Http\Controllers\Api\V1\Collaboration\RatingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Collaboration Routes — v1
|--------------------------------------------------------------------------
|
| All collaboration actions require a real, verified user.
| Guests may not send connections, invitations, or ratings.
|
*/

Route::middleware(['auth:sanctum', 'no.guest', 'verified'])->group(function () {

    // ── Connections ───────────────────────────────────────────────────────────

    Route::prefix('connections')->group(function () {
        Route::get('/',                        [ConnectionController::class, 'index']);   // list (filterable)
        Route::post('/',                       [ConnectionController::class, 'store']);   // send request
        Route::patch('/{connection}/accept',   [ConnectionController::class, 'accept']);  // accept (recipient only)
        Route::patch('/{connection}/reject',   [ConnectionController::class, 'reject']);  // reject → auto-deletes (recipient only)
        Route::patch('/{connection}/block',    [ConnectionController::class, 'block']);   // block (either party)
        Route::delete('/{connection}',         [ConnectionController::class, 'destroy']); // remove
    });

    // ── Invitations ───────────────────────────────────────────────────────────

    Route::prefix('invitations')->group(function () {
        Route::get('/',                           [InvitationController::class, 'index']);    // list (filterable)
        Route::post('/',                          [InvitationController::class, 'store']);    // send
        Route::patch('/{invitation}/respond',     [InvitationController::class, 'respond']);  // accept or decline
        Route::patch('/{invitation}/withdraw',    [InvitationController::class, 'withdraw']); // withdraw (sender only)
    });

    // ── Matches ───────────────────────────────────────────────────────────────

    // Note: {matchId} not {match} — 'match' is a PHP 8 reserved keyword.
    Route::prefix('matches')->group(function () {
        Route::get('/',                    [MatchController::class, 'index']);
        Route::patch('/{matchId}/view',    [MatchController::class, 'view']);
        Route::patch('/{matchId}/save',    [MatchController::class, 'save']);
        Route::post('/{matchId}/feedback', [MatchController::class, 'feedback']);
    });

    // ── Ratings ───────────────────────────────────────────────────────────────

    Route::post('ratings',             [RatingController::class, 'store']);   // rate a collaborator
    Route::put('ratings/{rating}',     [RatingController::class, 'update']);  // update own rating
    Route::delete('ratings/{rating}',  [RatingController::class, 'destroy']); // delete own rating

    Route::get('users/{user}/ratings', [RatingController::class, 'index']);   // view ratings received by user
});
