<?php

namespace App\Http\Controllers;

use App\Models\BackupLog;
use App\Models\System;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $systems = System::query()
            ->with(['backupLogs' => fn ($query) => $query->latest('received_at')->limit(5)])
            ->withCount('backupLogs')
            ->orderBy('name')
            ->get();

        return view('dashboard', [
            'systems' => $systems,
            'metrics' => [
                'total' => $systems->count(),
                'online' => $systems->where('last_health_status', 'ok')->count(),
                'attention' => $systems->whereIn('last_health_status', ['failed', 'unknown'])->count(),
                'backups' => BackupLog::query()->whereDate('received_at', today())->count(),
            ],
        ]);
    }
}
