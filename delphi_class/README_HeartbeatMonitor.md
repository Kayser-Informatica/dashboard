# THeartbeatMonitor — Documentação e Guia de Uso

A unit `uHeartbeatMonitor.pas` implementa a classe **`THeartbeatMonitor`**, projetada para enviar notificações de status (*heartbeat*) e logs para a API de monitoramento externa da Kayser Informática de forma periódica e sob demanda.

---

## 🚀 Características Principais

- **Desacoplada e Reutilizável**: Pode ser utilizada em qualquer projeto Delphi (VCL, FMX, Console ou Windows Service).
- **Compatível com Delphi 10.1 Berlin+**: Utiliza `THTTPClient` e `TMultipartFormData` nativos (`System.Net.HttpClient` / `System.Net.Mime`), dispensando DLLs OpenSSL externas (`libeay32.dll` / `ssleay32.dll`).
- **Execução Não-Bloqueante (Thread-Safe)**: Todas as chamadas HTTP são executadas em *background threads* (`TThread.CreateAnonymousThread`), garantindo que a aplicação ou interface gráfica nunca trave por lentidão de rede.
- **Callback Seguro de Log (`OnLog`)**: Sincroniza atualizações de interface através de `TThread.Queue`, evitando *Access Violation*.
- **Suporte a Anexo de Arquivo de Log (`log_file`)**: Quando um arquivo é anexado, o envio é automaticamente convertido para `multipart/form-data`.

---

## 📋 Propriedades da Classe

| Propriedade | Tipo | Padrão | Descrição |
| :--- | :--- | :--- | :--- |
| `URL` | `string` | `https://dashboard.servicoskayser.com.br/api/heartbeat` | Endpoint da API de monitoramento. |
| `Token` | `string` | `clt_live_g957SkC9Kr3q1xhQ2ZfuwP2oiYWf0t9rj89vi4Cp` | Bearer Token de autenticação do cliente. |
| `ServiceName` | `string` | `Servidor de relatório` | Nome do serviço exibido no dashboard. |
| `IntervalMinutes`| `Integer`| `30` | Intervalo em minutos entre checagens periódicas. |
| `GraceMinutes` | `Integer`| `5` | Tolerância de atraso antes de disparar alerta de queda. |
| `NotificationEmails` | `string` | `lucas@kayser.com.br` | E-mails para envio de alertas de indisponibilidade. |
| `Message` | `string` | `Servidor de relatório online` | Mensagem padrão do heartbeat periódico. |
| `Ok` | `Boolean`| `True` | Status de saúde do serviço (`True` = OK, `False` = Falha). |
| `DurationSeconds` | `Integer`| `1` | Duração estimada da operação em segundos. |
| `OnLog` | `THeartbeatLogEvent` | `nil` | Procedimento de retorno para registrar logs ou atualizar a UI. |
| `Running` | `Boolean` (Read) | `False` | Indica se o temporizador periódico está ativo. |

---

## 🛠️ Métodos Disponíveis

### 1. Inicialização e Controle do Temporizador
```pascal
// Inicia o timer periódico de 30 minutos e realiza o primeiro envio imediato
procedure Start;

// Para o timer de envio periódico
procedure Stop;
```

### 2. Disparo de Heartbeat Sob Demanda (Sobrecargas)

```pascal
// Envia o heartbeat com as propriedades configuradas no objeto
procedure SendHeartbeat(const AAsync: Boolean = True); overload;

// Envia mensagem personalizada, status e duração da requisição
procedure SendHeartbeat(const AMessage: string; const AOk: Boolean = True;
  const ADurationSeconds: Integer = 1; const AAsync: Boolean = True); overload;

// Envia mensagem personalizada, status, duração e anexa arquivo de log (log_file)
procedure SendHeartbeat(const AMessage: string; const AOk: Boolean;
  const ADurationSeconds: Integer; const ALogFilePath: string;
  const AAsync: Boolean = True); overload;
```

---

## 💻 Exemplos de Uso

### Exemplo 1: Uso no Formulário Principal (VCL / Servidor Horse)

```pascal
uses
  uHeartbeatMonitor, System.DateUtils;

type
  TF_Principal = class(TForm)
    MemoLog: TMemo;
    procedure FormCreate(Sender: TObject);
    procedure FormClose(Sender: TObject; var Action: TCloseAction);
  private
    FHeartbeatMonitor: THeartbeatMonitor;
  end;

procedure TF_Principal.FormCreate(Sender: TObject);
begin
  if (LowerCase(ParamStr(1)) = '-server') then
  begin
    // 1. Cria e parametriza o monitor
    FHeartbeatMonitor := THeartbeatMonitor.Create;
    FHeartbeatMonitor.URL := 'https://dashboard.servicoskayser.com.br/api/heartbeat';
    FHeartbeatMonitor.Token := 'clt_live_g957SkC9Kr3q1xhQ2ZfuwP2oiYWf0t9rj89vi4Cp';
    FHeartbeatMonitor.ServiceName := 'Servidor de relatório';
    FHeartbeatMonitor.IntervalMinutes := 30;
    FHeartbeatMonitor.GraceMinutes := 5;
    FHeartbeatMonitor.NotificationEmails := 'lucas@kayser.com.br';
    
    // 2. Conecta o evento de log para exibir no Memo (Thread-Safe)
    FHeartbeatMonitor.OnLog := procedure(const ALog: string; const ASuccess: Boolean)
    begin
      MemoLog.Lines.Add(ALog);
    end;

    // 3. Inicia o monitoramento periódico
    FHeartbeatMonitor.Start;
  end;
end;

procedure TF_Principal.FormClose(Sender: TObject; var Action: TCloseAction);
begin
  // Liberação segura
  if Assigned(FHeartbeatMonitor) then
  begin
    FHeartbeatMonitor.Stop;
    FreeAndNil(FHeartbeatMonitor);
  end;
end;
```

---

### Exemplo 2: Notificando Eventos de Sucesso e Falha com Anexo de Log

```pascal
procedure ProcessarRelatorio(const ANomeRelatorio, AEmpresa: string);
var
  dInicio: TDateTime;
  iDuracaoSeg: Integer;
  sArquivoErro: string;
begin
  dInicio := Now;
  try
    // Executa a geração do relatório...
    GerarPDF(ANomeRelatorio, AEmpresa);

    // Sucesso: calcula duração e envia
    iDuracaoSeg := SecondsBetween(Now, dInicio);
    if iDuracaoSeg < 1 then 
      iDuracaoSeg := 1;

    if Assigned(FHeartbeatMonitor) then
      FHeartbeatMonitor.SendHeartbeat(
        Format('Relatório %s gerado para empresa %s', [ANomeRelatorio, AEmpresa]),
        True,
        iDuracaoSeg
      );

  except
    on E: Exception do
    begin
      iDuracaoSeg := SecondsBetween(Now, dInicio);
      if iDuracaoSeg < 1 then 
        iDuracaoSeg := 1;

      // Grava o log de erro para envio
      sArquivoErro := ExtractFilePath(Application.ExeName) + 'log_erro.txt';
      MemoLog.Lines.SaveToFile(sArquivoErro);

      // Envia o heartbeat de falha anexando o arquivo
      if Assigned(FHeartbeatMonitor) then
        FHeartbeatMonitor.SendHeartbeat(
          Format('Erro ao gerar relatório %s: %s', [ANomeRelatorio, E.Message]),
          False,
          iDuracaoSeg,
          sArquivoErro // Caminho do arquivo anexado
        );
        
      raise;
    end;
  end;
end;
```

---

### Exemplo 3: Uso em Aplicação Console ou Windows Service (Sem Interface Gráfica)

```pascal
program MeuServicoConsole;

{$APPTYPE CONSOLE}

uses
  System.SysUtils,
  uHeartbeatMonitor in 'PRG\CLASSES\uHeartbeatMonitor.pas';

var
  Monitor: THeartbeatMonitor;
begin
  try
    Monitor := THeartbeatMonitor.Create;
    try
      Monitor.ServiceName := 'Serviço de Integração Automática';
      Monitor.IntervalMinutes := 15;
      Monitor.Token := 'SEU_TOKEN_AQUI';
      
      // Callback opcional via Console WriteLn
      Monitor.OnLog := procedure(const ALog: string; const ASuccess: Boolean)
      begin
        WriteLn(ALog);
      end;

      Monitor.Start;

      WriteLn('Serviço rodando. Pressione ENTER para encerrar...');
      ReadLn;

      Monitor.Stop;
    finally
      Monitor.Free;
    end;
  except
    on E: Exception do
      WriteLn('Erro: ' + E.Message);
  end;
end.
```

---

## 🌐 Especificação da API de Monitoramento

- **URL**: `POST https://dashboard.servicoskayser.com.br/api/heartbeat`
- **Header**: `Authorization: Bearer <Token>`

### Payload JSON (Envio sem anexo):
```json
{
  "service": "Servidor de relatório",
  "interval_minutes": 30,
  "grace_minutes": 5,
  "notification_emails": "lucas@kayser.com.br",
  "ok": true,
  "message": "Relatório fichamedica gerado para empresa 001",
  "duration_seconds": 2
}
```

### Formato Multipart/Form-Data (Envio com arquivo anexado):
- `service`: `"Servidor de relatório"`
- `interval_minutes`: `"30"`
- `grace_minutes`: `"5"`
- `notification_emails`: `"lucas@kayser.com.br"`
- `ok`: `"1"` ou `"0"`
- `message`: `"Erro ao gerar relatório: Connection Timeout"`
- `duration_seconds`: `"3"`
- `log_file`: Arquivo anexado (ex: `log_20260827_082530_123.txt`)
