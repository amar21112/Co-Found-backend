<?php

use App\Http\Controllers\Api\V1\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Reports Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum','no.guest', 'verified',])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::post('/', [ReportController::class, 'store'])->name('store');
    Route::get('/{id}', [ReportController::class, 'show'])->name('show');
    // Withdraw (user) — PATCH
    Route::patch('/{id}/withdraw', [ReportController::class, 'withdraw']);
    Route::patch('/{id}', [ReportController::class, 'update'])->name('update');

    // Hard delete (admin only) — DELETE
    Route::delete('/{id}', [ReportController::class, 'destroy'])->name('destroy');
});
