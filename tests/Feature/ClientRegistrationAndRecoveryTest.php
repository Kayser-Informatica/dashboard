<?php

namespace Tests\Feature;

use App\Mail\ClientTokenRecoveryMail;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClientRegistrationAndRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_fails_if_email_is_missing(): void
    {
        $response = $this->postJson('/api/clients/register', [
            'name' => 'NeeMedT',
            'slug' => 'neemedt',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_fails_if_email_is_invalid(): void
    {
        $response = $this->postJson('/api/clients/register', [
            'name' => 'NeeMedT',
            'slug' => 'neemedt',
            'email' => 'not-a-valid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_can_register_client_with_valid_email(): void
    {
        $response = $this->postJson('/api/clients/register', [
            'name' => 'Kayser Filial Sul',
            'slug' => 'kayser-filial-sul',
            'email' => 'admin@kayser.com.br',
        ]);

        $response->assertCreated()
            ->assertJsonPath('client.name', 'Kayser Filial Sul')
            ->assertJsonPath('client.slug', 'kayser-filial-sul')
            ->assertJsonPath('client.email', 'admin@kayser.com.br')
            ->assertJsonStructure([
                'message',
                'client' => ['id', 'name', 'slug', 'email'],
                'api_token',
            ]);

        $plainToken = $response->json('api_token');
        $this->assertNotEmpty($plainToken);

        $this->assertDatabaseHas('clients', [
            'name' => 'Kayser Filial Sul',
            'slug' => 'kayser-filial-sul',
            'email' => 'admin@kayser.com.br',
            'api_token' => hash('sha256', $plainToken),
        ]);
    }

    public function test_multiple_clients_can_use_the_same_email(): void
    {
        $response1 = $this->postJson('/api/clients/register', [
            'name' => 'Empresa Matriz',
            'slug' => 'empresa-matriz',
            'email' => 'lucas@empresa.com.br',
        ]);
        $response1->assertCreated();

        $response2 = $this->postJson('/api/clients/register', [
            'name' => 'Empresa Filial 01',
            'slug' => 'empresa-filial-01',
            'email' => 'lucas@empresa.com.br',
        ]);
        $response2->assertCreated();

        $this->assertEquals(2, Client::where('email', 'lucas@empresa.com.br')->count());
    }

    public function test_cannot_alter_email_after_client_creation(): void
    {
        $client = Client::create([
            'name' => 'Cliente Original',
            'slug' => 'cliente-original',
            'email' => 'original@empresa.com',
            'api_token' => Client::generateToken(),
        ]);

        // Tentativa de alterar o e-mail via Eloquent
        $client->update([
            'email' => 'hacked@empresa.com',
            'name' => 'Cliente Nome Atualizado',
        ]);

        $freshClient = $client->fresh();
        $this->assertEquals('original@empresa.com', $freshClient->email);
        $this->assertEquals('Cliente Nome Atualizado', $freshClient->name);
    }

    public function test_recover_token_fails_validation_if_fields_are_missing(): void
    {
        $response = $this->postJson('/api/clients/recover-token', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'client']);
    }

    public function test_recover_token_sends_email_with_token_when_client_and_email_match(): void
    {
        Mail::fake();

        $token = Client::generateToken();
        $client = Client::create([
            'name' => 'NeeMedT Diagnósticos',
            'slug' => 'neemedt-diagnosticos',
            'email' => 'suporte@neemedt.com',
            'api_token' => Client::hashToken($token),
        ]);

        // Teste passando o slug do cliente
        $response = $this->postJson('/api/clients/recover-token', [
            'email' => 'suporte@neemedt.com',
            'client' => 'neemedt-diagnosticos',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message']);

        Mail::assertSent(ClientTokenRecoveryMail::class, function (ClientTokenRecoveryMail $mail) use ($client) {
            return $mail->hasTo('suporte@neemedt.com') &&
                   $mail->client->id === $client->id &&
                   $mail->plainToken !== null &&
                   str_starts_with($mail->plainToken, 'clt_live_');
        });
    }

    public function test_recover_token_works_using_client_name(): void
    {
        Mail::fake();

        $client = Client::create([
            'name' => 'Clínica Saúde Total',
            'slug' => 'clinica-saude-total',
            'email' => 'diretoria@saudetotal.med.br',
            'api_token' => Client::generateToken(),
        ]);

        // Teste passando o nome exato
        $response = $this->postJson('/api/clients/recover-token', [
            'email' => 'diretoria@saudetotal.med.br',
            'client' => 'Clínica Saúde Total',
        ]);

        $response->assertOk();

        Mail::assertSent(ClientTokenRecoveryMail::class, function (ClientTokenRecoveryMail $mail) use ($client) {
            return $mail->hasTo('diretoria@saudetotal.med.br') && $mail->client->id === $client->id;
        });
    }

    public function test_recover_token_does_not_send_email_if_client_or_email_do_not_match(): void
    {
        Mail::fake();

        Client::create([
            'name' => 'NeeMedT Diagnósticos',
            'slug' => 'neemedt-diagnosticos',
            'email' => 'suporte@neemedt.com',
            'api_token' => Client::generateToken(),
        ]);

        // E-mail correto, mas cliente inexistente
        $response1 = $this->postJson('/api/clients/recover-token', [
            'email' => 'suporte@neemedt.com',
            'client' => 'outro-cliente-fantasma',
        ]);
        $response1->assertOk();

        // Cliente correto, mas e-mail não correspondente
        $response2 = $this->postJson('/api/clients/recover-token', [
            'email' => 'intruso@desconhecido.com',
            'client' => 'neemedt-diagnosticos',
        ]);
        $response2->assertOk();

        Mail::assertNothingSent();
    }
}
