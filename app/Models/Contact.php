<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Missing before — now added:
use App\Models\User;

use App\Models\Note;
use App\Models\ContactRelation;   // Destiny unified relations table
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

        // Old basic notes column
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
     * Auto-compile full_name before saving to DB.
     */
    protected static function booted(): void
    {
        static::saving(function (Contact $contact) {
            $contact->full_name = trim($contact->first_name . ' ' . $contact->last_name);
        });
    }

    /**
     * -- Multi-tenant scope
     */
    public function scopeForCurrentTenant($query)
    {
        $tenantId = auth()->user()?->tenant_id;
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Creator + Assigned agent
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * FIX: custom notes relationship (because 'notes' is a column)
     */
    public function allNotes()
    {
        return $this->hasMany(Note::class, 'contact_id')->latest();
    }

    /* ============================================================
     |  DESTINY CRM – UNIFIED RELATION SYSTEM
     |  contact_relations table structure:
     |  id, contact_id, type, name, relationship, phone,
     |  contacted, tenant_id, created_by, timestamps
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
     |  SERVICE EVENTS
     * ============================================================ */
    public function serviceEvents()
    {
        return $this->hasMany(ServiceEvent::class)
                    ->orderBy('event_date', 'desc');
    }

    /**
     * Calculated age based on date_of_birth
     */
    public function getAgeAttribute()
    {
        return $this->date_of_birth
            ? $this->date_of_birth->age
            : null;
    }
}
