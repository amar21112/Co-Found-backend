<?php

use App\Http\Controllers\Api\V1\Call\VideoCallController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Video Calls Routes — /api/v1/calls
|--------------------------------------------------------------------------
|
| All routes require auth:sanctum + no.guest + verified.
|
| Status machine:
|   scheduled ──► active   (first participant joins)
|   active    ──► ended    (host ends it, or last participant leaves)
|   scheduled ──► cancelled (host cancels before it starts)
|
*/

Route::middleware(['auth:sanctum', 'no.guest', 'verified'])
    ->prefix('calls')
    ->name('calls.')
    ->group(function () {
        Route::get('/',            [VideoCallController::class, 'index'])->name('index');
        Route::post('/',           [VideoCallController::class, 'initiate'])->name('initiate');
        Route::get('/{id}',        [VideoCallController::class, 'show'])->name('show');
        Route::post('/{id}/join',  [VideoCallController::class, 'join'])->name('join');
        Route::post('/{id}/leave', [VideoCallController::class, 'leave'])->name('leave');
        Route::patch('/{id}/end',  [VideoCallController::class, 'end'])->name('end');
        Route::patch('/{id}/cancel',[VideoCallController::class, 'cancel'])->name('cancel');
    });
