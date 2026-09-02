<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\MonitoredService;
use App\Models\ServiceLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PruneServiceLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_prunes_logs_older_than_retention_days_and_removes_physical_files(): void
    {
        Storage::fake('local');
        Config::set('services.dashboard.log_retention_days', 30);

        $client = Client::create([
            'name' => 'Cliente Alpha',
            'slug' => 'cliente-alpha',
            'email' => 'alpha@empresa.com',
            'api_token' => Client::generateToken(),
        ]);

        $service = MonitoredService::create([
            'client_id' => $client->id,
            'name' => 'Backup SQL',
            'slug' => 'backup-sql',
        ]);

        // Arquivo e log antigo (40 dias atrás) -> Deve ser expurgado
        Storage::disk('local')->put('service-logs/old_log.txt', 'Log antigo de 40 dias atras');
        $oldLog = ServiceLog::create([
            'monitored_service_id' => $service->id,
            'status' => 'ok',
            'original_filename' => 'old_log.txt',
            'stored_path' => 'service-logs/old_log.txt',
            'file_size' => 28,
            'received_at' => now()->subDays(40),
        ]);

        // Arquivo e log recente (10 dias atrás) -> Deve ser preservado
        Storage::disk('local')->put('service-logs/recent_log.txt', 'Log recente de 10 dias atras');
        $recentLog = ServiceLog::create([
            'monitored_service_id' => $service->id,
            'status' => 'ok',
            'original_filename' => 'recent_log.txt',
            'stored_path' => 'service-logs/recent_log.txt',
            'file_size' => 29,
            'received_at' => now()->subDays(10),
        ]);

        // Executar comando
        $this->artisan('logs:prune')
            ->expectsOutputToContain('Expurgo concluído')
            ->assertSuccessful();

        // Verifica que o log antigo foi deletado do banco e do storage
        $this->assertDatabaseMissing('service_logs', ['id' => $oldLog->id]);
        Storage::disk('local')->assertMissing('service-logs/old_log.txt');

        // Verifica que o log recente foi mantido intacto
        $this->assertDatabaseHas('service_logs', ['id' => $recentLog->id]);
        Storage::disk('local')->assertExists('service-logs/recent_log.txt');
    }

    public function test_dry_run_option_simulates_without_deleting_files_or_records(): void
    {
        Storage::fake('local');
        Config::set('services.dashboard.log_retention_days', 30);

        $client = Client::create([
            'name' => 'Cliente Beta',
            'slug' => 'cliente-beta',
            'email' => 'beta@empresa.com',
            'api_token' => Client::generateToken(),
        ]);

        $service = MonitoredService::create([
            'client_id' => $client->id,
            'name' => 'Backup DB',
            'slug' => 'backup-db',
        ]);

        Storage::disk('local')->put('service-logs/simulated.txt', 'Conteudo simulado');
        $log = ServiceLog::create([
            'monitored_service_id' => $service->id,
            'status' => 'ok',
            'original_filename' => 'simulated.txt',
            'stored_path' => 'service-logs/simulated.txt',
            'file_size' => 17,
            'received_at' => now()->subDays(45),
        ]);

        // Executar com --dry-run
        $this->artisan('logs:prune --dry-run')
            ->expectsOutputToContain('[DRY-RUN] Modo de simulação ativado')
            ->assertSuccessful();

        // Não deve ter apagado nem o registro nem o arquivo
        $this->assertDatabaseHas('service_logs', ['id' => $log->id]);
        Storage::disk('local')->assertExists('service-logs/simulated.txt');
    }
}
