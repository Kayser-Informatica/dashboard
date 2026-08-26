<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BackupLog;
use App\Models\System;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BackupLogController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'system' => ['required', 'string', 'max:140'],
            'status' => ['nullable', 'in:success,failed,warning,received'],
            'log_file' => ['required', 'file', 'max:10240'],
        ]);

        $system = System::query()
            ->where('slug', $validated['system'])
            ->orWhere('name', $validated['system'])
            ->first();

        if (! $system) {
            return response()->json([
                'message' => 'Sistema não encontrado. Envie um slug ou nome já cadastrado por healthcheck.',
            ], 404);
        }

        $file = $request->file('log_file');
        $storedPath = $file->store('backup-logs');
        $content = (string) file_get_contents($file->getRealPath());
        $receivedAt = now();

        $backupLog = BackupLog::create([
            'system_id' => $system->id,
            'status' => $validated['status'] ?? 'received',
            'original_filename' => Str::limit($file->getClientOriginalName(), 255, ''),
            'stored_path' => $storedPath,
            'file_size' => $file->getSize() ?: 0,
            'log_excerpt' => Str::limit(trim($content), 5000),
            'received_at' => $receivedAt,
        ]);

        $system->update(['last_backup_at' => $receivedAt]);

        return response()->json([
            'message' => 'Log de backup recebido com sucesso.',
            'backup' => [
                'id' => $backupLog->id,
                'system' => $system->slug,
                'status' => $backupLog->status,
                'filename' => $backupLog->original_filename,
                'size' => $backupLog->file_size,
                'received_at' => $backupLog->received_at->toIso8601String(),
            ],
        ], 201);
    }
}
