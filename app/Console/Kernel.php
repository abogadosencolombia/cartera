<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * Este es el "despertador" de tu aplicación. Aquí se definen todas las
     * tareas automáticas (Cron Jobs) que se ejecutarán en el servidor.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        // --- HORARIO DE TAREAS NOCTURNAS ESTRATÉGICAS ---
        // Las tareas se ejecutan de forma escalonada para evitar conflictos
        // y se protegen para no solaparse si alguna tarda más de lo normal.

        // TAREA 1: Validar el estado de las cooperativas.
        // Se ejecuta primero para tener la información más actualizada.
        $schedule->command('cooperativas:validar-diariamente')
                 ->dailyAt('01:00') // Se ejecuta todos los días a la 1:00 AM
                 ->timezone('America/Bogota') // Usando la zona horaria de Colombia
                 ->withoutOverlapping(); // No se ejecuta si la tarea anterior sigue corriendo

        // TAREA 2: Generar alertas basadas en reglas.
        // Se ejecuta 5 minutos después de la validación.
        $schedule->command('alertas:generar')
                 ->dailyAt('01:05') // Se ejecuta todos los días a la 1:05 AM
                 ->timezone('America/Bogota')
                 ->withoutOverlapping();

        // TAREA 3: Job para verificar cumplimiento legal.
        // Se ejecuta 5 minutos después de las alertas.
        $schedule->job(new \App\Jobs\VerificarCumplimientoLegalJob())
                 ->dailyAt('01:10') // Se ejecuta todos los días a la 1:10 AM
                 ->timezone('America/Bogota')
                 ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
