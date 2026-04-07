<?php

use App\Http\Controllers\Api\V1\Collaboration\ConnectionController;
use App\Http\Controllers\Api\V1\Collaboration\InvitationController;
use App\Http\Controllers\Api\V1\Collaboration\MatchController;
use App\Http\Controllers\Api\V1\Collaboration\RatingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Collaboration Routes
|--------------------------------------------------------------------------
|
| Connections, Invitations, Matches, Ratings.
| All routes are prefixed with /api (via RouteServiceProvider).
|
*/

Route::middleware('api')->group(function () {

    // ── Connections ───────────────────────────────────────────────────────────

    Route::prefix('connections')->group(function () {
        Route::get('/',                          [ConnectionController::class, 'index']);   // list accepted connections
        Route::post('/',                         [ConnectionController::class, 'store']);   // send connection request
        Route::patch('/{connection}/accept',     [ConnectionController::class, 'accept']);  // accept request
        Route::delete('/{connection}',           [ConnectionController::class, 'destroy']); // remove connection
    });

    // ── Invitations ───────────────────────────────────────────────────────────

    Route::prefix('invitations')->group(function () {
        Route::get('/',                           [InvitationController::class, 'index']);    // list sent + received
        Route::post('/',                          [InvitationController::class, 'store']);    // send invitation
        Route::patch('/{invitation}/respond',     [InvitationController::class, 'respond']);  // accept or decline
        Route::patch('/{invitation}/withdraw',    [InvitationController::class, 'withdraw']); // withdraw sent invitation
    });

    // ── Matches ───────────────────────────────────────────────────────────────

    Route::prefix('matches')->group(function () {
        Route::get('/',                        [MatchController::class, 'index']);       // list matches
        Route::patch('/{match}/view',          [MatchController::class, 'markViewed']); // mark as viewed
        Route::patch('/{match}/save',          [MatchController::class, 'save']);        // save / unsave
        Route::post('/{match}/feedback',       [MatchController::class, 'feedback']);    // submit feedback
    });

    // ── Ratings ───────────────────────────────────────────────────────────────

    Route::post('ratings',                     [RatingController::class, 'store']);    // rate a collaborator
    Route::put('ratings/{rating}',             [RatingController::class, 'update']);   // update own rating
    Route::delete('ratings/{rating}',          [RatingController::class, 'destroy']);  // delete own rating

    // View ratings received by any user
    Route::get('users/{user}/ratings',         [RatingController::class, 'index']);
});
