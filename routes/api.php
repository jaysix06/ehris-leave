<?php

use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\UserDetailsController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthTokenController::class, 'store'])
    ->middleware('throttle:login')
    ->name('api.auth.login');

Route::middleware('auth:sanctum')
    ->get('user-details', UserDetailsController::class)
    ->name('api.user-details');

Route::middleware('auth:sanctum')
    ->post('auth/logout', [AuthTokenController::class, 'destroy'])
    ->name('api.auth.logout');
