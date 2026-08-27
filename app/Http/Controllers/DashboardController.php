<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\MonitoredService;
use App\Models\ServiceLog;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $services = MonitoredService::query()
            ->with([
                'client',
                'serviceLogs' => fn ($logQuery) => $logQuery->latest('received_at')->limit(5),
            ])
            ->orderBy('name')
            ->get();

        $metrics = [
            'clients_count' => Client::count(),
            'total' => $services->count(),
            'online' => $services->filter(fn ($s) => $s->computed_status === 'ok')->count(),
            'attention' => $services->filter(fn ($s) => in_array($s->computed_status, ['failed', 'overdue', 'unknown']))->count(),
            'logs_today' => ServiceLog::query()->whereDate('received_at', today())->count(),
        ];

        return view('dashboard', [
            'services' => $services,
            'metrics' => $metrics,
            'refreshInterval' => config('services.dashboard.refresh_interval', 30),
        ]);
    }
}
