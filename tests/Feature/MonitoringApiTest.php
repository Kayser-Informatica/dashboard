<?php

namespace Tests\Feature;

use App\Models\BackupLog;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MonitoringApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.monitoring.token', 'test-token');
    }

    public function test_healthcheck_requires_token(): void
    {
        $response = $this->postJson('/api/healthchecks', [
            'name' => 'ERP Principal',
            'ok' => true,
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('systems', 0);
    }

    public function test_healthcheck_creates_and_updates_a_system(): void
    {
        $payload = [
            'name' => 'ERP Principal',
            'slug' => 'erp-principal',
            'ok' => true,
            'message' => 'API respondeu em 42ms',
            'ip' => '45.225.238.218',
        ];

        $this->withHeader('Authorization', 'Bearer test-token')
            ->withServerVariables(['REMOTE_ADDR' => '45.225.238.218'])
            ->postJson('/api/healthchecks', $payload)
            ->assertOk()
            ->assertJsonPath('system.slug', 'erp-principal')
            ->assertJsonPath('system.status', 'ok')
            ->assertJsonPath('system.external_ip', '45.225.238.218');

        $this->withHeader('X-Monitoring-Token', 'test-token')
            ->postJson('/api/healthchecks', [
                'name' => 'ERP Principal',
                'slug' => 'erp-principal',
                'ok' => false,
            ])
            ->assertOk()
            ->assertJsonPath('system.status', 'failed');

        $this->assertDatabaseHas('systems', [
            'slug' => 'erp-principal',
            'last_health_status' => 'failed',
            'external_ip' => '45.225.238.218',
        ]);
    }

    public function test_healthcheck_rejects_mismatched_ip(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer test-token')
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.42'])
            ->postJson('/api/healthchecks', [
                'name' => 'ERP Principal',
                'ok' => true,
                'ip' => '203.0.113.99',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'ip_mismatch')
            ->assertJsonPath('detected_ip', '198.51.100.42')
            ->assertJsonPath('provided_ip', '203.0.113.99');
    }

    public function test_healthcheck_resolves_request_ip_when_not_provided(): void
    {
        $this->withHeader('Authorization', 'Bearer test-token')
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.42'])
            ->postJson('/api/healthchecks', [
                'name' => 'Gateway Pagamentos',
                'ok' => true,
            ])
            ->assertOk()
            ->assertJsonPath('system.external_ip', '198.51.100.42');

        $this->assertDatabaseHas('systems', [
            'slug' => 'gateway-pagamentos',
            'external_ip' => '198.51.100.42',
        ]);
    }

    public function test_backup_log_is_stored_for_an_existing_system(): void
    {
        Storage::fake('local');
        $system = System::create([
            'name' => 'ERP Principal',
            'slug' => 'erp-principal',
            'last_health_status' => 'ok',
            'last_health_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer test-token')
            ->post('/api/backups/logs', [
                'system' => 'erp-principal',
                'status' => 'success',
                'log_file' => UploadedFile::fake()->createWithContent(
                    'backup-2026-08-20.log',
                    "Backup started\nBackup completed successfully",
                ),
            ]);

        $response->assertCreated()
            ->assertJsonPath('backup.status', 'success')
            ->assertJsonPath('backup.system', 'erp-principal');

        $this->assertDatabaseHas('backup_logs', [
            'system_id' => $system->id,
            'status' => 'success',
            'original_filename' => 'backup-2026-08-20.log',
        ]);

        $this->assertNotNull($system->fresh()->last_backup_at);
        Storage::disk('local')->assertExists(BackupLog::first()->stored_path);
    }

    public function test_systems_api_returns_systems_and_backup_logs(): void
    {
        $system = System::create([
            'name' => 'ERP Principal',
            'slug' => 'erp-principal',
            'last_health_status' => 'ok',
            'last_health_at' => now(),
            'external_ip' => '45.225.238.218',
        ]);

        BackupLog::create([
            'system_id' => $system->id,
            'status' => 'success',
            'original_filename' => 'backup.log',
            'stored_path' => 'backup-logs/backup.log',
            'file_size' => 2048,
            'log_excerpt' => 'Backup completed successfully',
            'received_at' => now(),
        ]);

        $response = $this->getJson('/api/systems');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'external_ip',
                        'last_health_status',
                        'backup_logs_count',
                        'backup_logs',
                    ],
                ],
            ])
            ->assertJsonPath('data.0.name', 'ERP Principal');
    }
}

