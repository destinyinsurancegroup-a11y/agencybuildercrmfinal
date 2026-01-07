<?php

namespace App\Console\Commands;

use App\Jobs\Gideon\DeepScanGlobalNotesJob;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GideonDeepScanGlobalNotesCommand extends Command
{
    protected $signature = 'gideon:deep-scan:global-notes {--agency=} {--since=}';
    protected $description = 'Dispatch Gideon Global Note Deep Scan (queue job).';

    public function handle(): int
    {
        $agencyOpt = $this->option('agency');
        $sinceOpt  = $this->option('since');

        $agencyId = $agencyOpt !== null && $agencyOpt !== '' ? (int) $agencyOpt : null;
        $since = $sinceOpt ? Carbon::parse($sinceOpt) : null;

        DeepScanGlobalNotesJob::dispatch($agencyId, $since);

        $this->info('Dispatched DeepScanGlobalNotesJob' . ($agencyId ? " for agency {$agencyId}" : ''));

        return self::SUCCESS;
    }
}
