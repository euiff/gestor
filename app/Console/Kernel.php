<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\ScheduleSetting;
use App\Models\Campanha;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Agendar comandos existentes
        $settings = ScheduleSetting::all();

        foreach ($settings as $setting) {
            $schedule->call(function () use ($setting) {
                \Artisan::call('clientes:verificar-vencidos', [
                    'user_id' => $setting->user_id,
                    'finalidade' => $setting->finalidade
                ]);
            })->dailyAt($setting->execution_time);
        }

        // Obter todas as campanhas
        $campanhas = Campanha::all();

        foreach ($campanhas as $campanha) {
            $schedule->command('campanhas:disparar')
                ->dailyAt($campanha->horario)
                ->when(function () use ($campanha) {
                    // Verificar se a campanha deve ser disparada hoje
                    if ($campanha->data) {
                        // Se a data estiver definida, verificar se é hoje
                        return Carbon::parse($campanha->data)->isToday();
                    } else {
                        // Se a data não estiver definida, disparar diariamente no horário especificado
                        return true;
                    }
                });
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Register the custom commands for the application.
     */
    // protected $commands = [
    //     \App\Console\Commands\InstallServer::class,
    // ];
}