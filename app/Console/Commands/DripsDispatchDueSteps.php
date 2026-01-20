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
        $limit = max(1, (int) $this->option('limit'));
        $ids = [];

        DB::transaction(function () use ($limit, &$ids) {
            $driver = DB::getDriverName();

            $base = DB::table('campaign_enrollment_steps as ces')
                ->join('campaign_enrollments as ce', 'ce.id', '=', 'ces.campaign_enrollment_id')
                ->join('drip_campaigns as dc', 'dc.id', '=', 'ce.drip_campaign_id')
                ->where('ces.status', '=', 'scheduled')
                ->whereNull('ces.message_id')
                ->where('ces.scheduled_for', '<=', now())
                ->where('ce.status', '=', 'active')
                ->where('dc.status', '=', 'active')
                ->orderBy('ces.scheduled_for')
                ->limit($limit)
                ->select('ces.id');

            // Lock rows so multiple schedulers don't claim the same steps
            if (in_array($driver, ['mysql', 'pgsql'], true)) {
                $rows = $base->lock(DB::raw('FOR UPDATE SKIP LOCKED'))->get();
            } else {
                $rows = $base->lockForUpdate()->get();
            }

            $ids = $rows->pluck('id')->map(fn ($v) => (int) $v)->all();

            if (empty($ids)) {
                return;
            }

            // Claim immediately so they won't be selected again next minute
            DB::table('campaign_enrollment_steps')
                ->whereIn('id', $ids)
                ->update([
                    'status'     => 'queued',
                    'queued_at'  => now(),
                    'updated_at' => now(),
                ]);
        });

        foreach ($ids as $id) {
            QueueDripEnrollmentStep::dispatch($id)->onQueue('messages');
        }

        $this->info('Dispatched ' . count($ids) . ' due drip steps.');
        return self::SUCCESS;
    }
}
