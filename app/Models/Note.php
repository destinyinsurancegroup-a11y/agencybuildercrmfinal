<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'created_by',
        'tenant_id',
        'note',
    ];

    /**
     * Automatically enforce tenant assignment and created_by assignment.
     */
    protected static function booted()
    {
        static::creating(function (Note $note) {
            // Auto-assign tenant_id from logged-in user
            if (auth()->check()) {
                $note->tenant_id = auth()->user()->tenant_id;
                $note->created_by = auth()->id();
            }
        });
    }

    /**
     * Relationship: each note belongs to a single contact.
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Relationship: user who wrote the note.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Safety: Only allow querying notes belonging to the authenticated tenant.
     */
    public function scopeForCurrentTenant($query)
    {
        if (auth()->check()) {
            return $query->where('tenant_id', auth()->user()->tenant_id);
        }

        return $query;
    }
}
