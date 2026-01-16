<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignEnrollmentStep extends Model
{
    protected $fillable = [
        'agency_id','tenant_id','campaign_enrollment_id','drip_step_id',
        'scheduled_for','status','message_id','queued_at','sent_at','skipped_at',
        'skip_reason','attempts','last_error'
    ];

    protected $dates = ['scheduled_for','queued_at','sent_at','skipped_at'];

    public function enrollment()
    {
        return $this->belongsTo(CampaignEnrollment::class, 'campaign_enrollment_id');
    }

    public function dripStep()
    {
        return $this->belongsTo(DripStep::class, 'drip_step_id');
    }

    public function markSkipped(string $reason): void
    {
        $this->status = 'skipped';
        $this->skipped_at = now();
        $this->skip_reason = $reason;
        $this->save();
    }
}
