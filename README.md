# Systems Control

Dashboard em **Laravel 13.26.1** para acompanhar a saúde dos sistemas da empresa e os logs de seus backups. A aplicação usa Blade para a interface, Eloquent para persistência e SQLite como banco padrão de desenvolvimento.

## O que está incluído

A tela inicial (`/`) exibe os sistemas cadastrados, o último resultado de healthcheck, o último backup recebido e os cinco logs mais recentes de cada sistema. Os dados aparecem automaticamente assim que os sistemas começam a enviar informações pela API.

A ingestão é protegida por token. O valor deve ser definido na variável `MONITORING_API_TOKEN` do arquivo `.env`. O token pode ser enviado como `Authorization: Bearer <token>` ou como `X-Monitoring-Token: <token>`.

| Método | Endpoint | Finalidade |
| --- | --- | --- |
| `POST` | `/api/healthchecks` | Cria ou atualiza o status e IP externo de um sistema |
| `POST` | `/api/backups/logs` | Recebe e armazena um arquivo de log de backup |
| `GET` | `/api/systems` | Consulta os sistemas e os cinco últimos backups de cada um |
| `GET` | `/api/dashboard/metrics` | Retorna métricas agregadas e sistemas para polling reativo |
| `GET` | `/` | Abre o dashboard operacional otimizado para monitores |


## Requisitos

É necessário ter PHP 8.3 ou superior, Composer e a extensão SQLite habilitada. O projeto já contém um banco SQLite vazio em `database/database.sqlite`.

## Instalação

```bash
cp .env.example .env
composer install
php artisan key:generate
mkdir -p database
touch database/database.sqlite
php artisan migrate
php artisan serve
```

No `.env`, substitua `change-this-token-in-production` por um token forte e defina a URL correta da aplicação:

```dotenv
APP_URL=http://localhost:8000
MONITORING_API_TOKEN=troque-por-um-token-longo-e-seguro
```

Depois, acesse `http://localhost:8000`.

## Enviando um healthcheck

O envio cria ou atualiza o sistema automaticamente, registrando seu status e IP externo. O `slug` e o `ip` são opcionais; quando o IP não for informado, a API detecta automaticamente o IP de origem da requisição.

```bash
curl -X POST http://localhost:8000/api/healthchecks \
  -H 'Authorization: Bearer troque-por-um-token-longo-e-seguro' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "ERP Principal",
    "slug": "erp-principal",
    "ok": true,
    "ip": "45.225.238.218",
    "message": "API respondeu em 42ms"
  }'
```

O campo `ok` pode ser `true` ou `false`. Também é possível usar `status` com os valores `ok`, `failed` ou `unknown`.

## Enviando um log de backup

O sistema precisa existir previamente, normalmente por meio de um healthcheck. O arquivo é validado e salvo no disco local privado em `storage/app/private/backup-logs`.

```bash
curl -X POST http://localhost:8000/api/backups/logs \
  -H 'X-Monitoring-Token: troque-por-um-token-longo-e-seguro' \
  -H 'Accept: application/json' \
  -F 'system=erp-principal' \
  -F 'status=success' \
  -F 'log_file=@/var/log/backup-erp.log'
```

O campo `status` aceita `success`, `failed`, `warning` ou `received`. O limite de upload é 10 MB. O banco guarda o nome original, tamanho, caminho privado, data de recebimento e um trecho de até 5.000 caracteres do arquivo para visualização rápida.

## Modelo de dados

A tabela `systems` guarda identidade, status do healthcheck e as datas de atividade. A tabela `backup_logs` pertence a um sistema e registra cada arquivo recebido. Ao receber um backup, `last_backup_at` do sistema é atualizado automaticamente.

## Coleção Insomnia

O arquivo [insomnia.json](file:///home/helitto/projects/sistemas-dashboard/insomnia.json) está disponível na raiz do projeto e pode ser importado diretamente no Insomnia (Import > From File).

Ele já inclui:
* Variáveis de ambiente (`base_url`, `token`, `system_slug`).
* Requisições de **Healthchecks** (OK com IP, Falha e via Header).
* Requisições de **Backups** (Upload de arquivo de log multipart).
* Requisição de **Listagem de Sistemas**.

## Testes

A suíte de testes cobre token obrigatório, criação e atualização de healthcheck com validação de IP, upload de log e renderização do dashboard:

```bash
php artisan test
```

Resultado validado neste pacote: **8 testes aprovados e 29 asserções**.

## Estrutura principal

```text
app/Http/Controllers/Api/HealthcheckController.php
app/Http/Controllers/Api/BackupLogController.php
app/Http/Controllers/DashboardController.php
app/Http/Middleware/VerifyMonitoringToken.php
app/Models/System.php
app/Models/BackupLog.php
database/migrations/*_create_systems_table.php
database/migrations/*_create_backup_logs_table.php
database/migrations/*_add_external_ip_to_systems_table.php
resources/views/dashboard.blade.php
routes/api.php
routes/web.php
insomnia.json
```

## Referências

[1]: https://laravel.com/docs/13.x/routing "Laravel 13.x — Routing"
[2]: https://laravel.com/docs/13.x/validation "Laravel 13.x — Validation"
[3]: https://laravel.com/docs/13.x/filesystem "Laravel 13.x — File Storage"
[4]: https://laravel.com/docs/13.x/migrations "Laravel 13.x — Database Migrations"
