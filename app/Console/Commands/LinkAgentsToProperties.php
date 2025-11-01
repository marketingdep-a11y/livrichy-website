<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Statamic\Facades\Entry;

class LinkAgentsToProperties extends Command
{
    protected $signature = 'agents:link-to-properties {--dry-run : Show what would be linked without saving}';

    protected $description = 'Link existing properties to agents by external_agent_id or agent name';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $properties = Entry::query()
            ->where('collection', 'properties')
            ->get();

        $linked = 0;
        $skipped = 0;
        $notFound = 0;

        foreach ($properties as $property) {
            // Skip if already has agent
            if ($property->get('agent')) {
                $skipped++;
                continue;
            }

            $agentId = null;
            $matchedBy = null;

            // Try external_agent_id first
            $externalAgentId = $property->get('external_agent_id');
            if ($externalAgentId) {
                $agent = Entry::query()
                    ->where('collection', 'agents')
                    ->where('data->external_id', $externalAgentId)
                    ->first();

                if ($agent) {
                    $agentId = $agent->id();
                    $matchedBy = "external_id: {$externalAgentId}";
                }
            }

            if (!$agentId) {
                $notFound++;
                continue;
            }

            if ($isDryRun) {
                $this->line("Would link: {$property->get('title')} -> Agent (matched by {$matchedBy})");
            } else {
                $property->set('agent', $agentId);
                $property->save();
                
                $this->info("Linked: {$property->get('title')} -> Agent (matched by {$matchedBy})");
            }

            $linked++;
        }

        $this->newLine();
        $this->info("Summary:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total properties', $properties->count()],
                ['Linked', $linked],
                ['Already had agent', $skipped],
                ['Agent not found', $notFound],
            ]
        );

        if ($isDryRun) {
            $this->newLine();
            $this->comment('This was a dry run. Use without --dry-run to save changes.');
        }

        return self::SUCCESS;
    }
}

