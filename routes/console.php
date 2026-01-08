<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

/**
 * Default Laravel example command
 */
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


/**
 * Gideon: Global Note Deep Scan
 *
 * Dispatches the background job that scans all notes
 * for missed or forgotten opportunities.
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
 * Gideon: Leads Needing Disposition (P1)
 *
 * Scans Leads (contacts.contact_type = "Lead") that are 14+ days old
 * and still in New/blank status (i.e., no disposition set).
 *
 * Dispositions:
 * - Sold (final) -> excluded
 * - Not Interested (final) -> excluded
 * - Follow Up (non-final but explicit) -> excluded from "needs disposition"
 */
Artisan::command('gideon:scan:lead-disposition {--agency=} {--dry-run}', function () {
    $agency = $this->option('agency');
    $dryRun = (bool) $this->option('dry-run');

    $this->call(
        \App\Console\Commands\GideonScanLeadsNeedingDispositionCommand::class,
        [
            '--agency' => $agency,
            '--dry-run' => $dryRun,
        ]
    );
})->purpose('Run Gideon lead disposition scan (14+ days still New/blank)');
