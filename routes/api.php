<?php

use App\Http\Controllers\Api\BackupLogController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\HealthcheckController;
use Illuminate\Support\Facades\Route;

Route::post('/healthchecks', HealthcheckController::class)
    ->middleware(['monitoring.token', 'throttle:healthchecks'])
    ->name('api.healthchecks.store');

Route::post('/backups/logs', [BackupLogController::class, 'store'])
    ->middleware(['monitoring.token', 'throttle:backup-logs'])
    ->name('api.backups.logs.store');

Route::get('/systems', [HealthcheckController::class, 'index'])
    ->name('api.systems.index');

Route::get('/dashboard/metrics', DashboardApiController::class)
    ->name('api.dashboard.metrics');

