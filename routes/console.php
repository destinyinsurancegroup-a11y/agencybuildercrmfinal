<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


/**
 * Gideon: Global Note Deep Scan
 */
Artisan::command('gideon:deep-scan:global-notes {--agency=} {--since=}', function () {
    $agency = $this->option('agency');
    $since  = $this->option('since');

    $this->call(
        \App\Console\Commands\GideonDeepScanGlobalNotesCommand::class,
        [
            '--agency' => $agency,
            '--since'  => $since,
        ]
    );
})->purpose('Run Gideon global note deep scan');


/**
 * Gideon: P2 Policy Review Scan (6-month review due)
 */
Artisan::command('gideon:scan:policy-review {--agency=} {--dry-run}', function () {
    $agency = $this->option('agency');
    $dryRun = (bool) $this->option('dry-run');

    $this->call(
        \App\Console\Commands\GideonScanPolicyReviewsDueCommand::class,
        [
            '--agency'  => $agency,
            '--dry-run' => $dryRun,
        ]
    );
})->purpose('Run Gideon scan for 6-month policy reviews due (P2).');
