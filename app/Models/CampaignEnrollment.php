<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignEnrollment extends Model
{
    protected $fillable = [
        'agency_id','tenant_id','drip_campaign_id','contact_id',
        'status','source','enrolled_by','enrolled_at','started_at',
        'completed_at','canceled_at','cancellation_reason'
    ];

    protected $dates = ['enrolled_at','started_at','completed_at','canceled_at'];

    public function campaign()
    {
        return $this->belongsTo(DripCampaign::class, 'drip_campaign_id');
    }

    public function steps()
    {
        return $this->hasMany(CampaignEnrollmentStep::class, 'campaign_enrollment_id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }
}
