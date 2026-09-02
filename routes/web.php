<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)
    ->middleware('dashboard.auth')
    ->name('dashboard');
