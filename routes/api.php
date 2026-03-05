<?php

use App\Http\Controllers\Api\EmployeeDetailsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api.key', 'throttle:60,1'])
    ->get('employee-details/{hrid}', EmployeeDetailsController::class)
    ->whereNumber('hrid')
    ->name('api.employee-details.show');
