<?php

namespace App\Console\Commands;

use App\Services\AgentImport\AgentImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncCrmAgents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:agents {--url= : Override the CRM endpoint URL}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise active CRM agents into Statamic.';

    public function __construct(private readonly AgentImporter $importer)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $url = $this->option('url') ?? config('services.crm_agents.url');

        if (blank($url)) {
            $this->error('CRM agents endpoint URL is not configured. Set services.crm_agents.url or provide the --url option.');

            return self::FAILURE;
        }

        try {
            $report = $this->importer->sync($url);
        } catch (Throwable $exception) {
            Log::error('Unable to synchronise CRM agents.', [
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);

            $this->error('Agent sync failed. Check the logs for details.');

            return self::FAILURE;
        }

        $this->info('Agent sync completed.');

        $this->table(
            ['Metric', 'Value'],
            collect($report)->map(fn ($value, $key) => [ucwords(str_replace('_', ' ', $key)), $value])->all()
        );

        return self::SUCCESS;
    }
}
