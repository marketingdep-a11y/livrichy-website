<?php

namespace App\Console\Commands;

use App\Services\AgentImport\GoogleSheetsAgentImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncGoogleSheetsAgents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:google-sheets-agents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise agents from Google Sheets into Statamic.';

    public function __construct(private readonly GoogleSheetsAgentImporter $importer)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting Google Sheets agents synchronization...');

        try {
            $report = $this->importer->sync();
        } catch (Throwable $exception) {
            Log::error('Unable to synchronise Google Sheets agents.', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $this->error('Agent sync failed: '.$exception->getMessage());
            $this->error('Check the logs for more details.');

            return self::FAILURE;
        }

        $this->info('Agent sync completed successfully!');
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            collect($report)->map(fn ($value, $key) => [ucwords(str_replace('_', ' ', $key)), $value])->all()
        );

        return self::SUCCESS;
    }
}

