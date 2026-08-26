<?php

namespace App\Console\Commands;

use App\Mail\ServiceAlertMail;
use App\Models\MonitoredService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckMonitoredServiceDeadlines extends Command
{
    protected $signature = 'monitors:check-deadlines';

    protected $description = 'Verifica os serviços que ultrapassaram o tempo esperado de heartbeat e dispara alertas por e-mail';

    public function handle(): int
    {
        $services = MonitoredService::with('client')
            ->where('active', true)
            ->whereNotNull('last_ping_at')
            ->get();

        $alertsCount = 0;
        $now = now();

        foreach ($services as $service) {
            if ($service->is_overdue) {
                // Se ainda não estava marcado como em alerta, disparar o e-mail
                if (! $service->is_in_alert) {
                    $service->is_in_alert = true;
                    $service->last_alert_sent_at = $now;
                    $service->save();

                    $emails = $service->getNotificationEmailsArray();

                    if (! empty($emails)) {
                        try {
                            $overdueMinutes = (int) $service->next_expected_at?->diffInMinutes($now);
                            $details = "O serviço não enviou sinal de vida no prazo estipulado. Está atrasado há aproximadamente {$overdueMinutes} minutos.";
                            
                            Mail::to($emails)->send(new ServiceAlertMail($service, 'overdue', $details));
                            $this->info("Alerta de atraso enviado para {$service->client?->name} - {$service->name} (Destinatários: " . implode(', ', $emails) . ")");
                            $alertsCount++;
                        } catch (\Throwable $e) {
                            Log::error("Falha ao enviar email de atraso para o servico {$service->id}: " . $e->getMessage());
                            $this->error("Erro ao enviar email para o servico {$service->id}: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        $this->info("Verificação de prazos concluída. Novos alertas disparados: {$alertsCount}.");

        return Command::SUCCESS;
    }
}
