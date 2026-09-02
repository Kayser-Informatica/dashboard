# Systems Control

Dashboard em **Laravel 13** para monitoramento centralizado de clientes, serviços periódicos (estilo Heartbeat / Dead Man's Switch), arquivos de logs de execução e alertas automáticos por e-mail.

---

## 🌟 Recursos Principais

* **Cadastro de Clientes com Token Exclusivo:** Cada cliente se cadastra e recebe uma chave de API única (`clt_live_...`).
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
| `POST` | `/api/clients/register` | Pública | Cadastra um novo cliente com e-mail e emite seu `api_token` |
| `POST` | `/api/clients/recover-token` | Pública | Envia o token de API por e-mail caso os dados coincidam |
| `POST` | `/api/heartbeat` | Token do Cliente | Registra sinal de vida, periodicidade, status e log anexo |
| `GET` | `/api/services/{service}/logs/{log}/download` | Pública/Sessão | Download do arquivo de log original |
| `GET` | `/api/dashboard/metrics` | Pública | Retorna métricas agregadas e clientes para polling reativo |
| `GET` | `/` | Pública | Abre o Dashboard operacional para monitores e NOC |

---

## 1. Como Cadastrar um Cliente

Cadastre o cliente informando o nome e o e-mail de recuperação (obrigatório). O mesmo e-mail pode ser vinculado a vários clientes, e não poderá ser alterado posteriormente:

```bash
curl -X POST http://localhost:8000/api/clients/register \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "name": "Meu Cliente",
    "slug": "meu-cliente",
    "email": "ti@meucliente.com"
  }'
```

**Resposta (201 Created):**
```json
{
  "message": "Cliente cadastrado com sucesso! Guarde este token de API com segurança, ele é necessário para enviar pings e logs de monitoramento.",
  "client": {
    "id": 1,
    "name": "Meu Cliente",
    "slug": "meu-cliente",
    "email": "ti@meucliente.com"
  },
  "api_token": "clt_live_a1b2c3d4e5f67890abcdef1234567890abcdef12"
}
```

---

## 2. Como Recuperar o Token de API (Em Caso de Perda)

Se o token de API do cliente for perdido, utilize o endpoint de recuperação informando o e-mail cadastrado e o nome ou slug do cliente:

```bash
curl -X POST http://localhost:8000/api/clients/recover-token \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "email": "ti@meucliente.com",
    "client": "Meu Cliente"
  }'
```

**Resposta (200 OK):**
```json
{
  "message": "Se os dados informados estiverem corretos, um e-mail com as credenciais e o token de API foi enviado para o endereço cadastrado."
}
```

*(Caso os dados coincidam, o cliente receberá um e-mail com o token de acesso e instruções de cabeçalho Bearer).*

---

## 3. Como Enviar um Heartbeat (Sinal de Vida)

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
    "notification_emails": "ti@meucliente.com; suporte@meucliente.com"
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
  -F 'notification_emails=ti@meucliente.com; suporte@meucliente.com' \
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

## 🔌 Exemplos de Integração

### 1. Delphi (`examples/delphi`)
Para aplicações desktop, serviços Windows ou servidores desenvolvidos em **Delphi** (10.1 Berlin ou superior):
* **Classe Delphi:** [`examples/delphi/uHeartbeatMonitor.pas`](examples/delphi/uHeartbeatMonitor.pas) — Classe `THeartbeatMonitor` nativa (`System.Net.HttpClient`), não-bloqueante (threads assíncronas), com suporte a timer periódico, disparo sob demanda e anexo de arquivos de log (`multipart/form-data`).
* **Documentação & Exemplos:** Consulte o guia detalhado em [`examples/delphi/README_HeartbeatMonitor.md`](examples/delphi/README_HeartbeatMonitor.md).

### 2. cURL / Batch / Shell Script (`examples/curl`)
Para rotinas de backup, sincronização de arquivos, scripts de banco de dados e tarefas agendadas:
* **Script Modelo Windows:** [`examples/curl/exemplo_backup.bat`](examples/curl/exemplo_backup.bat) — Script Batch com captura de logs, validação de erro e envio de heartbeat.
* **Documentação & Exemplos:** Consulte o guia em [`examples/curl/README.md`](examples/curl/README.md) com exemplos para Batch, PowerShell e Bash Linux.

---

## 🚀 Como Executar o Projeto

Você pode executar o projeto utilizando **Laravel Sail** (recomendado para desenvolvimento), **Docker Standalone** (imagem de produção autocontida) ou **Localmente**.

---

### Opção 1: Laravel Sail (Recomendado para Desenvolvimento)

O Sail fornece um ambiente de desenvolvimento completo e isolado (PHP 8.3, MySQL 8.0, Redis, Mailpit e Node/Vite) sem necessidade de instalar dependências no host:

```bash
# 1. Copie o arquivo de ambiente
cp .env.example .env

# 2. Inicie os containers do Sail em segundo plano
./vendor/bin/sail up -d
# ou simplesmente 'sail up -d' caso tenha o alias configurado

# 3. Execute as migrações do banco de dados
./vendor/bin/sail artisan migrate

# 4. (Opcional) Gere a chave da aplicação se ainda não estiver definida
./vendor/bin/sail artisan key:generate

# 5. Acesse no navegador:
# Dashboard: http://localhost:8000
# Mailpit (Webmail de testes): http://localhost:8025
```

#### Comandos Úteis com o Sail:
```bash
# Executar comandos Artisan
./vendor/bin/sail artisan [comando]

# Executar comandos Composer
./vendor/bin/sail composer [comando]

# Compilar assets ou iniciar o Vite (hot-reload)
./vendor/bin/sail npm run dev

# Abrir um terminal bash dentro do container da aplicação
./vendor/bin/sail bash

# Parar os containers
./vendor/bin/sail down
```

---

### Opção 2: Docker Compose Standalone (Build Autocontido / Produção)

Utiliza a imagem de produção em [`docker/Dockerfile`](docker/Dockerfile) com Nginx, PHP-FPM, supervisord e assets compilados via Vite:

```bash
# 1. Copie o arquivo de ambiente
cp .env.example .env

# 2. Inicie os containers com o arquivo de produção
docker compose -f docker-compose.prod.yml up -d --build

# 3. Acesse no navegador:
# http://localhost:8000

# 4. Para parar os containers:
docker compose -f docker-compose.prod.yml down
```

---

### Opção 3: Executando Localmente (PHP + Composer + MySQL)

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
npm install && npm run build
php artisan serve
```

---

## ⚙️ Variáveis de Ambiente Importantes

No seu arquivo `.env`:

| Variável | Padrão | Descrição |
| :--- | :--- | :--- |
| `APP_PORT` | `8000` | Porta HTTP da aplicação no host (evita conflitos com a porta 80). |
| `VITE_PORT` | `5174` | Porta do servidor de desenvolvimento Vite. |
| `DASHBOARD_REFRESH_INTERVAL_SECONDS` | `30` | Intervalo em segundos para atualização reativa automática do painel sem reload. |
| `APP_TIMEZONE` | `America/Sao_Paulo` | Fuso horário para registro e exibição das datas de execução. |
| `MAIL_MAILER` | `smtp` / `log` | Driver de envio de e-mails (`smtp`, `ses`, `mailgun`, etc.). |
| `MAIL_HOST`, `MAIL_PORT` | `mailpit` / `1025` | Configurações do Mailpit local no Sail ou servidor SMTP externo. |

---

## 📁 Coleção Insomnia

O arquivo [`insomnia.json`](insomnia.json) na raiz do projeto contém todas as requisições prontas e organizadas por pastas:
* **1. Clientes:** Cadastro e emissão de token.
* **2. Heartbeat & Serviços:** JSON Sucesso, JSON Falha e Multipart com upload de arquivo.
* **3. Dashboard & Métricas:** Polling de métricas.

---

## 🚢 Deploy (Coolify / Docker)

O repositório inclui o arquivo [`compose.coolify.yaml`](compose.coolify.yaml) pronto para implantação automatizada em instâncias **Coolify** ou qualquer servidor Docker com suporte a `compose`.

---

## 🧪 Testes Automatizados

Para rodar os testes automatizados da aplicação:

```bash
# Com Laravel Sail (Recomendado):
./vendor/bin/sail test
# ou:
./vendor/bin/sail artisan test

# Com Docker Compose Standalone:
docker compose -f docker-compose.prod.yml exec app php artisan test

# Localmente:
php artisan test
```
