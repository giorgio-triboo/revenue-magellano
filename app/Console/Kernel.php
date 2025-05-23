<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\CleanupSystemCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Esegui il comando di pulizia ogni giorno alle 02:00
        $schedule->command('system:cleanup')
                ->dailyAt('02:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/scheduler.log'));

        // Esegui il queue worker ogni minuto
        $schedule->command('queue:work --queue=csv-processing,ax-export --tries=3 --timeout=3600')
                ->everyMinute()
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/queue-worker.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}