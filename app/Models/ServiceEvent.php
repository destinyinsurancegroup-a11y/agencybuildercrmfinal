<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\TenantScoped;

class ServiceEvent extends Model
{
    use HasFactory, TenantScoped;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'agency_id',   // multi-tenant scoping field
        'contact_id',
        'event_date',
        'event_type',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'event_date' => 'datetime',
    ];

    /**
     * The contact this service event is associated with.
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * The agency (tenant) that owns this service event.
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}
