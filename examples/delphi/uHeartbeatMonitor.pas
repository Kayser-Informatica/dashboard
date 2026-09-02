unit uHeartbeatMonitor;

interface

uses
  System.SysUtils,
  System.Classes,
  System.JSON,
  System.Net.HttpClient,
  System.Net.URLClient,
  System.Net.Mime,
  Vcl.ExtCtrls;

type
  THeartbeatLogEvent = reference to procedure(const AMessage: string; const ASuccess: Boolean);

  THeartbeatMonitor = class
  private
    FTimer: TTimer;
    FURL: string;
    FToken: string;
    FServiceName: string;
    FIntervalMinutes: Integer;
    FGraceMinutes: Integer;
    FNotificationEmails: string;
    FMessage: string;
    FOk: Boolean;
    FDurationSeconds: Integer;
    FOnLog: THeartbeatLogEvent;
    FRunning: Boolean;

    procedure OnTimerTick(Sender: TObject);
    procedure DoLog(const AMessage: string; const ASuccess: Boolean);
    function MontarJSONPayload(const AMessage: string; const AOk: Boolean; const ADurationSeconds: Integer): string;
    function ExecutarEnvioHTTP(const AMessage: string; const AOk: Boolean; const ADurationSeconds: Integer; const ALogFilePath: string = ''): Boolean;
  public
    constructor Create;
    destructor Destroy; override;

    procedure Start;
    procedure Stop;
    procedure SendHeartbeat(const AAsync: Boolean = True); overload;
    procedure SendHeartbeat(const AMessage: string; const AOk: Boolean = True;
      const ADurationSeconds: Integer = 1; const AAsync: Boolean = True); overload;
    procedure SendHeartbeat(const AMessage: string; const AOk: Boolean;
      const ADurationSeconds: Integer; const ALogFilePath: string;
      const AAsync: Boolean = True); overload;

    property URL: string read FURL write FURL;
    property Token: string read FToken write FToken;
    property ServiceName: string read FServiceName write FServiceName;
    property IntervalMinutes: Integer read FIntervalMinutes write FIntervalMinutes;
    property GraceMinutes: Integer read FGraceMinutes write FGraceMinutes;
    property NotificationEmails: string read FNotificationEmails write FNotificationEmails;
    property Message: string read FMessage write FMessage;
    property Ok: Boolean read FOk write FOk;
    property DurationSeconds: Integer read FDurationSeconds write FDurationSeconds;
    property OnLog: THeartbeatLogEvent read FOnLog write FOnLog;
    property Running: Boolean read FRunning;
  end;

implementation

{ THeartbeatMonitor }

constructor THeartbeatMonitor.Create;
begin
  inherited Create;
  FURL := 'https://dashboard.servicoskayser.com.br/api/heartbeat';
  FToken := 'clt_live_g957SkC9Kr3q1xhQ2ZfuwP2oiYWf0t9rj89vi4Cp';
  FServiceName := 'Servidor de relatório';
  FIntervalMinutes := 30;
  FGraceMinutes := 5;
  FNotificationEmails := 'lucas@kayser.com.br';
  FMessage := 'Servidor de relatório online';
  FOk := True;
  FDurationSeconds := 1;
  FRunning := False;

  FTimer := TTimer.Create(nil);
  FTimer.Enabled := False;
  FTimer.Interval := Cardinal(FIntervalMinutes) * 60 * 1000;
  FTimer.OnTimer := OnTimerTick;
end;

destructor THeartbeatMonitor.Destroy;
begin
  Stop;
  FreeAndNil(FTimer);
  inherited;
end;

procedure THeartbeatMonitor.Start;
begin
  if FRunning then
    Exit;

  FRunning := True;
  FTimer.Interval := Cardinal(FIntervalMinutes) * 60 * 1000;
  FTimer.Enabled := True;

  // Realiza o primeiro disparo imediatamente ao iniciar
  SendHeartbeat(True);
end;

procedure THeartbeatMonitor.Stop;
begin
  FRunning := False;
  if Assigned(FTimer) then
    FTimer.Enabled := False;
end;

procedure THeartbeatMonitor.OnTimerTick(Sender: TObject);
begin
  SendHeartbeat(True);
end;

function THeartbeatMonitor.MontarJSONPayload(const AMessage: string; const AOk: Boolean; const ADurationSeconds: Integer): string;
var
  JSONObj: TJSONObject;
  LMsg: string;
  LSeconds: Integer;
begin
  if AMessage <> '' then
    LMsg := AMessage
  else
    LMsg := FMessage;

  if ADurationSeconds > 0 then
    LSeconds := ADurationSeconds
  else
    LSeconds := FDurationSeconds;

  JSONObj := TJSONObject.Create;
  try
    JSONObj.AddPair('service', FServiceName);
    JSONObj.AddPair('interval_minutes', TJSONNumber.Create(FIntervalMinutes));
    JSONObj.AddPair('grace_minutes', TJSONNumber.Create(FGraceMinutes));
    JSONObj.AddPair('notification_emails', FNotificationEmails);
    JSONObj.AddPair('ok', TJSONBool.Create(AOk));
    JSONObj.AddPair('message', LMsg);
    JSONObj.AddPair('duration_seconds', TJSONNumber.Create(LSeconds));

    Result := JSONObj.ToJSON;
  finally
    JSONObj.Free;
  end;
end;

function THeartbeatMonitor.ExecutarEnvioHTTP(const AMessage: string; const AOk: Boolean;
  const ADurationSeconds: Integer; const ALogFilePath: string): Boolean;
var
  LHttpClient: THTTPClient;
  LRequestBody: TStringStream;
  LFormData: TMultipartFormData;
  LResponse: IHTTPResponse;
  LPayload: string;
  LHeaders: TNetHeaders;
  LMsg: string;
  LSeconds: Integer;
  LTemAnexo: Boolean;
begin
  Result := False;

  if AMessage <> '' then
    LMsg := AMessage
  else
    LMsg := FMessage;

  if ADurationSeconds > 0 then
    LSeconds := ADurationSeconds
  else
    LSeconds := FDurationSeconds;

  LTemAnexo := (ALogFilePath <> '') and FileExists(ALogFilePath);

  LHttpClient := THTTPClient.Create;
  try
    LHttpClient.ConnectionTimeout := 10000; // 10 segundos
    LHttpClient.ResponseTimeout := 20000;   // 20 segundos
    LHttpClient.Accept := 'application/json';

    if LTemAnexo then
    begin
      // Envio multipart/form-data com arquivo de log anexado
      SetLength(LHeaders, 1);
      LHeaders[0] := TNameValuePair.Create('Authorization', 'Bearer ' + FToken);

      LFormData := TMultipartFormData.Create;
      try
        LFormData.AddField('service', FServiceName);
        LFormData.AddField('interval_minutes', IntToStr(FIntervalMinutes));
        LFormData.AddField('grace_minutes', IntToStr(FGraceMinutes));
        LFormData.AddField('notification_emails', FNotificationEmails);
        if AOk then
          LFormData.AddField('ok', '1')
        else
          LFormData.AddField('ok', '0');
        LFormData.AddField('message', LMsg);
        LFormData.AddField('duration_seconds', IntToStr(LSeconds));
        LFormData.AddFile('log_file', ALogFilePath);

        LResponse := LHttpClient.Post(FURL, LFormData, nil, LHeaders);
      finally
        LFormData.Free;
      end;
    end
    else
    begin
      // Envio padrao JSON application/json
      SetLength(LHeaders, 2);
      LHeaders[0] := TNameValuePair.Create('Authorization', 'Bearer ' + FToken);
      LHeaders[1] := TNameValuePair.Create('Content-Type', 'application/json');

      LPayload := MontarJSONPayload(AMessage, AOk, ADurationSeconds);
      LRequestBody := TStringStream.Create(LPayload, TEncoding.UTF8);
      try
        LResponse := LHttpClient.Post(FURL, LRequestBody, nil, LHeaders);
      finally
        LRequestBody.Free;
      end;
    end;

    if Assigned(LResponse) and (LResponse.StatusCode >= 200) and (LResponse.StatusCode < 300) then
    begin
      Result := True;
      DoLog(Format('[Heartbeat] Sucesso (%d) - %s: %s', [LResponse.StatusCode, FormatDateTime('dd/mm/yyyy hh:nn:ss', Now), LMsg]), True);
    end
    else
    begin
      if Assigned(LResponse) then
        DoLog(Format('[Heartbeat] Falha (%d: %s) - %s: %s', [LResponse.StatusCode, LResponse.StatusText, FormatDateTime('dd/mm/yyyy hh:nn:ss', Now), LMsg]), False)
      else
        DoLog(Format('[Heartbeat] Falha: Sem resposta da API - %s: %s', [FormatDateTime('dd/mm/yyyy hh:nn:ss', Now), LMsg]), False);
    end;
  except
    on E: Exception do
    begin
      DoLog(Format('[Heartbeat] Erro de conexão: %s - %s', [E.Message, FormatDateTime('dd/mm/yyyy hh:nn:ss', Now)]), False);
    end;
  end;
  LHttpClient.Free;
end;

procedure THeartbeatMonitor.DoLog(const AMessage: string; const ASuccess: Boolean);
var
  LMsg: string;
  LSuccess: Boolean;
begin
  if not Assigned(FOnLog) then
    Exit;

  LMsg := AMessage;
  LSuccess := ASuccess;

  // Garante que o evento seja despachado de forma segura na Main Thread (VCL)
  TThread.Queue(nil,
    procedure
    begin
      if Assigned(FOnLog) then
        FOnLog(LMsg, LSuccess);
    end);
end;

procedure THeartbeatMonitor.SendHeartbeat(const AAsync: Boolean);
begin
  SendHeartbeat(FMessage, FOk, FDurationSeconds, AAsync);
end;

procedure THeartbeatMonitor.SendHeartbeat(const AMessage: string; const AOk: Boolean;
  const ADurationSeconds: Integer; const AAsync: Boolean);
begin
  SendHeartbeat(AMessage, AOk, ADurationSeconds, '', AAsync);
end;

procedure THeartbeatMonitor.SendHeartbeat(const AMessage: string; const AOk: Boolean;
  const ADurationSeconds: Integer; const ALogFilePath: string; const AAsync: Boolean);
var
  LMsg: string;
  LOk: Boolean;
  LDuration: Integer;
  LLogFile: string;
begin
  LMsg := AMessage;
  LOk := AOk;
  LDuration := ADurationSeconds;
  LLogFile := ALogFilePath;

  if AAsync then
  begin
    TThread.CreateAnonymousThread(
      procedure
      begin
        ExecutarEnvioHTTP(LMsg, LOk, LDuration, LLogFile);
      end).Start;
  end
  else
  begin
    ExecutarEnvioHTTP(LMsg, LOk, LDuration, LLogFile);
  end;
end;

end.
