<?php

namespace App\Console\Commands;

use App\Jobs\Gideon\DeepScanGlobalNotesJob;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GideonDeepScanGlobalNotesCommand extends Command
{
    protected $signature = 'gideon:deep-scan:global-notes {--tenant=} {--since=}';
    protected $description = 'Dispatch Gideon Global Note Deep Scan (queue job).';

    public function handle(): int
    {
        $tenantId = $this->option('tenant') ?: null;
        $sinceOpt = $this->option('since') ?: null;

        $since = $sinceOpt ? Carbon::parse($sinceOpt) : null;

        DeepScanGlobalNotesJob::dispatch($tenantId, $since);

        $this->info('Dispatched DeepScanGlobalNotesJob' . ($tenantId ? " for tenant {$tenantId}" : ''));

        return self::SUCCESS;
    }
}
