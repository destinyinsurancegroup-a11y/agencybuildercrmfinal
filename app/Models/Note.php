<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',   // legacy tenant field (we'll migrate to agency_id later)
        'contact_id',
        'created_by',
        'body',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Auto-assign created_by and tenant_id if not set.
     */
    protected static function booted()
    {
        static::creating(function (Note $note) {
            if (auth()->check()) {
                if (! $note->created_by) {
                    $note->created_by = auth()->id();
                }

                // Keep legacy tenant_id behavior until we fully move to agency_id
                if (is_null($note->tenant_id) && property_exists(auth()->user(), 'tenant_id')) {
                    $note->tenant_id = auth()->user()->tenant_id;
                }
            }
        });
    }

    /**
     * Note belongs to a contact.
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * User who wrote the note (canonical).
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Backwards-compat alias for templates expecting $note->user.
     */
    public function user()
    {
        return $this->author();
    }
}
