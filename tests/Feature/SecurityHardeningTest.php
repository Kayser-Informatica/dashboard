<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\MonitoredService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_responses_contain_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_rejects_unauthorized_registration_when_public_registration_is_disabled(): void
    {
        Config::set('services.clients.allow_public_registration', false);
        Config::set('services.clients.registration_secret', 'chave-mestra-secreta');

        // Tentativa sem secret -> Bloqueada com 403
        $forbidden = $this->postJson('/api/clients/register', [
            'name' => 'Empresa Teste',
            'email' => 'teste@empresa.com',
        ]);

        $forbidden->assertForbidden()
            ->assertJsonPath('error', 'registration_forbidden');

        // Tentativa com secret errado -> Bloqueada com 403
        $wrongSecret = $this->withHeaders([
            'X-Registration-Secret' => 'chave-errada',
        ])->postJson('/api/clients/register', [
            'name' => 'Empresa Teste',
            'email' => 'teste@empresa.com',
        ]);

        $wrongSecret->assertForbidden();

        // Tentativa com secret correto via header -> Permitida
        $allowed = $this->withHeaders([
            'X-Registration-Secret' => 'chave-mestra-secreta',
        ])->postJson('/api/clients/register', [
            'name' => 'Empresa Autorizada',
            'email' => 'auth@empresa.com',
        ]);

        $allowed->assertCreated()
            ->assertJsonPath('client.name', 'Empresa Autorizada');
    }

    public function test_heartbeat_rejects_dangerous_file_extensions(): void
    {
        $plainToken = Client::generateToken();
        $client = Client::create([
            'name' => 'Empresa Teste',
            'slug' => 'empresa-teste',
            'email' => 'teste@empresa.com',
            'api_token' => Client::hashToken($plainToken),
        ]);

        // Arquivo .php executável -> Deve falhar na validação
        $dangerousFile = UploadedFile::fake()->create('malicious.php', 10, 'application/x-php');

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$plainToken}",
        ])->post('/api/heartbeat', [
            'service' => 'Backup',
            'log_file' => $dangerousFile,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['log_file']);
    }

    public function test_heartbeat_accepts_valid_log_file_extensions(): void
    {
        $plainToken = Client::generateToken();
        $client = Client::create([
            'name' => 'Empresa Teste',
            'slug' => 'empresa-teste',
            'email' => 'teste@empresa.com',
            'api_token' => Client::hashToken($plainToken),
        ]);

        // Arquivo .log válido -> Deve passar com sucesso
        $validFile = UploadedFile::fake()->create('execucao.log', 10, 'text/plain');

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$plainToken}",
        ])->post('/api/heartbeat', [
            'service' => 'Backup',
            'log_file' => $validFile,
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('service.name', 'Backup')
            ->assertJsonPath('service.has_log_attached', true);
    }
}
