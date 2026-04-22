<?php


use App\Http\Controllers\Api\V1\Chat\ConversationController;
use App\Http\Controllers\Api\V1\Chat\FileController;
use App\Http\Controllers\Api\V1\Chat\MessageController;
use App\Http\Controllers\Api\V1\Chat\NotificationController;
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

    // ── Firebase token ────────────────────────────────────────────────────────
    // Must be called once after login so the frontend can authenticate to RTDB.
    Route::get('firebase-token', [ConversationController::class, 'firebaseToken']);

    // ── Conversations ─────────────────────────────────────────────────────────
    Route::prefix('conversations')->group(function () {
        Route::get('/',    [ConversationController::class, 'index']);
        Route::post('/',   [ConversationController::class, 'store']);

        Route::prefix('{conversation}')->group(function () {
            Route::get('/',    [ConversationController::class, 'show']);
            Route::patch('/',  [ConversationController::class, 'update']);
            Route::post('/leave', [ConversationController::class, 'leave']);

            // Participants
            Route::post('/participants',             [ConversationController::class, 'addParticipant']);
            Route::delete('/participants/{userId}',  [ConversationController::class, 'removeParticipant']);

            // Messages
            Route::get('/messages',               [MessageController::class, 'index']);
            Route::post('/messages',              [MessageController::class, 'store']);
            Route::put('/messages/{message}',     [MessageController::class, 'update']);
            Route::delete('/messages/{message}',  [MessageController::class, 'destroy']);
            Route::patch('/messages/{message}/pin',    [MessageController::class, 'pin']);
            Route::post('/messages/{message}/read',    [MessageController::class, 'markRead']);
            Route::post('/read-all',                   [MessageController::class, 'markAllRead']);

            // Reactions
            Route::post('/messages/{message}/reactions',   [MessageController::class, 'addReaction']);
            Route::delete('/messages/{message}/reactions', [MessageController::class, 'removeReaction']);

            // Typing indicators (ephemeral — Firebase only, no MySQL)
            Route::post('/typing',   [MessageController::class, 'typing']);
            Route::delete('/typing', [MessageController::class, 'clearTyping']);

            // Files shared in this conversation
            Route::get('/files',  [FileController::class, 'indexShared']);
            Route::post('/files', [FileController::class, 'share']);
        });
    });

    // ── Files (standalone upload) ─────────────────────────────────────────────
    Route::prefix('files')->group(function () {
        Route::post('/',       [FileController::class, 'upload']);
        Route::get('/{file}',  [FileController::class, 'show']);
        Route::delete('/{file}', [FileController::class, 'destroy']);
    });

    // ── Notifications ─────────────────────────────────────────────────────────
    Route::prefix('notifications')->group(function () {
        Route::get('/',                   [NotificationController::class, 'index']);
        Route::post('/read-all',          [NotificationController::class, 'markAllRead']);
        Route::patch('/{id}/read',        [NotificationController::class, 'markRead']);
        Route::get('/preferences',        [NotificationController::class, 'preferences']);
        Route::put('/preferences',        [NotificationController::class, 'updatePreferences']);
    });
});
