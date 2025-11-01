<?php

namespace App\Console;

use App\Console\Commands\FetchRemoteJson;
use App\Console\Commands\SyncCrmAgents;
use App\Console\Commands\SyncGoogleSheetsAgents;
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
        SyncCrmAgents::class,
        SyncGoogleSheetsAgents::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('import:fetch-json --sync')
            ->daily()
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground()
            ->description('Fetch a JSON payload from the remote endpoint for import.');

        $schedule->command('import:agents')
            ->daily()
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground()
            ->description('Synchronise CRM agents from the remote endpoint.');

        $schedule->command('import:google-sheets-agents')
            ->weeklyOn(3, '14:00') // Wednesday at 14:00 UTC (18:00 UAE)
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground()
            ->description('Synchronise agents from Google Sheets every Wednesday at 18:00 UAE time.');
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
