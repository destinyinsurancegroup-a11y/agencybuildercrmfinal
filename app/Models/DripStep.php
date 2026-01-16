<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DripStep extends Model
{
    protected $fillable = [
        'agency_id','tenant_id','drip_campaign_id','template_id',
        'step_order','delay_days','holiday_mmdd','send_time_local','is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function campaign()
    {
        return $this->belongsTo(DripCampaign::class, 'drip_campaign_id');
    }

    public function template()
    {
        return $this->belongsTo(MessageTemplate::class, 'template_id');
    }
}
