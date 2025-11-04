<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\FetchEmails::class,
        Commands\DeployCRM::class,
        Commands\RollbackCRM::class,
    ];
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('queue:work --timeout=600 --tries=3')->everyMinute()->withoutOverlapping();
        // $schedule->command('inspire')->hourly();
        // $schedule->command('app:fetch-emails')->timezone('Asia/Karachi')->dailyAt('09:00');
        // $schedule->command('app:fetch-emails')->timezone('Asia/Karachi')->dailyAt('18:00');
        $schedule->command('app:fetch-emails')->timezone('Asia/Karachi')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
