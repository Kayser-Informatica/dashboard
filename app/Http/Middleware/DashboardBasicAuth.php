<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $authEnabled = (bool) config('services.dashboard.auth_enabled', false);

        if (! $authEnabled) {
            return $next($request);
        }

        $expectedUser = (string) config('services.dashboard.username', 'admin');
        $expectedPass = (string) config('services.dashboard.password', '');

        // Se a proteção estiver ligada mas a senha não foi informada no .env, bloqueia por segurança
        if ($expectedPass === '') {
            return response()->json([
                'message' => 'Autenticação do Dashboard habilitada, mas nenhuma senha foi definida no arquivo .env (DASHBOARD_PASSWORD).',
                'error' => 'server_misconfiguration',
            ], 500);
        }

        $user = (string) $request->getUser();
        $password = (string) $request->getPassword();

        if ($user !== '' && $password !== '' && hash_equals($expectedUser, $user) && hash_equals($expectedPass, $password)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Acesso não autorizado ao Dashboard ou métricas. Informe as credenciais Basic Auth.',
                'error' => 'unauthorized',
            ], 401, [
                'WWW-Authenticate' => 'Basic realm="Vigilant Dashboard"',
            ]);
        }

        return response('Acesso não autorizado. Informe as credenciais de acesso ao Dashboard.', 401, [
            'WWW-Authenticate' => 'Basic realm="Vigilant Dashboard"',
        ]);
    }
}
