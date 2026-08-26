<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Alerta de Monitoramento</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f7f6; color: #333; margin: 0; padding: 24px; }
        .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .header-danger { background: #e11d48; color: #fff; padding: 20px 24px; }
        .header-warning { background: #d97706; color: #fff; padding: 20px 24px; }
        .header h1 { margin: 0; font-size: 20px; }
        .content { padding: 24px; line-height: 1.6; }
        .info-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .info-table td { padding: 8px 12px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        .info-table td.label { font-weight: bold; width: 35%; color: #4b5563; }
        .box-log { background: #1e293b; color: #f8fafc; padding: 12px 16px; border-radius: 6px; font-family: monospace; font-size: 12px; white-space: pre-wrap; word-break: break-all; margin-top: 12px; max-height: 250px; overflow-y: auto; }
        .footer { font-size: 12px; color: #9ca3af; text-align: center; padding: 16px 24px; border-top: 1px solid #f3f4f6; }
    </style>
</head>
<body>
    <div class="card">
        <div class="{{ $alertType === 'failed' ? 'header-danger' : 'header-warning' }}">
            <h1>{{ $alertType === 'failed' ? '🚨 Falha de Execução Detectada' : '⚠️ Alerta de Atraso no Heartbeat' }}</h1>
        </div>
        <div class="content">
            <p>O monitoramento automático identificou uma anomalia no serviço monitorado:</p>
            
            <table class="info-table">
                <tr>
                    <td class="label">Cliente:</td>
                    <td><strong>{{ $service->client?->name ?? 'Não identificado' }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Serviço:</td>
                    <td><strong>{{ $service->name }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Status:</td>
                    <td><strong style="color: {{ $alertType === 'failed' ? '#e11d48' : '#d97706' }};">{{ $alertType === 'failed' ? 'FALHA REPORTADA' : 'ATRASADO / SEM RESPOSTA' }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Periodicidade esperada:</td>
                    <td>A cada {{ $service->expected_interval_minutes }} minutos (tolerância: {{ $service->grace_period_minutes }} min)</td>
                </tr>
                <tr>
                    <td class="label">Último sinal de vida:</td>
                    <td>{{ $service->last_ping_at ? $service->last_ping_at->format('d/m/Y H:i:s') . ' (' . $service->last_ping_at->diffForHumans() . ')' : 'Nenhum registro anterior' }}</td>
                </tr>
                @if($service->last_ip)
                <tr>
                    <td class="label">Último IP detectado:</td>
                    <td><code>{{ $service->last_ip }}</code></td>
                </tr>
                @endif
            </table>

            @if($details || $service->last_message)
                <h4>Detalhes / Mensagem:</h4>
                <div class="box-log">{{ $details ?: $service->last_message }}</div>
            @endif

            <p style="margin-top: 20px; font-size: 13px; color: #6b7280;">
                Este e-mail foi disparado automaticamente pelo <strong>Systems Control</strong>. Por favor, verifique o servidor ou a rotina agendada correspondente.
            </p>
        </div>
        <div class="footer">
            Systems Control — Painel Operacional de Monitoramento
        </div>
    </div>
</body>
</html>
