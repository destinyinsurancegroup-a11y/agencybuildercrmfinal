<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory, TenantScoped;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'agency_id',   // multi-tenant owner
        'created_by',
        'title',
        'start',
        'end',
        'color',
        'location',
        'reminder',
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
     * The agency (tenant) that owns this event.
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * The user who created this event.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
