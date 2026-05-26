<?php


use App\Http\Controllers\Api\V1\Chat\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Chat & Notifications Routes  —  /api/v1
|--------------------------------------------------------------------------
|
| The client talks to Firebase RTDB directly for all conversation,
| message, and file operations. Laravel owns notifications only.
|
*/

Route::middleware(['auth:sanctum','no.guest', 'verified',])->prefix('v1')->group(function () {

    Route::prefix('notifications')->group(function () {
        Route::get('/',                   [NotificationController::class, 'index']);
        Route::post('/read-all',          [NotificationController::class, 'markAllRead']);
        Route::patch('/{id}/read',        [NotificationController::class, 'markRead']);
        Route::get('/preferences',        [NotificationController::class, 'preferences']);
        Route::put('/preferences',        [NotificationController::class, 'updatePreferences']);
    });
});
