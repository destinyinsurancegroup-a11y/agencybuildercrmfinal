<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\TenantScoped;

class ContactRelation extends Model
{
    use HasFactory, TenantScoped;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'agency_id',    // multi-tenant scoping field
        'contact_id',
        'type',
        'name',
        'relationship',
        'phone',
        'contacted',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'contacted' => 'boolean',
    ];

    /**
     * The primary contact this relation belongs to.
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * The agency (tenant) that owns this relation record.
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}
