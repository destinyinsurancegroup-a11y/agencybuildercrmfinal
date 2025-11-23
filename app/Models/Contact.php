<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Note;
use App\Models\ContactRelation;   // Destiny unified relations
use App\Models\ServiceEvent;
use Carbon\Carbon;

class Contact extends Model
{
    use HasFactory;

    protected $table = 'contacts';   // Explicit for safety

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

        // Legacy free-text notes column
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
     * Build full_name automatically.
     */
    protected static function booted(): void
    {
        static::saving(function (Contact $contact) {
            $contact->full_name = trim($contact->first_name . ' ' . $contact->last_name);
        });
    }

    /**
     * Scope: multi-tenant filtering.
     */
    public function scopeForCurrentTenant($query)
    {
        $tenantId = auth()->user()?->tenant_id;
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Contact creator and assigned agent.
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
     * Relationship: full Note model (separate from "notes" text field).
     */
    public function allNotes()
    {
        return $this->hasMany(Note::class, 'contact_id')->latest();
    }

    /* ============================================================
     |  DESTINY RELATION SYSTEM — Unified Table
     |  contact_relations:
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
     * Accessor: contact age.
     */
    public function getAgeAttribute()
    {
        return $this->date_of_birth
            ? $this->date_of_birth->age
            : null;
    }

    /* ============================================================
     |  UPCOMING DATE CHECKERS for Dashboard Insights
     * ============================================================ */

    /**
     * Is the contact's birthday within the next 7 days?
     */
    public function birthdayIsSoon(): bool
    {
        if (!$this->date_of_birth) {
            return false;
        }

        $dob = $this->date_of_birth->copy();
        $next = Carbon::create(now()->year, $dob->month, $dob->day);

        if ($next->isPast()) {
            $next->addYear();
        }

        return now()->diffInDays($next) <= 7;
    }

    /**
     * Is the contact's anniversary within the next 7 days?
     */
    public function anniversaryIsSoon(): bool
    {
        if (!$this->anniversary) {
            return false;
        }

        $ann = $this->anniversary->copy();
        $next = Carbon::create(now()->year, $ann->month, $ann->day);

        if ($next->isPast()) {
            $next->addYear();
        }

        return now()->diffInDays($next) <= 7;
    }
}
