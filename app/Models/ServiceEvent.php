<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\TenantScoped;

class ServiceEvent extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'contact_id',
        'event_date',
        'event_type',
        'notes',
        'agency_id',   // multi-tenant scoping field
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
