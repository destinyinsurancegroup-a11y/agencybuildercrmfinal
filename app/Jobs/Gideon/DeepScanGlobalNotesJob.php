<?php

namespace App\Jobs\Gideon;

use App\Services\Gideon\GlobalNoteScanner\GlobalNoteScannerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class DeepScanGlobalNotesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param string|null $tenantId If set, only scan one tenant.
     * @param Carbon|null $since If set, only scan notes changed since this time.
     */
    public function __construct(
        public readonly ?string $tenantId = null,
        public readonly ?Carbon $since = null
    ) {
        $this->onQueue('gideon');
    }

    public function handle(GlobalNoteScannerService $scanner): void
    {
        $scanner->runDeepScan(
            tenantId: $this->tenantId,
            since: $this->since
        );
    }
}
