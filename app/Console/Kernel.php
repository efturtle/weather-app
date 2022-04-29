<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

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
        $schedule->call( function() {
            Http:post('http://127.0.0.1:8000/api/v1/weather', [
                'date' => now()->format('Y-m-d'),
            ]);
        })->daily();

        $schedule->call( function() {
            Http::put('http://127.0.0.1:8000/api/v1/weather', [
                'date' => now()->format('Y-m-d'),
            ]);
        })->everySixHours();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
