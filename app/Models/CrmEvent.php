<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CrmEvent extends Model
{
    use HasFactory, TenantScoped;

    protected $table = 'crm_events';

    protected $fillable = [
        'title',
        'start',
        'end',
        'color',
        'agency_id',   // NEW: multi-tenant scoping field
    ];

    protected $casts = [
        'start' => 'datetime',
        'end'   => 'datetime',
    ];
}
