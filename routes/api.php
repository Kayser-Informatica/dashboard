<?php

use App\Http\Controllers\Api\BackupLogController;
use App\Http\Controllers\Api\ClientRegisterController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\HealthcheckController;
use App\Http\Controllers\Api\HeartbeatController;
use App\Http\Controllers\Api\ServiceLogDownloadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas de Ingestão de Clientes e Serviços (Heartbeat / Dead Man's Switch)
|--------------------------------------------------------------------------
*/

// Cadastro de Cliente (emite token único de API)
Route::post('/clients/register', ClientRegisterController::class)
    ->middleware('throttle:60,1')
    ->name('api.clients.register');

// Heartbeat / Ping do Serviço (com periodicidade, e-mails de alerta e upload de log)
Route::post('/heartbeat', HeartbeatController::class)
    ->middleware(['client.token', 'throttle:120,1'])
    ->name('api.heartbeat');

// Download do arquivo de log do serviço
Route::get('/services/{service}/logs/{log}/download', ServiceLogDownloadController::class)
    ->name('api.services.logs.download');

// Métricas agregadas do Dashboard para polling em tempo real
Route::get('/dashboard/metrics', DashboardApiController::class)
    ->name('api.dashboard.metrics');


/*
|--------------------------------------------------------------------------
| Rotas Legadas (Compatibilidade)
|--------------------------------------------------------------------------
*/
Route::post('/healthchecks', HealthcheckController::class)
    ->middleware(['monitoring.token', 'throttle:healthchecks'])
    ->name('api.healthchecks.store');

Route::post('/backups/logs', [BackupLogController::class, 'store'])
    ->middleware(['monitoring.token', 'throttle:backup-logs'])
    ->name('api.backups.logs.store');

Route::get('/systems', [HealthcheckController::class, 'index'])
    ->name('api.systems.index');
