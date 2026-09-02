<?php

namespace App\Console\Commands;

use App\Models\ServiceLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneServiceLogs extends Command
{
    protected $signature = 'logs:prune
                            {--days= : Número de dias de retenção para expurgo de logs (padrão do .env ou 30)}
                            {--dry-run : Apenas simula o expurgo sem excluir arquivos ou registros}';

    protected $description = 'Expurga registros e arquivos físicos de logs de serviços mais antigos que o período de retenção';

    public function handle(): int
    {
        $daysOption = $this->option('days');
        $retentionDays = $daysOption !== null ? (int) $daysOption : (int) config('services.dashboard.log_retention_days', 30);
        $isDryRun = (bool) $this->option('dry-run');

        if ($retentionDays <= 0) {
            $this->error('O período de retenção em dias deve ser um número inteiro positivo maior que zero.');
            return Command::INVALID;
        }

        $cutoff = now()->subDays($retentionDays);

        $this->info("Iniciando expurgo de logs com mais de {$retentionDays} dias (anteriores a {$cutoff->format('d/m/Y H:i:s')})...");

        if ($isDryRun) {
            $this->warn('[DRY-RUN] Modo de simulação ativado. Nenhum arquivo ou registro será modificado.');
        }

        $logsQuery = ServiceLog::where('received_at', '<', $cutoff);
        $totalLogsCount = $logsQuery->count();

        if ($totalLogsCount === 0) {
            $this->info('Nenhum log antigo encontrado para expurgo.');
            return Command::SUCCESS;
        }

        $deletedRecords = 0;
        $deletedFiles = 0;
        $freedBytes = 0;

        // Processamento em chunks de 100 para economia de memória
        $logsQuery->orderBy('id')->chunk(100, function ($logs) use (&$deletedRecords, &$deletedFiles, &$freedBytes, $isDryRun): void {
            foreach ($logs as $log) {
                $fileSize = $log->file_size ?: 0;
                $storedPath = $log->stored_path;

                if ($storedPath && Storage::exists($storedPath)) {
                    if (! $isDryRun) {
                        Storage::delete($storedPath);
                    }
                    $deletedFiles++;
                    $freedBytes += $fileSize;
                }

                if (! $isDryRun) {
                    $log->delete();
                }

                $deletedRecords++;
            }
        });

        $freedFormatted = number_format($freedBytes / (1024 * 1024), 2, ',', '.') . ' MB';
        $actionWord = $isDryRun ? 'identificados para exclusão' : 'excluídos com sucesso';

        $this->info("Expurgo concluído:");
        $this->line("- Registros de log no banco: {$deletedRecords} {$actionWord}.");
        $this->line("- Arquivos físicos no storage: {$deletedFiles} {$actionWord}.");
        $this->line("- Espaço em disco liberado (estimado): {$freedFormatted}.");

        return Command::SUCCESS;
    }
}
