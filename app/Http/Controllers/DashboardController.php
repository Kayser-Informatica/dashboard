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
        $clients = Client::query()
            ->with([
                'monitoredServices' => function ($query) {
                    $query->with([
                        'serviceLogs' => fn ($logQuery) => $logQuery->latest('received_at')->limit(5),
                    ])->orderBy('name');
                },
            ])
            ->orderBy('name')
            ->get();

        $allServices = $clients->flatMap->monitoredServices;

        $metrics = [
            'clients_count' => $clients->count(),
            'total' => $allServices->count(),
            'online' => $allServices->filter(fn ($s) => $s->computed_status === 'ok')->count(),
            'attention' => $allServices->filter(fn ($s) => in_array($s->computed_status, ['failed', 'overdue', 'unknown']))->count(),
            'logs_today' => ServiceLog::query()->whereDate('received_at', today())->count(),
        ];

        return view('dashboard', [
            'clients' => $clients,
            'metrics' => $metrics,
            'refreshInterval' => config('services.dashboard.refresh_interval', 30),
        ]);
    }
}
