<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class DashboardBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $authEnabled = (bool) config('services.dashboard.auth_enabled', false);

        if (! $authEnabled) {
            return $next($request);
        }

        // 1. Verificação de Whitelist de IPs (Acesso direto liberado sem senha)
        $clientIp = $this->resolveClientIp($request);
        $whitelist = $this->getIpWhitelist();

        if ($clientIp && ! empty($whitelist) && IpUtils::checkIp($clientIp, $whitelist)) {
            return $next($request);
        }

        // 2. Verificação de Credenciais HTTP Basic Auth
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
                'message' => 'Acesso não autorizado ao Dashboard ou métricas. Informe as credenciais Basic Auth ou acesse de um IP autorizado na whitelist.',
                'error' => 'unauthorized',
            ], 401, [
                'WWW-Authenticate' => 'Basic realm="Vigilant Dashboard"',
            ]);
        }

        return response('Acesso não autorizado. Informe as credenciais de acesso ao Dashboard ou acesse de um IP autorizado.', 401, [
            'WWW-Authenticate' => 'Basic realm="Vigilant Dashboard"',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function getIpWhitelist(): array
    {
        $rawWhitelist = config('services.dashboard.ip_whitelist');

        if (is_array($rawWhitelist)) {
            return array_values(array_filter(array_map('trim', $rawWhitelist)));
        }

        if (is_string($rawWhitelist) && trim($rawWhitelist) !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $rawWhitelist))));
        }

        return [];
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
