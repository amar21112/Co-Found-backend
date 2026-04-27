<?php

use App\Http\Controllers\Api\V1\File\FileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Chat & Notifications Module Routes  —  /api/v1
|--------------------------------------------------------------------------
|
| All routes require Sanctum auth.
| Route model binding resolves Conversation, Message, File by UUID.
|
*/

Route::middleware(['auth:sanctum','no.guest', 'verified',])->prefix('v1')->group(function () {

    Route::prefix('files')->group(function () {
        Route::post('/',       [FileController::class, 'upload']);
        Route::get('/{file}',  [FileController::class, 'show']);
        Route::delete('/{file}', [FileController::class, 'destroy']);
    });
});
