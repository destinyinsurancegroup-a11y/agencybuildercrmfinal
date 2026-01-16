<?php

namespace App\Console\Commands;

use App\Jobs\QueueDripEnrollmentStep;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DripsDispatchDueSteps extends Command
{
    protected $signature = 'drips:dispatch-due-steps {--limit=300}';
    protected $description = 'Dispatch jobs for due drip enrollment steps.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $due = DB::table('campaign_enrollment_steps as ces')
            ->join('campaign_enrollments as ce', 'ce.id', '=', 'ces.campaign_enrollment_id')
            ->join('drip_campaigns as dc', 'dc.id', '=', 'ce.drip_campaign_id')
            ->where('ces.status', '=', 'scheduled')
            ->where('ces.scheduled_for', '<=', now())
            ->where('ce.status', '=', 'active')
            ->where('dc.status', '=', 'active')
            ->orderBy('ces.scheduled_for')
            ->limit($limit)
            ->select('ces.id')
            ->get();

        foreach ($due as $row) {
            QueueDripEnrollmentStep::dispatch((int) $row->id)->onQueue('messages');
        }

        $this->info('Dispatched '.$due->count().' due drip steps.');
        return self::SUCCESS;
    }
}
