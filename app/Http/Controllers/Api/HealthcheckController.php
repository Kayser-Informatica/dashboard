<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\System;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HealthcheckController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'ok' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:ok,failed,unknown'],
            'message' => ['nullable', 'string', 'max:1000'],
            'ip' => ['nullable', 'ip'],
            'external_ip' => ['nullable', 'ip'],
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);
        $status = array_key_exists('ok', $validated)
            ? ($validated['ok'] ? 'ok' : 'failed')
            : ($validated['status'] ?? 'ok');

        $explicitIp = $validated['ip'] ?? $validated['external_ip'] ?? null;
        $clientIp = $this->resolveClientIp($request);

        if ($explicitIp !== null && $clientIp !== null && $explicitIp !== $clientIp) {
            return response()->json([
                'message' => 'O IP informado no corpo da requisição não corresponde ao IP de origem detectado.',
                'error' => 'ip_mismatch',
                'detected_ip' => $clientIp,
                'provided_ip' => $explicitIp,
            ], 422);
        }

        $now = now();
        $updateData = [
            'name' => $validated['name'],
            'last_health_status' => $status,
            'last_health_message' => $validated['message'] ?? null,
            'last_health_at' => $now,
            'active' => true,
        ];

        if ($explicitIp !== null) {
            $updateData['external_ip'] = $explicitIp;
            $updateData['last_ip_at'] = $now;
        }

        $system = System::where('slug', $slug)->first();

        if ($system) {
            if ($explicitIp === null && $system->external_ip === null && $clientIp !== null) {
                $updateData['external_ip'] = $clientIp;
                $updateData['last_ip_at'] = $now;
            }
            $system->update($updateData);
        } else {
            if ($explicitIp === null && $clientIp !== null) {
                $updateData['external_ip'] = $clientIp;
                $updateData['last_ip_at'] = $now;
            }
            $updateData['slug'] = $slug;
            $system = System::create($updateData);
        }

        return response()->json([
            'message' => 'Healthcheck recebido com sucesso.',
            'system' => [
                'id' => $system->id,
                'name' => $system->name,
                'slug' => $system->slug,
                'status' => $system->last_health_status,
                'external_ip' => $system->external_ip,
                'checked_at' => $system->last_health_at?->toIso8601String(),
            ],
        ]);
    }

    public function index(): JsonResponse
    {
        $systems = System::query()
            ->withCount('backupLogs')
            ->with(['backupLogs' => fn ($query) => $query->latest('received_at')->limit(5)])
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $systems,
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
