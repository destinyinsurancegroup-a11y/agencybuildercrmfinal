<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\TenantScoped;

class ContactRelation extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'contact_id',
        'type',
        'name',
        'relationship',
        'phone',
        'contacted',
        'tenant_id',   // legacy field, can be retired in Phase 2
        'created_by',
        'agency_id',   // multi-tenant scoping field
    ];

    protected $casts = [
        'contacted' => 'boolean',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
