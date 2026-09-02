<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\MonitoredService;
use App\Models\ServiceLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DashboardBasicAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_dashboard_access_when_auth_is_disabled(): void
    {
        Config::set('services.dashboard.auth_enabled', false);

        $response = $this->get('/');
        $response->assertOk();

        $apiResponse = $this->getJson('/api/dashboard/metrics');
        $apiResponse->assertOk();
    }

    public function test_blocks_dashboard_access_when_auth_is_enabled_and_no_credentials_sent(): void
    {
        Config::set('services.dashboard.auth_enabled', true);
        Config::set('services.dashboard.username', 'admin');
        Config::set('services.dashboard.password', 'secret123');

        $webResponse = $this->get('/');
        $webResponse->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Basic realm="Vigilant Dashboard"');

        $apiResponse = $this->getJson('/api/dashboard/metrics');
        $apiResponse->assertUnauthorized()
            ->assertJsonPath('error', 'unauthorized')
            ->assertHeader('WWW-Authenticate', 'Basic realm="Vigilant Dashboard"');
    }

    public function test_blocks_dashboard_access_with_invalid_credentials(): void
    {
        Config::set('services.dashboard.auth_enabled', true);
        Config::set('services.dashboard.username', 'admin');
        Config::set('services.dashboard.password', 'secret123');

        $response = $this->withHeaders([
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'wrong_password',
        ])->get('/');

        $response->assertUnauthorized();
    }

    public function test_allows_dashboard_access_with_valid_credentials(): void
    {
        Config::set('services.dashboard.auth_enabled', true);
        Config::set('services.dashboard.username', 'admin');
        Config::set('services.dashboard.password', 'secret123');

        $webResponse = $this->withHeaders([
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'secret123',
        ])->get('/');

        $webResponse->assertOk();

        $apiResponse = $this->withHeaders([
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'secret123',
        ])->getJson('/api/dashboard/metrics');

        $apiResponse->assertOk()
            ->assertJsonStructure(['metrics', 'clients', 'services']);
    }

    public function test_protects_log_download_route_with_basic_auth(): void
    {
        Storage::fake('local');

        Config::set('services.dashboard.auth_enabled', true);
        Config::set('services.dashboard.username', 'admin');
        Config::set('services.dashboard.password', 'secret123');

        $client = Client::create([
            'name' => 'Empresa Teste',
            'slug' => 'empresa-teste',
            'email' => 'teste@empresa.com',
            'api_token' => Client::generateToken(),
        ]);

        $service = MonitoredService::create([
            'client_id' => $client->id,
            'name' => 'Backup',
            'slug' => 'backup',
        ]);

        Storage::disk('local')->put('service-logs/test.log', 'Log de teste confidencial');

        $log = ServiceLog::create([
            'monitored_service_id' => $service->id,
            'status' => 'ok',
            'original_filename' => 'backup.log',
            'stored_path' => 'service-logs/test.log',
            'file_size' => 25,
            'log_excerpt' => 'Log de teste confidencial',
            'received_at' => now(),
        ]);

        // Sem autenticação -> Bloqueado
        $unauthorized = $this->get("/api/services/{$service->id}/logs/{$log->id}/download");
        $unauthorized->assertUnauthorized();

        // Com autenticação válida -> Download permitido
        $authorized = $this->withHeaders([
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'secret123',
        ])->get("/api/services/{$service->id}/logs/{$log->id}/download");

        $authorized->assertOk();
    }

    public function test_allows_dashboard_access_without_credentials_if_ip_is_whitelisted(): void
    {
        Config::set('services.dashboard.auth_enabled', true);
        Config::set('services.dashboard.username', 'admin');
        Config::set('services.dashboard.password', 'secret123');
        Config::set('services.dashboard.ip_whitelist', '192.168.1.50, 10.0.0.0/24');

        // IP na whitelist direta -> Acesso liberado sem login
        $directIpResponse = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.50'])->get('/');
        $directIpResponse->assertOk();

        // IP dentro do range CIDR da whitelist -> Acesso liberado sem login
        $cidrIpResponse = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.123'])->getJson('/api/dashboard/metrics');
        $cidrIpResponse->assertOk();

        // IP fora da whitelist -> Bloqueado sem credenciais
        $outsideIpResponse = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.19'])->get('/');
        $outsideIpResponse->assertUnauthorized();
    }
}

