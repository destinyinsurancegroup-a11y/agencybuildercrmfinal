<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\TenantScoped;

class CrmEvent extends Model
{
    use HasFactory, TenantScoped;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'crm_events';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'agency_id',   // multi-tenant scoping field
        'title',
        'start',
        'end',
        'color',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start' => 'datetime',
        'end'   => 'datetime',
    ];

    /**
     * The agency (tenant) that owns this CRM event.
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}
