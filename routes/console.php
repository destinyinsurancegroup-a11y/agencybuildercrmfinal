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
