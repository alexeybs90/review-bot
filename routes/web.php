<?php

use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\ReviewBotController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::controller(ReviewBotController::class)->group(function () {
//    Route::get('/', 'home')->name('home');
    Route::get('/test', 'sendTest')->name('test');
    Route::get('/set-webhook', 'setWebhook')->name('set-webhook');
    Route::post('/review-bot', 'handle')
        ->name('review-bot')->withoutMiddleware([VerifyCsrfToken::class]);
});

Route::get('/', function () { return view('app'); });

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
