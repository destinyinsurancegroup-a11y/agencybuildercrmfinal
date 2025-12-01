<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'created_by',
        'title',
        'start',
        'end',
        'location',
        'reminder',
        'color',
        'contact_id', // ← NEW: associate event with a specific contact/lead
    ];

    protected $casts = [
        'start'    => 'datetime',
        'end'      => 'datetime',
        'reminder' => 'integer',
        'contact_id' => 'integer', // ← NEW: ensure proper casting
    ];

    /**
     * Auto-assign tenant_id and created_by for new events.
     */
    protected static function booted()
    {
        static::creating(function ($event) {
            if (auth()->check()) {
                $event->tenant_id = auth()->user()->tenant_id ?? null;
                $event->created_by = auth()->id();
            }
        });
    }

    /**
     * User who created this event.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Contact / lead this event is associated with (for follow-ups).
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
