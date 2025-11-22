<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tenant_id',           // ⭐ Required for multi-tenant system

        'leads_worked',
        'calls',
        'stops',
        'presentations',
        'apps_written',
        'premium_collected',
        'ap',

        // ⭐ REQUIRED because your controller sets created_at manually
        'created_at',
    ];

    // Allow Laravel to auto-manage timestamps unless you disable updated_at only
    public $timestamps = true;
}
