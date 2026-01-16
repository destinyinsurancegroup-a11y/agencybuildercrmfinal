<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DripCampaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agency_id','tenant_id','name','type','status','channel_mode','description','created_by'
    ];

    public function steps()
    {
        return $this->hasMany(DripStep::class, 'drip_campaign_id');
    }
}
