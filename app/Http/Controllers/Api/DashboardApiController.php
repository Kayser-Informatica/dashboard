<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BackupLog;
use App\Models\System;
use Illuminate\Http\JsonResponse;

class DashboardApiController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $systems = System::query()
            ->with(['backupLogs' => fn ($query) => $query->latest('received_at')->limit(5)])
            ->withCount('backupLogs')
            ->orderBy('name')
            ->get();

        $metrics = [
            'total' => $systems->count(),
            'online' => $systems->where('last_health_status', 'ok')->count(),
            'attention' => $systems->whereIn('last_health_status', ['failed', 'unknown'])->count(),
            'backups' => BackupLog::query()->whereDate('received_at', today())->count(),
        ];

        $systemsData = $systems->map(function (System $system) {
            return [
                'id' => $system->id,
                'name' => $system->name,
                'slug' => $system->slug,
                'external_ip' => $system->external_ip ?? 'Não informado',
                'last_health_status' => $system->last_health_status ?? 'unknown',
                'last_health_status_label' => match ($system->last_health_status) {
                    'ok' => 'Operacional',
                    'failed' => 'Falha',
                    default => 'Sem dados',
                },
                'last_health_message' => $system->last_health_message,
                'last_health_at' => $system->last_health_at?->toIso8601String(),
                'last_health_at_human' => $system->last_health_at?->diffForHumans() ?? 'Aguardando',
                'last_backup_at' => $system->last_backup_at?->toIso8601String(),
                'last_backup_at_human' => $system->last_backup_at?->diffForHumans() ?? 'Nenhum recebido',
                'backup_logs_count' => $system->backup_logs_count,
                'backup_logs' => $system->backupLogs->map(function (BackupLog $backup) {
                    return [
                        'id' => $backup->id,
                        'original_filename' => $backup->original_filename,
                        'file_size' => $backup->file_size,
                        'file_size_formatted' => number_format($backup->file_size / 1024, 1, ',', '.') . ' KB',
                        'status' => $backup->status,
                        'received_at' => $backup->received_at?->toIso8601String(),
                        'received_at_formatted' => $backup->received_at?->format('d/m H:i') ?? '-',
                    ];
                }),
            ];
        });

        return response()->json([
            'metrics' => $metrics,
            'systems' => $systemsData,
            'server_time' => now()->format('d/m/Y H:i:s'),
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}
