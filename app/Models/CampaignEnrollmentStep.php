<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignEnrollmentStep extends Model
{
    protected $fillable = [
        'agency_id','tenant_id','campaign_enrollment_id','drip_step_id',
        'scheduled_for','status','message_id','queued_at','sent_at','skipped_at',
        'skip_reason','attempts','last_error'
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'queued_at'     => 'datetime',
        'sent_at'       => 'datetime',
        'skipped_at'    => 'datetime',
        'attempts'      => 'integer',
        'agency_id'     => 'integer',
        'tenant_id'     => 'integer',
        'message_id'    => 'integer',
        'campaign_enrollment_id' => 'integer',
        'drip_step_id'  => 'integer',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(CampaignEnrollment::class, 'campaign_enrollment_id');
    }

    public function dripStep(): BelongsTo
    {
        return $this->belongsTo(DripStep::class, 'drip_step_id');
    }

    public function markSkipped(string $reason): void
    {
        $this->status = 'skipped';
        $this->skipped_at = now();
        $this->skip_reason = $reason;

        // Normalize state for clarity/audit
        $this->message_id = null;
        $this->last_error = null;
        // Optional: clear queued_at if we never actually queued a message row
        // (If you prefer to keep "claimed time", comment this out.)
        $this->queued_at = null;

        $this->save();
    }

    public function markFailed(string $error): void
    {
        $this->status = 'failed';
        $this->last_error = mb_substr($error, 0, 2000);
        $this->save();
    }
}
