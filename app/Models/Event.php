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
    ];

    protected $casts = [
        'start' => 'datetime',
        'end'   => 'datetime',
        'reminder' => 'integer',
    ];

    // Auto-assign tenant_id and created_by
    protected static function booted()
    {
        static::creating(function ($event) {
            if (auth()->check()) {
                $event->tenant_id = auth()->user()->tenant_id ?? null;
                $event->created_by = auth()->id();
            }
        });
    }

    // Relationships (optional but correct)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
