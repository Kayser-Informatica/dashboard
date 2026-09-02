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
        $statusPriority = fn (string $status) => match ($status) {
            'failed' => 1,
            'unknown' => 2,
            'ok' => 3,
            default => 4,
        };

        $services = MonitoredService::query()
            ->with([
                'client',
                'serviceLogs' => fn ($logQuery) => $logQuery->latest('received_at')->limit(10),
            ])
            ->get()
            ->sortBy([
                fn ($a, $b) => $statusPriority($a->computed_status) <=> $statusPriority($b->computed_status),
                ['name', 'asc'],
            ])
            ->values();

        $metrics = [
            'clients_count' => Client::count(),
            'total' => $services->count(),
            'online' => $services->filter(fn ($s) => $s->computed_status === 'ok')->count(),
            'attention' => $services->filter(fn ($s) => in_array($s->computed_status, ['failed', 'unknown']))->count(),
            'logs_today' => ServiceLog::query()->whereDate('received_at', today())->count(),
        ];

        $servicesData = $services->map(function (MonitoredService $service) {
            $computedStatus = $service->computed_status;
            $deadline = $service->next_expected_at;

            $statusLabel = match ($computedStatus) {
                'ok' => 'Operacional',
                'failed' => 'Falha',
                default => 'Aguardando',
            };

            return [
                'id' => $service->id,
                'name' => $service->name,
                'slug' => $service->slug,
                'client_id' => $service->client_id,
                'client_name' => $service->client?->name ?? 'Cliente',
                'client_slug' => $service->client?->slug ?? '',
                'interval_minutes' => $service->expected_interval_minutes,
                'grace_minutes' => $service->grace_period_minutes,
                'notification_emails' => $service->notification_emails,
                'computed_status' => $computedStatus,
                'status_label' => $statusLabel,
                'is_overdue' => $service->is_overdue,
                'last_message' => $service->last_message,
                'last_duration_seconds' => $service->last_duration_seconds,
                'last_duration_formatted' => $service->last_duration_seconds !== null ? "{$service->last_duration_seconds}s" : 'Não informada',
                'last_ip' => $service->last_ip ?? 'Não detectado',
                'last_ping_at' => $service->last_ping_at?->toIso8601String(),
                'last_ping_at_formatted' => $service->last_ping_at?->format('d/m/Y H:i:s') ?? 'Nunca',
                'last_ping_at_human' => $service->last_ping_at?->diffForHumans() ?? 'Nunca',
                'next_expected_at' => $deadline?->toIso8601String(),
                'next_expected_at_formatted' => $deadline?->format('d/m/Y H:i:s') ?? 'Sem periodicidade',
                'next_expected_at_human' => $deadline ? ($service->is_overdue ? 'Atrasado há ' . $deadline->diffForHumans(null, true) : 'Previsto ' . $deadline->diffForHumans()) : 'Sem periodicidade',
                'logs_count' => $service->serviceLogs->count(),
                'logs' => $service->serviceLogs->map(function ($log) use ($service) {
                    return [
                        'id' => $log->id,
                        'status' => $log->status,
                        'filename' => $log->original_filename,
                        'file_size' => $log->file_size,
                        'file_size_formatted' => number_format($log->file_size / 1024, 1, ',', '.') . ' KB',
                        'log_excerpt' => $log->log_excerpt,
                        'download_url' => route('api.services.logs.download', ['service' => $service->id, 'log' => $log->id]),
                        'received_at' => $log->received_at?->toIso8601String(),
                        'received_at_formatted' => $log->received_at?->format('d/m/Y H:i:s') ?? '-',
                    ];
                })->values(),
            ];
        })->values();

        $clientsData = Client::query()
            ->with(['monitoredServices'])
            ->orderBy('name')
            ->get()
            ->map(function (Client $client) {
                $clientServices = $client->monitoredServices;
                $total = $clientServices->count();
                $online = $clientServices->filter(fn ($s) => $s->computed_status === 'ok')->count();
                $attention = $clientServices->filter(fn ($s) => in_array($s->computed_status, ['failed', 'unknown']))->count();

                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'slug' => $client->slug,
                    'email' => $client->email,
                    'services_count' => $total,
                    'online_count' => $online,
                    'attention_count' => $attention,
                    'has_attention' => $attention > 0,
                ];
            })
            ->values();

        return view('dashboard', [
            'services' => $services,
            'servicesData' => $servicesData,
            'clientsData' => $clientsData,
            'metrics' => $metrics,
            'refreshInterval' => config('services.dashboard.refresh_interval', 30),
        ]);
    }
}
