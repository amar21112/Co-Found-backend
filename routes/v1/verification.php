<?php

use App\Http\Controllers\Api\V1\Verification\IdentityVerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Identity Verification Routes — /api/v1/verification
|--------------------------------------------------------------------------
|
| Both routes require:
|   auth:sanctum — valid token
|   no.guest     — ephemeral guests cannot submit identity documents
|   verified     — email must be verified before identity verification
|
| Business rules enforced in service:
|   - Max 3 submission attempts per user
|   - Can only resubmit if previous status is 'rejected'
|   - Document images stored privately (never public URLs)
|
*/

Route::middleware(['auth:sanctum', 'no.guest', 'verified'])
    ->prefix('verification')
    ->name('verification.')
    ->group(function () {
        Route::get('/',  [IdentityVerificationController::class, 'show'])->name('show');
        Route::post('/', [IdentityVerificationController::class, 'submit'])->name('submit');
    });
