<?php

use App\Http\Controllers\Api\V1\Call\JitsiReservationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Jitsi Internal Routes — /api/v1/jitsi
|--------------------------------------------------------------------------
|
| Called exclusively by Prosody modules on the same VPS.
| Protected by 'auth.jitsi' middleware (Authorization: Bearer <secret>).
| nginx must restrict /api/v1/jitsi/ to 127.0.0.1 only.
|
| Prosody config:
|   reservations_api_prefix = "http://127.0.0.1/api/v1"
|   reservations_api_headers = {
|       ["Authorization"] = "Bearer YOUR_JITSI_RESERVATION_SECRET";
|   }
|
*/

Route::prefix('jitsi')
    ->middleware('auth.jitsi')
    ->name('jitsi.')
    ->group(function () {
        // mod_reservations — room lifecycle
        Route::post('/conference',        [JitsiReservationController::class, 'create'])->name('conference.create');
        Route::get('/conference/{id}',    [JitsiReservationController::class, 'show'])->name('conference.show');
        Route::delete('/conference/{id}', [JitsiReservationController::class, 'destroy'])->name('conference.destroy');

        // mod_cofound_access — per-join identity verification
        Route::post('/participant/verify', [JitsiReservationController::class, 'verifyParticipant'])->name('participant.verify');
    });
