@echo off
setlocal EnableDelayedExpansion

REM =========================================================================
REM  VIGILANT - EXEMPLO DE ROTINA DE BACKUP COM NOTIFICACAO AO DASHBOARD
REM =========================================================================

REM ===== 1. CONFIGURACOES DO DASHBOARD =====
set DASHBOARD_URL=https://dashboard.servicoskayser.com.br/api/heartbeat
set API_TOKEN=clt_live_SEU_TOKEN_AQUI
set NOME_SERVICO=Backup Sistema
set INTERVALO_MINUTOS=1440
set TOLERANCIA_MINUTOS=60
set EMAILS_ALERTA=ti@empresa.com.br

REM ===== 2. DIRETORIOS E ARQUIVOS DE LOG =====
set SCRIPT_DIR=%~dp0
set CURL_EXE=%SCRIPT_DIR%curl.exe
set LOG_DIR=%SCRIPT_DIR%logs
set ARQUIVO_LOG=%LOG_DIR%\backup_%date:~-4,4%%date:~-7,2%%date:~-10,2%.log

if not exist "%LOG_DIR%" mkdir "%LOG_DIR%"

echo ========================================================================= > "%ARQUIVO_LOG%"
echo  INICIO DA ROTINA DE BACKUP: %date% %time% >> "%ARQUIVO_LOG%"
echo ========================================================================= >> "%ARQUIVO_LOG%"

REM ===== 3. EXECUCAO DO COMANDO DE BACKUP / COPIA =====
REM Exemplo demonstrativo com Robocopy ou compactador (substitua pelo seu comando real):
echo [%time%] Executando sincronizacao de dados... >> "%ARQUIVO_LOG%"
robocopy "C:\Sistema\Dados" "D:\Backups\Dados" /MIR /NP /R:2 /W:5 >> "%ARQUIVO_LOG%" 2>&1

REM Avalia o resultado (Robocopy retorna <= 1 para sucesso)
if %ERRORLEVEL% LEQ 1 (
    set STATUS_OK=true
    set MSG_STATUS=Backup e sincronizacao concluidos com sucesso.
    echo [%time%] SUCESSO: Rotina finalizada sem erros. >> "%ARQUIVO_LOG%"
) else (
    set STATUS_OK=false
    set MSG_STATUS=Falha na execucao do backup. Codigo de saida: %ERRORLEVEL%
    echo [%time%] ERRO: Falha durante o processo de copia! >> "%ARQUIVO_LOG%"
)

echo ========================================================================= >> "%ARQUIVO_LOG%"
echo  FIM DA ROTINA: %date% %time% >> "%ARQUIVO_LOG%"
echo ========================================================================= >> "%ARQUIVO_LOG%"

REM ===== 4. DISPARO DO HEARTBEAT E ANEXO DE LOG =====
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

echo.
echo Processo concluido com sucesso.
endlocal
