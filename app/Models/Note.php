<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',   // legacy tenant field
        'contact_id',
        'created_by',
        'note',        // <-- actual DB column
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

                if (is_null($note->tenant_id) && isset(auth()->user()->tenant_id)) {
                    $note->tenant_id = auth()->user()->tenant_id;
                }
            }
        });
    }

    /**
     * Accessor so views can use $note->body even though DB column is "note".
     */
    public function getBodyAttribute()
    {
        return $this->note;
    }

    /**
     * Mutator so assigning $note->body updates the "note" column.
     */
    public function setBodyAttribute($value)
    {
        $this->attributes['note'] = $value;
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
