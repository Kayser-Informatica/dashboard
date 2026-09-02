<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Recuperação de Token de Acesso</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f7f6; color: #333; margin: 0; padding: 24px; }
        .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .header { background: #0284c7; color: #fff; padding: 20px 24px; }
        .header h1 { margin: 0; font-size: 20px; }
        .content { padding: 24px; line-height: 1.6; }
        .info-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .info-table td { padding: 8px 12px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        .info-table td.label { font-weight: bold; width: 35%; color: #4b5563; }
        .token-box { background: #0f172a; color: #38bdf8; padding: 14px 16px; border-radius: 6px; font-family: 'Courier New', Courier, monospace; font-size: 14px; font-weight: bold; word-break: break-all; margin: 16px 0; border: 1px solid #1e293b; letter-spacing: 0.5px; }
        .curl-example { background: #1e293b; color: #f8fafc; padding: 12px 16px; border-radius: 6px; font-family: monospace; font-size: 12px; white-space: pre-wrap; word-break: break-all; margin-top: 8px; }
        .warning-box { background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 4px; font-size: 13px; color: #92400e; margin: 16px 0; }
        .footer { font-size: 12px; color: #9ca3af; text-align: center; padding: 16px 24px; border-top: 1px solid #f3f4f6; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>🔑 Recuperação de Token de API</h1>
        </div>
        <div class="content">
            <p>Olá! Você solicitou a recuperação do token de API para o cliente abaixo no <strong>Systems Control</strong>:</p>
            
            <table class="info-table">
                <tr>
                    <td class="label">Cliente:</td>
                    <td><strong>{{ $client->name }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Identificador (Slug):</td>
                    <td><code>{{ $client->slug }}</code></td>
                </tr>
                <tr>
                    <td class="label">E-mail Cadastrado:</td>
                    <td>{{ $client->email }}</td>
                </tr>
            </table>

            <h4 style="margin-bottom: 6px; color: #1e293b;">Seu Token de API:</h4>
            <div class="token-box">{{ $plainToken ?? $client->api_token }}</div>

            <div class="warning-box">
                <strong>Atenção:</strong> Este token permite enviar sinais de vida (heartbeats) e logs para os serviços deste cliente. Mantenha-o em sigilo e seguro.
            </div>

            <h4 style="margin-bottom: 6px; color: #1e293b;">Como utilizar em suas requisições:</h4>
            <p style="font-size: 13px; color: #4b5563; margin-top: 0;">
                Envie o token no cabeçalho <code>Authorization</code> como <code>Bearer Token</code>:
            </p>
            <div class="curl-example">Authorization: Bearer {{ $plainToken ?? $client->api_token }}</div>

            <p style="margin-top: 24px; font-size: 13px; color: #6b7280;">
                Se você não solicitou a recuperação deste token, por favor ignore esta mensagem.
            </p>
        </div>
        <div class="footer">
            Systems Control — Painel Operacional de Monitoramento
        </div>
    </div>
</body>
</html>
