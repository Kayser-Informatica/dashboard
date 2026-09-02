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

        $this->normalizeRequestInput($request);

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
            'log_file' => ['nullable', 'file', 'mimes:txt,log,csv,json,gz,zip', 'max:10240'], // 10MB
        ], [
            'service.required' => 'O campo service (nome do serviço) é obrigatório.',
            'service.string' => 'O nome do serviço deve ser um texto.',
            'service.max' => 'O nome do serviço não pode ultrapassar 120 caracteres.',
            'slug.regex' => 'O slug informado possui formato inválido. Use apenas letras minúsculas, números e hífens.',
            'interval_minutes.integer' => 'O intervalo esperado (interval_minutes) deve ser um número inteiro em minutos.',
            'interval_minutes.min' => 'O intervalo mínimo permitido é de 1 minuto.',
            'grace_minutes.integer' => 'O tempo de tolerância (grace_minutes) deve ser um número inteiro em minutos.',
            'ok.boolean' => 'O campo ok deve ser um valor booleano válido (true/false, 1/0, "ok", "sim", "não").',
            'status.in' => 'O status informado é inválido. Valores aceitos: ok, failed, warning, received.',
            'message.max' => 'A mensagem não pode ultrapassar 2000 caracteres.',
            'duration_seconds.integer' => 'A duração em segundos deve ser um número inteiro.',
            'duration_seconds.min' => 'A duração em segundos não pode ser negativa.',
            'log_file.file' => 'O anexo log_file deve ser um arquivo válido.',
            'log_file.mimes' => 'O arquivo de log deve possuir uma extensão permitida (.log, .txt, .csv, .json, .gz, .zip).',
            'log_file.max' => 'O arquivo de log não pode ultrapassar 10MB.',
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
            $originalFilename = Str::limit($file->getClientOriginalName() ?: 'log.txt', 255, '');
            $fileSize = $file->getSize() ?: 0;

            // Ler o conteúdo antes de armazenar e sanitizar UTF-8 para evitar falhas de encoding
            $rawContent = (string) $file->get();
            if (! mb_check_encoding($rawContent, 'UTF-8')) {
                $rawContent = mb_convert_encoding($rawContent, 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-8');
            }
            $cleanContent = iconv('UTF-8', 'UTF-8//IGNORE', $rawContent) ?: $rawContent;

            $storedPath = $file->store('service-logs');

            $serviceLog = new ServiceLog([
                'status' => $status,
                'original_filename' => $originalFilename,
                'stored_path' => $storedPath,
                'file_size' => $fileSize,
                'log_excerpt' => Str::limit(trim($cleanContent), 5000),
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

    private function normalizeRequestInput(Request $request): void
    {
        if ($request->has('ok')) {
            $rawOk = $request->input('ok');
            if (is_string($rawOk)) {
                $trimmed = strtolower(trim($rawOk));
                if (in_array($trimmed, ['1', 'true', 'ok', 'yes', 'sim', 't', 's', 'success', 'sucesso'], true)) {
                    $request->merge(['ok' => true]);
                } elseif (in_array($trimmed, ['0', 'false', 'fail', 'failed', 'no', 'nao', 'não', 'f', 'n', 'erro', 'error'], true)) {
                    $request->merge(['ok' => false]);
                }
            }
        }
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
