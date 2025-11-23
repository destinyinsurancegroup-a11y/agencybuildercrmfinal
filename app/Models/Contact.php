<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Note;
use App\Models\ContactRelation;   // ⭐ NEW: Destiny unified relations
use App\Models\ServiceEvent;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'created_by',
        'assigned_to',

        'first_name',
        'last_name',
        'full_name',
        'email',
        'phone',

        'contact_type',
        'status',
        'source',
        'tags',

        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',

        'date_of_birth',

        // POLICY FIELDS
        'policy_type',
        'face_amount',
        'premium_amount',
        'premium_due_date',
        'premium_due_text',
        'policy_issue_date',
        'anniversary',

        // OLD notes column (separate from Note model)
        'notes',

        // BOOK / SERVICE FIELDS
        'carrier',
    ];

    protected $casts = [
        'tags'               => 'array',
        'date_of_birth'      => 'date',
        'premium_due_date'   => 'date',
        'policy_issue_date'  => 'date',
        'anniversary'        => 'date',

        'face_amount'        => 'decimal:2',
        'premium_amount'     => 'decimal:2',
    ];

    /**
     * Auto-build full_name before saving.
     */
    public static function booted(): void
    {
        static::saving(function (Contact $contact) {
            $contact->full_name = trim($contact->first_name . ' ' . $contact->last_name);
        });
    }

    /**
     * Scope to current tenant.
     */
    public function scopeForCurrentTenant($query)
    {
        $tenantId = auth()->user()?->tenant_id;
        return $query->where('tenant_id', $tenantId);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * FIXED: avoids conflict with "notes" column.
     */
    public function allNotes()
    {
        return $this->hasMany(Note::class, 'contact_id')->latest();
    }

    /* ============================================================
     |  DESTINY RELATION SYSTEM (Unified Table)
     |  ------------------------------------------------------------
     |  contact_relations table fields:
     |  - id
     |  - contact_id
     |  - type = 'beneficiary' or 'emergency'
     |  - name
     |  - relationship
     |  - phone
     |  - contacted
     |  - tenant_id, created_by, timestamps
     * ============================================================ */

    public function relations()
    {
        return $this->hasMany(ContactRelation::class);
    }

    public function beneficiaries()
    {
        return $this->relations()->where('type', 'beneficiary');
    }

    public function emergencyContacts()
    {
        return $this->relations()->where('type', 'emergency');
    }

    /* ============================================================
     |  SERVICE EVENTS (you already had this)
     * ============================================================ */
    public function serviceEvents()
    {
        return $this->hasMany(ServiceEvent::class)->orderBy('event_date', 'desc');
    }

    /**
     * Helpful age accessor.
     */
    public function getAgeAttribute()
    {
        return $this->date_of_birth
            ? $this->date_of_birth->age
            : null;
    }
}
