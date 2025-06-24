<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Add scheduled tasks here, for example:
        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        // Load commands from the `app/Console/Commands` directory
        $this->load(__DIR__.'/Commands');

        // Include additional custom console routes
        require base_path('routes/console.php');
    }

    /**
     * Register the application's middleware.
     *
     * @return void
     */
    protected function middleware()
    {
        $this->middleware([
            \Illuminate\Http\Middleware\PreventRequestsDuringMaintenance::class,
            // Add any other custom middleware you may need
        ]);
    }
}
