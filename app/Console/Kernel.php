<?php

namespace App\Console;

use App\Console\Commands\FetchRemoteJson;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array<int, class-string>
     */
    protected $commands = [
        FetchRemoteJson::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('import:fetch-json')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground()
            ->description('Fetch a JSON payload from the remote endpoint for import.');
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
