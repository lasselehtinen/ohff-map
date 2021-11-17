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
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('update:references')
         ->daily()
         ->pingBefore('https://hc-ping.com/36f355c2-6e24-4fc5-8a0d-5cf361666d57/start')
         ->pingOnSuccess('https://hc-ping.com/36f355c2-6e24-4fc5-8a0d-5cf361666d57')
         ->pingOnFailure('https://hc-ping.com/36f355c2-6e24-4fc5-8a0d-5cf361666d57/fail');
        
        $schedule->command('update:activations')
         ->weekly()
         ->pingBefore('https://hc-ping.com/f005c83b-60b6-4433-af16-d6a738b9da1c/start')
         ->pingOnSuccess('https://hc-ping.com/f005c83b-60b6-4433-af16-d6a738b9da1c')
         ->pingOnFailure('https://hc-ping.com/f005c83b-60b6-4433-af16-d6a738b9da1c/fail');
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
