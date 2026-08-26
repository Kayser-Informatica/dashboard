# Systems Control

Dashboard em **Laravel 13** para monitoramento centralizado de clientes, serviços periódicos (estilo Heartbeat / Dead Man's Switch), arquivos de logs de execução e alertas automáticos por e-mail.

---

## 🌟 Recursos Principais

* **Cadastro de Clientes com Token Exclusivo:** Cada cliente (ex: *NeeMedT*) se cadastra e recebe uma chave de API única (`clt_live_...`).
* **Monitoramento Periódico de Serviços (Heartbeat):** Cada serviço (ex: *Envio de e-mails*, *Backup do sistema*) avisa o dashboard quando executa, informando a periodicidade esperada (ex: a cada 60 min ou 24h).
* **Detecção Automática de Atrasos:** Se um serviço não enviar sinal de vida dentro do prazo combinado (+ tolerância), o painel muda para status **Atrasado** e destaca o alerta.
* **Alertas por E-mail:** Envio automático de notificações para uma lista de e-mails (`notification_emails`) em caso de:
  1. Falha explícita (`ok: false`).
  2. Atraso/inatividade do heartbeat (via `php artisan monitors:check-deadlines`).
  3. Notificação de normalização/recuperação (`ok: true`).
* **Anexo e Visualizador de Logs:** Envio opcional de arquivos `.log`/`.txt` junto com o heartbeat, com visualizador estilo terminal no Dashboard e download do arquivo original.

---

## 📡 Endpoints da API

| Método | Endpoint | Autenticação | Finalidade |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/clients/register` | Pública | Cadastra um novo cliente e emite seu `api_token` |
| `POST` | `/api/heartbeat` | Token do Cliente | Registra sinal de vida, periodicidade, status e log anexo |
| `GET` | `/api/services/{service}/logs/{log}/download` | Pública/Sessão | Download do arquivo de log original |
| `GET` | `/api/dashboard/metrics` | Pública | Retorna métricas agregadas e clientes para polling reativo |
| `GET` | `/` | Pública | Abre o Dashboard operacional para monitores e NOC |

---

## 1. Como Cadastrar um Cliente

Cadastre o cliente uma única vez para receber o token de autenticação:

```bash
curl -X POST http://localhost:8000/api/clients/register \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "name": "NeeMedT",
    "slug": "neemedt"
  }'
```

**Resposta (201 Created):**
```json
{
  "message": "Cliente cadastrado com sucesso! Guarde este token de API...",
  "client": {
    "id": 1,
    "name": "NeeMedT",
    "slug": "neemedt"
  },
  "api_token": "clt_live_a1b2c3d4e5f67890abcdef1234567890abcdef12"
}
```

---

## 2. Como Enviar um Heartbeat (Sinal de Vida)

### A) Ping Simples em JSON (Ex: Rotina de E-mails a cada 60 min)
```bash
curl -X POST http://localhost:8000/api/heartbeat \
  -H 'Authorization: Bearer clt_live_seu_token_aqui' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "service": "Envio de emails do financeiro",
    "interval_minutes": 60,
    "grace_minutes": 10,
    "ok": true,
    "message": "45 e-mails enviados em 2 segundos",
    "duration_seconds": 2,
    "notification_emails": "ti@neemedt.com; suporte@empresa.com"
  }'
```

### B) Ping com Upload de Arquivo de Log (Ex: Backup Diário de 24h)
```bash
curl -X POST http://localhost:8000/api/heartbeat \
  -H 'Authorization: Bearer clt_live_seu_token_aqui' \
  -H 'Accept: application/json' \
  -F 'service=Backup do sistema' \
  -F 'interval_minutes=1440' \
  -F 'grace_minutes=30' \
  -F 'ok=true' \
  -F 'message=Backup concluído com sucesso' \
  -F 'notification_emails=ti@neemedt.com; suporte@empresa.com' \
  -F 'log_file=@/var/log/backup-erp.log'
```

---

## ⏰ Verificação de Atrasos & Alertas Automáticos

Para checar continuamente se algum serviço atrasou e disparar e-mails de alerta, o comando do console é agendado no Laravel:

```bash
php artisan monitors:check-deadlines
```

*(No servidor em produção, execute o cron padrão do Laravel `* * * * * php /caminho/artisan schedule:run >> /dev/null 2>&1`).*

---

## 📁 Coleção Insomnia

O arquivo [insomnia.json](file:///home/lucas/web_projects/dashboard/insomnia.json) na raiz do projeto contém todas as requisições prontas e organizadas por pastas:
* **1. Clientes:** Cadastro e emissão de token.
* **2. Heartbeat & Serviços:** JSON Sucesso, JSON Falha e Multipart com upload de arquivo.
* **3. Dashboard & Métricas:** Polling de métricas.

---

## 🧪 Testes Automatizados

```bash
php artisan test
```
