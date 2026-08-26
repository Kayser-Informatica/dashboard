<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Serviço Recuperado</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f7f6; color: #333; margin: 0; padding: 24px; }
        .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .header-success { background: #059669; color: #fff; padding: 20px 24px; }
        .header h1 { margin: 0; font-size: 20px; }
        .content { padding: 24px; line-height: 1.6; }
        .info-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .info-table td { padding: 8px 12px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        .info-table td.label { font-weight: bold; width: 35%; color: #4b5563; }
        .footer { font-size: 12px; color: #9ca3af; text-align: center; padding: 16px 24px; border-top: 1px solid #f3f4f6; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header-success">
            <h1>🟢 Serviço Normalizado</h1>
        </div>
        <div class="content">
            <p>O serviço voltou a enviar sinais de vida com sucesso e está operando dentro dos parâmetros normais:</p>
            
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
                    <td class="label">Status Atual:</td>
                    <td><strong style="color: #059669;">OPERACIONAL (OK)</strong></td>
                </tr>
                <tr>
                    <td class="label">Horário da Recuperação:</td>
                    <td>{{ $service->last_ping_at?->format('d/m/Y H:i:s') ?? now()->format('d/m/Y H:i:s') }}</td>
                </tr>
                @if($service->last_message)
                <tr>
                    <td class="label">Mensagem:</td>
                    <td>{{ $service->last_message }}</td>
                </tr>
                @endif
            </table>

            <p style="margin-top: 20px; font-size: 13px; color: #6b7280;">
                O estado de alerta para este serviço foi cancelado no <strong>Systems Control</strong>.
            </p>
        </div>
        <div class="footer">
            Systems Control — Painel Operacional de Monitoramento
        </div>
    </div>
</body>
</html>
