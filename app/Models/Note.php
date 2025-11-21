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
        'body',     // FIXED — This must match controller + DB
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Auto-assign tenant + created_by.
     */
    protected static function booted()
    {
        static::creating(function (Note $note) {
            if (auth()->check()) {
                $note->tenant_id = auth()->user()->tenant_id;
                $note->created_by = auth()->id();
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
     * User who wrote the note.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Multi-tenant protection.
     */
    public function scopeForCurrentTenant($query)
    {
        if (auth()->check()) {
            return $query->where('tenant_id', auth()->user()->tenant_id);
        }

        return $query;
    }
}
