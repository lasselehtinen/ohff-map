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
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('update:references')
            ->dailyAt('06:00')
            ->pingBefore('https://hc-ping.com/36f355c2-6e24-4fc5-8a0d-5cf361666d57/start')
            ->pingOnSuccess('https://hc-ping.com/36f355c2-6e24-4fc5-8a0d-5cf361666d57')
            ->pingOnFailure('https://hc-ping.com/36f355c2-6e24-4fc5-8a0d-5cf361666d57/fail');

        $schedule->command('update:activations')
            ->daily()
            ->pingBefore('https://hc-ping.com/f005c83b-60b6-4433-af16-d6a738b9da1c/start')
            ->pingOnSuccess('https://hc-ping.com/f005c83b-60b6-4433-af16-d6a738b9da1c')
            ->pingOnFailure('https://hc-ping.com/f005c83b-60b6-4433-af16-d6a738b9da1c/fail');

        $schedule->command('parse:protected_planet')
            ->daily()
            ->pingBefore('https://hc-ping.com/4763e317-c6c8-43c6-8e00-5d6206dbc604/start')
            ->pingOnSuccess('https://hc-ping.com/4763e317-c6c8-43c6-8e00-5d6206dbc604')
            ->pingOnFailure('https://hc-ping.com/4763e317-c6c8-43c6-8e00-5d6206dbc604/fail');

        $schedule->command('generate:csv')
            ->daily()
            ->pingBefore('https://hc-ping.com/24bc9e2a-6a24-496f-adb5-f662c4399870/start')
            ->pingOnSuccess('https://hc-ping.com/24bc9e2a-6a24-496f-adb5-f662c4399870')
            ->pingOnFailure('https://hc-ping.com/24bc9e2a-6a24-496f-adb5-f662c4399870/fail');

        $schedule->command('backup:run --only-db')
            ->everySixHours()
            ->pingBefore('https://hc-ping.com/7a294d83-319d-49d0-b58f-cfddd3aed84f/start')
            ->pingOnSuccess('https://hc-ping.com/7a294d83-319d-49d0-b58f-cfddd3aed84f')
            ->pingOnFailure('https://hc-ping.com/7a294d83-319d-49d0-b58f-cfddd3aed84f/fail');

        $schedule->command('backup:clean')
            ->daily()
            ->pingBefore('https://hc-ping.com/b92d7b26-4f60-45a7-a46d-d4875dd615b3/start')
            ->pingOnSuccess('https://hc-ping.com/b92d7b26-4f60-45a7-a46d-d4875dd615b3')
            ->pingOnFailure('https://hc-ping.com/b92d7b26-4f60-45a7-a46d-d4875dd615b3/fail');
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
