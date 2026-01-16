<?php

namespace App\Console;

use App\Console\Commands\DripsDispatchDueSteps;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Dispatch due drip steps into the existing messages queue.
        $schedule->command('drips:dispatch-due-steps --limit=500')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer();

        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        // Ensure the drips command is registered even if command discovery/load changes.
        $this->commands([
            DripsDispatchDueSteps::class,
        ]);

        require base_path('routes/console.php');
    }
}
