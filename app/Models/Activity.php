<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\TenantScoped;

class Activity extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'user_id',
        'tenant_id',           // legacy; will be retired in Phase 2
        'agency_id',           // NEW: multi-tenant scoping field

        'leads_worked',
        'calls',
        'stops',
        'presentations',
        'apps_written',
        'premium_collected',
        'ap',

        'created_at',          // you are manually setting this
    ];

    public $timestamps = true;
}
