<?php

use App\Http\Controllers\EmailPreviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// ── Email template previews (local only) ──────────────────────────────
if (app()->isLocal()) {
    Route::get(
        '/email-preview/{template}',
        EmailPreviewController::class
    )->name('email.preview');
}
