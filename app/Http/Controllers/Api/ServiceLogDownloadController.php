<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MonitoredService;
use App\Models\ServiceLog;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServiceLogDownloadController extends Controller
{
    public function __invoke(MonitoredService $service, ServiceLog $log): StreamedResponse
    {
        if ($log->monitored_service_id !== $service->id) {
            abort(404, 'Log não pertence ao serviço especificado.');
        }

        if (! $log->stored_path || ! Storage::exists($log->stored_path)) {
            abort(404, 'Arquivo de log não encontrado no servidor.');
        }

        return Storage::download($log->stored_path, $log->original_filename ?: 'log.txt');
    }
}
