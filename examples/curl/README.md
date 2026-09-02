# Integração via cURL / Scripts de Linha de Comando (Batch & Shell)

Este diretório contém os binários portáteis do **cURL para Windows** e exemplos práticos de integração para monitoramento de rotinas periódicas (como backups, sincronização de arquivos, dumps de bancos de dados e tarefas agendadas) via chamadas HTTP à API do **Vigilant**.

---

## 📁 Estrutura deste Diretório

* **`curl.exe`**, **`libcurl-x64.dll`**, **`curl-ca-bundle.crt`**: Binários independentes do cURL (64-bit) para execução direta no Windows, sem necessidade de instalação prévia no sistema.
* **`exemplo_backup.bat`**: Modelo pronto de script Batch para Windows com captura de logs e envio de notificação de Sucesso/Falha.
* **`README.md`**: Este guia com instruções, parâmetros e exemplos práticos.

---

## ⚙️ Parâmetros de Configuração

Ao disparar um heartbeat via cURL, utilize os seguintes parâmetros no corpo da requisição (`multipart/form-data` ou `application/json`):

| Parâmetro | Tipo | Obrigatório | Descrição | Exemplo |
| :--- | :--- | :---: | :--- | :--- |
| `service` | `string` | **Sim** | Nome de exibição da rotina/serviço no Dashboard. | `"Backup Banco Produção"` |
| `interval_minutes` | `integer` | Não | Intervalo esperado entre execuções (em minutos). Ex: 24h = `1440`. | `1440` |
| `grace_minutes` | `integer` | Não | Tolerância adicional em minutos antes de marcar como atrasado. | `60` |
| `ok` | `boolean` | Não | Status da execução (`true` = Sucesso, `false` = Falha). Padrão: `true`. | `true` |
| `message` | `string` | Não | Mensagem descritiva do status ou motivo da falha. | `"Backup de 12GB concluído com sucesso."` |
| `duration_seconds` | `integer` | Não | Duração da rotina em segundos. | `185` |
| `notification_emails`| `string` | Não | E-mails separados por vírgula para alertas de falha/atraso. | `"ti@empresa.com.br, noc@empresa.com.br"` |
| `log_file` | `file` | Não | Arquivo de log anexado (`.log` ou `.txt`). | `@C:\Backups\logs\backup_hoje.log` |

> [!IMPORTANT]
> A autenticação exige o envio do cabeçalho **`Authorization: Bearer clt_live_...`** com o token da empresa emitido no cadastro (`POST /api/clients/register`).

---

## 💻 1. Exemplo em Batch Script Windows (`.bat` / `.cmd`)

Este modelo executa um procedimento (ex: backup ou cópia de arquivos), salva a saída em um arquivo de log e notifica o Dashboard tanto em caso de **sucesso** quanto em caso de **falha**:

```bat
@echo off
setlocal EnableDelayedExpansion

REM ===== 1. CONFIGURACOES DO DASHBOARD =====
set DASHBOARD_URL=https://dashboard.servicoskayser.com.br/api/heartbeat
set API_TOKEN=clt_live_SEU_TOKEN_AQUI
set NOME_SERVICO=Backup Sistema
set INTERVALO_MINUTOS=1440
set TOLERANCIA_MINUTOS=60
set EMAILS_ALERTA=ti@empresa.com.br

REM ===== 2. CONFIGURACOES LOCAIS =====
set SCRIPT_DIR=%~dp0
set CURL_EXE=%SCRIPT_DIR%curl.exe
set LOG_DIR=C:\Backups\logs
set ARQUIVO_LOG=%LOG_DIR%\backup_%date:~-4,4%%date:~-7,2%%date:~-10,2%.log

if not exist "%LOG_DIR%" mkdir "%LOG_DIR%"

echo [%date% %time%] Iniciando rotina de backup... > "%ARQUIVO_LOG%"

REM ===== 3. EXECUCAO DA SUA ROTINA (Exemplo com 7-Zip ou Robocopy) =====
REM Substitua esta linha pelo seu comando real de backup:
robocopy "C:\Sistema\Dados" "D:\Backups\Dados" /MIR /NP /R:2 /W:5 >> "%ARQUIVO_LOG%" 2>&1

REM Robocopy retorna códigos <= 1 para sucesso/sem alterações
if %ERRORLEVEL% LEQ 1 (
    set STATUS_OK=true
    set MSG_STATUS=Backup e sincronizacao concluidos com sucesso.
    echo [%date% %time%] Sucesso no backup. >> "%ARQUIVO_LOG%"
) else (
    set STATUS_OK=false
    set MSG_STATUS=Falha na execucao do backup. Codigo de saida: %ERRORLEVEL%
    echo [%date% %time%] ERRO: Falha no backup! >> "%ARQUIVO_LOG%"
)

REM ===== 4. ENVIO DE NOTIFICACAO AO DASHBOARD =====
echo Enviando notificacao ao Dashboard...
"%CURL_EXE%" -s -X POST "%DASHBOARD_URL%" ^
  -H "Authorization: Bearer %API_TOKEN%" ^
  -H "Accept: application/json" ^
  -F "service=%NOME_SERVICO%" ^
  -F "interval_minutes=%INTERVALO_MINUTOS%" ^
  -F "grace_minutes=%TOLERANCIA_MINUTOS%" ^
  -F "ok=%STATUS_OK%" ^
  -F "message=%MSG_STATUS%" ^
  -F "notification_emails=%EMAILS_ALERTA%" ^
  -F "log_file=@%ARQUIVO_LOG%"

echo [%date% %time%] Notificacao enviada.
endlocal
```

---

## ⚡ 2. Exemplo em PowerShell (`.ps1`)

Para ambientes com PowerShell disponível:

```powershell
$DashboardUrl = "https://dashboard.servicoskayser.com.br/api/heartbeat"
$ApiToken     = "clt_live_SEU_TOKEN_AQUI"
$LogPath      = "C:\Backups\logs\backup_$(Get-Date -Format 'yyyyMMdd').log"

$Headers = @{
    "Authorization" = "Bearer $ApiToken"
    "Accept"        = "application/json"
}

$Form = @{
    service             = "Backup Financeiro"
    interval_minutes    = "1440"
    grace_minutes       = "60"
    ok                  = "true"
    message             = "Backup concluído com sucesso via PowerShell."
    notification_emails = "ti@empresa.com.br"
    log_file            = Get-Item -Path $LogPath
}

Invoke-RestMethod -Uri $DashboardUrl -Method Post -Headers $Headers -Form $Form
```

---

## 🐧 3. Exemplo em Shell Script Linux / macOS (`.sh`)

Ideal para crontabs e rotinas em servidores Linux:

```bash
#!/usr/bin/env bash
set -e

DASHBOARD_URL="https://dashboard.servicoskayser.com.br/api/heartbeat"
API_TOKEN="clt_live_SEU_TOKEN_AQUI"
LOG_FILE="/var/log/backups/backup_$(date +%Y%m%d).log"

# Executa rotina (ex: mysqldump)
mysqldump -u root -p'senha' banco_producao > /backups/db.sql 2> "$LOG_FILE"
EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ]; then
  OK="true"
  MSG="Dump MySQL realizado e compactado com sucesso."
else
  OK="false"
  MSG="Erro durante a criacao do dump MySQL. Codigo: $EXIT_CODE"
fi

# Dispara sinal de vida e anexa log
curl -s -X POST "$DASHBOARD_URL" \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Accept: application/json" \
  -F "service=Backup MySQL Producao" \
  -F "interval_minutes=1440" \
  -F "grace_minutes=60" \
  -F "ok=$OK" \
  -F "message=$MSG" \
  -F "notification_emails=ti@empresa.com.br" \
  -F "log_file=@$LOG_FILE"
```

---

## ⏰ Configuração no Agendador de Tarefas do Windows (Task Scheduler)

1. Abra o **Agendador de Tarefas** (`taskschd.msc`).
2. Clique em **Criar Tarefa Básica...** e dê um nome (ex: `Backup Diário e Heartbeat`).
3. Escolha o disparador (ex: *Diariamente às 23:00*).
4. Na ação, selecione **Iniciar um programa**.
5. No campo **Programa/script**, aponte para o arquivo `.bat` (ex: `C:\Backups\exemplo_backup.bat`).
6. No campo **Iniciar em (opcional)**, insira a pasta do script (ex: `C:\Backups\`).
7. Marque para executar mesmo que o usuário não esteja logado.
