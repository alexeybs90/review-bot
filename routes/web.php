<?php

use App\Http\Controllers\ReviewBotController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [ReviewBotController::class, 'home'])->name('home');
Route::get('/test', [ReviewBotController::class, 'sendTest'])->name('test');
Route::get('/set-webhook', [ReviewBotController::class, 'setWebhook'])->name('set-webhook');
//Route::get('/review-bot', [ReviewBotController::class, 'handle'])->name('review-bot')->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('/review-bot', [ReviewBotController::class, 'handle'])->name('review-bot1')->withoutMiddleware([VerifyCsrfToken::class]);

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
