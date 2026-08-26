<?php

namespace Tests\Feature;

use App\Mail\ServiceAlertMail;
use App\Mail\ServiceRecoveredMail;
use App\Models\Client;
use App\Models\MonitoredService;
use App\Models\ServiceLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MonitoredServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register_a_new_client_and_receive_api_token(): void
    {
        $response = $this->postJson('/api/clients/register', [
            'name' => 'NeeMedT',
            'slug' => 'neemedt',
        ]);

        $response->assertCreated()
            ->assertJsonPath('client.name', 'NeeMedT')
            ->assertJsonPath('client.slug', 'neemedt')
            ->assertJsonStructure(['message', 'client' => ['id', 'name', 'slug'], 'api_token']);

        $token = $response->json('api_token');
        $this->assertStringStartsWith('clt_live_', $token);

        $this->assertDatabaseHas('clients', [
            'name' => 'NeeMedT',
            'slug' => 'neemedt',
            'api_token' => $token,
        ]);
    }

    public function test_rejects_duplicate_client_registration(): void
    {
        Client::create([
            'name' => 'NeeMedT',
            'slug' => 'neemedt',
            'api_token' => Client::generateToken(),
        ]);

        $response = $this->postJson('/api/clients/register', [
            'name' => 'NeeMedT',
            'slug' => 'neemedt',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'client_already_exists');
    }

    public function test_heartbeat_requires_valid_client_token(): void
    {
        $response = $this->postJson('/api/heartbeat', [
            'service' => 'Backup do sistema',
        ]);

        $response->assertUnauthorized();
    }

    public function test_heartbeat_creates_monitored_service_and_sets_interval(): void
    {
        $client = Client::create([
            'name' => 'NeeMedT',
            'slug' => 'neemedt',
            'api_token' => Client::generateToken(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$client->api_token}")
            ->postJson('/api/heartbeat', [
                'service' => 'Envio de emails do financeiro',
                'interval_minutes' => 60,
                'grace_minutes' => 10,
                'notification_emails' => 'ti@neemedt.com; lucas@empresa.com',
                'ok' => true,
                'message' => '50 emails enviados com sucesso',
                'duration_seconds' => 3,
            ]);

        $response->assertOk()
            ->assertJsonPath('service.name', 'Envio de emails do financeiro')
            ->assertJsonPath('service.slug', 'envio-de-emails-do-financeiro')
            ->assertJsonPath('service.status', 'ok')
            ->assertJsonPath('service.interval_minutes', 60)
            ->assertJsonPath('service.grace_minutes', 10);

        $this->assertDatabaseHas('monitored_services', [
            'client_id' => $client->id,
            'slug' => 'envio-de-emails-do-financeiro',
            'expected_interval_minutes' => 60,
            'grace_period_minutes' => 10,
            'last_status' => 'ok',
            'last_message' => '50 emails enviados com sucesso',
            'last_duration_seconds' => 3,
        ]);
    }

    public function test_heartbeat_can_upload_log_file(): void
    {
        Storage::fake('local');

        $client = Client::create([
            'name' => 'NeeMedT',
            'slug' => 'neemedt',
            'api_token' => Client::generateToken(),
        ]);

        $file = UploadedFile::fake()->createWithContent('backup-daily.log', "INICIANDO BACKUP\nSUCESSO: 1.2GB COPIADOS\nFIM");

        $response = $this->withHeader('Authorization', "Bearer {$client->api_token}")
            ->post('/api/heartbeat', [
                'service' => 'Backup do sistema',
                'interval_minutes' => 1440,
                'ok' => 'true',
                'log_file' => $file,
            ]);

        $response->assertOk()
            ->assertJsonPath('service.has_log_attached', true);

        $service = MonitoredService::where('slug', 'backup-do-sistema')->first();
        $this->assertNotNull($service);

        $this->assertDatabaseHas('service_logs', [
            'monitored_service_id' => $service->id,
            'original_filename' => 'backup-daily.log',
            'status' => 'ok',
        ]);

        $serviceLog = ServiceLog::first();
        $this->assertStringContainsString('SUCESSO: 1.2GB COPIADOS', $serviceLog->log_excerpt);
        Storage::assertExists($serviceLog->stored_path);
    }

    public function test_heartbeat_failure_sends_alert_mail(): void
    {
        Mail::fake();

        $client = Client::create([
            'name' => 'NeeMedT',
            'slug' => 'neemedt',
            'api_token' => Client::generateToken(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$client->api_token}")
            ->postJson('/api/heartbeat', [
                'service' => 'Envio de emails do financeiro',
                'interval_minutes' => 60,
                'notification_emails' => 'ti@neemedt.com; alerts@infra.com',
                'ok' => false,
                'message' => 'Falha crítica: Falha ao autenticar no servidor SMTP',
            ]);

        $response->assertOk();

        Mail::assertSent(ServiceAlertMail::class, function (ServiceAlertMail $mail) {
            return $mail->hasTo('ti@neemedt.com') &&
                   $mail->hasTo('alerts@infra.com') &&
                   $mail->alertType === 'failed';
        });

        $service = MonitoredService::first();
        $this->assertTrue($service->is_in_alert);
    }

    public function test_heartbeat_recovery_sends_recovery_mail_when_previously_in_alert(): void
    {
        Mail::fake();

        $client = Client::create([
            'name' => 'NeeMedT',
            'slug' => 'neemedt',
            'api_token' => Client::generateToken(),
        ]);

        $service = MonitoredService::create([
            'client_id' => $client->id,
            'name' => 'Envio de emails do financeiro',
            'slug' => 'envio-de-emails-do-financeiro',
            'expected_interval_minutes' => 60,
            'notification_emails' => 'ti@neemedt.com',
            'last_status' => 'failed',
            'is_in_alert' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$client->api_token}")
            ->postJson('/api/heartbeat', [
                'service' => 'Envio de emails do financeiro',
                'ok' => true,
                'message' => 'Normalizado',
            ]);

        $response->assertOk();

        Mail::assertSent(ServiceRecoveredMail::class, function (ServiceRecoveredMail $mail) {
            return $mail->hasTo('ti@neemedt.com');
        });

        $this->assertFalse($service->fresh()->is_in_alert);
    }

    public function test_command_detects_overdue_services_and_sends_alert(): void
    {
        Mail::fake();

        $client = Client::create([
            'name' => 'NeeMedT',
            'slug' => 'neemedt',
            'api_token' => Client::generateToken(),
        ]);

        // Serviço que deveria rodar a cada 30 min + 5 min tolerancia, mas rodou há 2 horas
        $service = MonitoredService::create([
            'client_id' => $client->id,
            'name' => 'Rotina de Sincronização',
            'slug' => 'rotina-de-sincronizacao',
            'expected_interval_minutes' => 30,
            'grace_period_minutes' => 5,
            'notification_emails' => 'admin@neemedt.com',
            'last_ping_at' => now()->subHours(2),
            'last_status' => 'ok',
            'is_in_alert' => false,
            'active' => true,
        ]);

        $this->assertTrue($service->is_overdue);

        Artisan::call('monitors:check-deadlines');

        Mail::assertSent(ServiceAlertMail::class, function (ServiceAlertMail $mail) {
            return $mail->hasTo('admin@neemedt.com') && $mail->alertType === 'overdue';
        });

        $this->assertTrue($service->fresh()->is_in_alert);
    }

    public function test_dashboard_and_metrics_api_render_clients_and_services(): void
    {
        $client = Client::create([
            'name' => 'NeeMedT',
            'slug' => 'neemedt',
            'api_token' => Client::generateToken(),
        ]);

        $service = MonitoredService::create([
            'client_id' => $client->id,
            'name' => 'Envio de emails do financeiro',
            'slug' => 'envio-de-emails-do-financeiro',
            'expected_interval_minutes' => 60,
            'last_ping_at' => now(),
            'last_status' => 'ok',
            'last_message' => 'Tudo certo',
        ]);

        ServiceLog::create([
            'monitored_service_id' => $service->id,
            'status' => 'ok',
            'original_filename' => 'output.log',
            'file_size' => 1024,
            'log_excerpt' => 'Processo concluído',
            'received_at' => now(),
        ]);

        // Testar página HTML
        $this->get('/')
            ->assertOk()
            ->assertSee('NeeMedT')
            ->assertSee('Envio de emails do financeiro')
            ->assertSee('output.log')
            ->assertSee('Operacional');

        // Testar API JSON
        $this->getJson('/api/dashboard/metrics')
            ->assertOk()
            ->assertJsonPath('metrics.clients_count', 1)
            ->assertJsonPath('metrics.total', 1)
            ->assertJsonPath('metrics.online', 1)
            ->assertJsonPath('clients.0.name', 'NeeMedT')
            ->assertJsonPath('clients.0.services.0.name', 'Envio de emails do financeiro');
    }
}
