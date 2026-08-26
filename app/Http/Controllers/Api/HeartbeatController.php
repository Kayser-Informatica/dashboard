<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ServiceAlertMail;
use App\Mail\ServiceRecoveredMail;
use App\Models\Client;
use App\Models\MonitoredService;
use App\Models\ServiceLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HeartbeatController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->attributes->get('client');

        $validated = $request->validate([
            'service' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'interval_minutes' => ['nullable', 'integer', 'min:1', 'max:525600'],
            'grace_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'notification_emails' => ['nullable', 'string', 'max:500'],
            'ok' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:ok,failed,warning,received'],
            'message' => ['nullable', 'string', 'max:2000'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'log_file' => ['nullable', 'file', 'max:10240'], // 10MB
        ]);

        $serviceSlug = $validated['slug'] ?? Str::slug($validated['service']);
        $status = array_key_exists('ok', $validated)
            ? ($validated['ok'] ? 'ok' : 'failed')
            : ($validated['status'] ?? 'ok');

        $clientIp = $this->resolveClientIp($request);
        $now = now();

        /** @var MonitoredService $service */
        $service = MonitoredService::firstOrNew([
            'client_id' => $client->id,
            'slug' => $serviceSlug,
        ]);

        $previousWasInAlert = $service->is_in_alert;

        $service->name = $validated['service'];
        $service->last_ping_at = $now;
        $service->last_status = $status;
        $service->last_message = $validated['message'] ?? $service->last_message;
        $service->last_ip = $clientIp ?? $service->last_ip;
        $service->active = true;

        if (isset($validated['interval_minutes'])) {
            $service->expected_interval_minutes = (int) $validated['interval_minutes'];
        }

        if (isset($validated['grace_minutes'])) {
            $service->grace_period_minutes = (int) $validated['grace_minutes'];
        }

        if (isset($validated['notification_emails'])) {
            $service->notification_emails = $validated['notification_emails'];
        }

        if (isset($validated['duration_seconds'])) {
            $service->last_duration_seconds = (int) $validated['duration_seconds'];
        }

        $serviceLog = null;

        // Processamento de upload de arquivo de log se enviado
        if ($request->hasFile('log_file')) {
            $file = $request->file('log_file');
            $storedPath = $file->store('service-logs');
            $content = (string) file_get_contents($file->getRealPath());

            $serviceLog = new ServiceLog([
                'status' => $status,
                'original_filename' => Str::limit($file->getClientOriginalName(), 255, ''),
                'stored_path' => $storedPath,
                'file_size' => $file->getSize() ?: 0,
                'log_excerpt' => Str::limit(trim($content), 5000),
                'message' => $validated['message'] ?? null,
                'duration_seconds' => $validated['duration_seconds'] ?? null,
                'ip' => $clientIp,
                'received_at' => $now,
            ]);
        }

        // Lógica de alerta por e-mail
        $emails = $service->getNotificationEmailsArray();

        if ($status === 'failed') {
            $service->is_in_alert = true;
            $service->last_alert_sent_at = $now;

            if (! empty($emails)) {
                try {
                    $details = $validated['message'] ?? null;
                    if ($serviceLog && $serviceLog->log_excerpt) {
                        $details = ($details ? "{$details}\n\n" : '') . "--- Trecho do Log Anexo ---\n" . $serviceLog->log_excerpt;
                    }
                    Mail::to($emails)->send(new ServiceAlertMail($service, 'failed', $details));
                } catch (\Throwable $e) {
                    Log::error("Erro ao enviar email de alerta para o servico {$service->id}: " . $e->getMessage());
                }
            }
        } elseif ($status === 'ok') {
            if ($previousWasInAlert) {
                $service->is_in_alert = false;
                if (! empty($emails)) {
                    try {
                        Mail::to($emails)->send(new ServiceRecoveredMail($service));
                    } catch (\Throwable $e) {
                        Log::error("Erro ao enviar email de recuperacao para o servico {$service->id}: " . $e->getMessage());
                    }
                }
            }
        }

        $service->save();

        if ($serviceLog) {
            $serviceLog->monitored_service_id = $service->id;
            $serviceLog->save();

            // Rotação de logs antigos (manter até 10 logs por serviço)
            $oldLogs = ServiceLog::where('monitored_service_id', $service->id)
                ->orderByDesc('received_at')
                ->skip(10)
                ->take(50)
                ->get();

            foreach ($oldLogs as $oldLog) {
                if ($oldLog->stored_path && Storage::exists($oldLog->stored_path)) {
                    Storage::delete($oldLog->stored_path);
                }
                $oldLog->delete();
            }
        }

        return response()->json([
            'message' => 'Heartbeat registrado com sucesso.',
            'service' => [
                'id' => $service->id,
                'client' => $client->name,
                'name' => $service->name,
                'slug' => $service->slug,
                'status' => $service->last_status,
                'interval_minutes' => $service->expected_interval_minutes,
                'grace_minutes' => $service->grace_period_minutes,
                'last_ping_at' => $service->last_ping_at?->toIso8601String(),
                'next_expected_at' => $service->next_expected_at?->toIso8601String(),
                'has_log_attached' => $serviceLog !== null,
            ],
        ]);
    }

    private function resolveClientIp(Request $request): ?string
    {
        $rawIp = $request->header('CF-Connecting-IP')
            ?: ($request->header('X-Real-IP')
            ?: ($request->header('X-Forwarded-For')
            ?: $request->ip()));

        if (is_string($rawIp) && str_contains($rawIp, ',')) {
            $rawIp = trim(explode(',', $rawIp)[0]);
        }

        return filter_var($rawIp, FILTER_VALIDATE_IP) ? $rawIp : null;
    }
}
