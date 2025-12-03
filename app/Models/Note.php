<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// IMPORTANT: use the SAME TenantScoped trait import that your Contact/Document models use.
// Example (adjust if your path is different):
use App\Models\Traits\TenantScoped;

class Note extends Model
{
    use HasFactory;
    use TenantScoped; // applies global scope + auto-fills agency_id

    protected $fillable = [
        'agency_id',
        'contact_id',
        'created_by',
        'body',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Auto-assign created_by (agency_id is handled by TenantScoped).
     */
    protected static function booted()
    {
        static::creating(function (Note $note) {
            if (auth()->check() && ! $note->created_by) {
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
    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
