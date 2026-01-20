<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DripCampaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agency_id',
        'tenant_id',
        'name',
        'type',        // onboarding_timeline|date_birthday|date_client_anniversary|date_holiday
        'status',      // active|paused|archived
        'channel_mode',// sms|email|mixed (MVP can ignore as constraint)
        'description',
        'created_by',
    ];

    protected $casts = [
        'agency_id'   => 'integer',
        'tenant_id'   => 'integer',
        'created_by'  => 'integer',
    ];

    public function steps(): HasMany
    {
        // Always return steps in intended order
        return $this->hasMany(DripStep::class, 'drip_campaign_id')
            ->orderBy('step_order');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
