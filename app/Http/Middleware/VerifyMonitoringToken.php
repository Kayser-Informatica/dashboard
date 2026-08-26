<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMonitoringToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = (string) config('services.monitoring.token');
        $providedToken = (string) ($request->bearerToken() ?: $request->header('X-Monitoring-Token'));

        if ($configuredToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            return response()->json([
                'message' => 'Token de monitoramento inválido ou ausente.',
            ], 401);
        }

        return $next($request);
    }
}
