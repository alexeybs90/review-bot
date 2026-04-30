<?php

use App\Http\Controllers\Api\CompanyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::delete('company-images/{image}', [CompanyController::class, 'destroyImage']);

Route::apiResources([
    'companies' => CompanyController::class,
]);
