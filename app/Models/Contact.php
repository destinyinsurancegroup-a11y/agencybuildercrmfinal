<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Note;
use App\Models\Message;            // ✅ ADD
use App\Models\ContactRelation;    // Destiny unified relations
use App\Models\ServiceEvent;
use App\Models\Event;              // Calendar events / follow-ups
use App\Models\Concerns\TenantScoped;
use Carbon\Carbon;

class Contact extends Model
{
    use HasFactory, TenantScoped;

    // Explicit table name for safety
    protected $table = 'contacts';

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        // Multi-tenant scoping field
        'agency_id',

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

        // Legacy naming: keep both, don't break existing DB/UI
        'policy_issue_date',

        /**
         * IMPORTANT:
         * Going forward, we will treat `anniversary` as the POLICY ANNIVERSARY DATE
         * derived from "Initial Draft Date" (or whichever policy milestone you choose).
         * We are NOT using wedding anniversaries.
         */
        'anniversary',

        // Legacy free-text notes column
        'notes',

        // BOOK / SERVICE FIELDS
        'carrier',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'tags'               => 'array',
        'date_of_birth'      => 'date',
        'premium_due_date'   => 'date',
        'policy_issue_date'  => 'date',

        // Treated as POLICY anniversary date
        'anniversary'        => 'date',

        'face_amount'        => 'decimal:2',
        'premium_amount'     => 'decimal:2',
    ];

    /**
     * Build full_name automatically before save.
     */
    protected static function booted(): void
    {
        static::saving(function (Contact $contact) {
            $contact->full_name = trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? ''));
        });
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
     * Relationship: Note records (separate from the "notes" text column).
     *
     * IMPORTANT: Because there is also a "notes" TEXT column on this model,
     * always access this relationship via the method:
     *   $contact->notes()->get()
     * not via $contact->notes (which will return the column value).
     */
    public function notes()
    {
        return $this->hasMany(Note::class, 'contact_id')->latest();
    }

    /**
     * Backwards-compatible alias for any legacy code.
     */
    public function allNotes()
    {
        return $this->notes();
    }

    /**
     * ✅ Messages thread (SMS + Email) for this contact.
     * direction: outbound/inbound
     * channel: sms/email
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'contact_id')->latest();
    }

    /* ============================================================
     |  DESTINY RELATION SYSTEM — Unified Table
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
     * Calendar events / follow-ups associated with this contact.
     */
    public function events()
    {
        return $this->hasMany(Event::class);
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

    /**
     * ✅ Policy anniversary date accessor.
     * Right now we store it in `anniversary` (MVP).
     * Later, if you move to policy table(s), only this method needs updating.
     */
    public function getPolicyAnniversaryDateAttribute()
    {
        return $this->anniversary;
    }

    /* ============================================================
     |  UPCOMING DATE CHECKERS for Dashboard Insights
     * ============================================================ */

    public function birthdayIsSoon(): bool
    {
        if (!$this->date_of_birth) return false;

        $dob = $this->date_of_birth->copy();
        $next = Carbon::create(now()->year, $dob->month, $dob->day);

        if ($next->isPast()) $next->addYear();

        return now()->diffInDays($next) <= 7;
    }

    /**
     * ✅ New explicit method (preferred):
     * This is the POLICY anniversary (Initial Draft Date).
     */
    public function policyAnniversaryIsSoon(): bool
    {
        $date = $this->policy_anniversary_date; // uses accessor above
        if (!$date) return false;

        $d = $date->copy();
        $next = Carbon::create(now()->year, $d->month, $d->day);

        if ($next->isPast()) $next->addYear();

        return now()->diffInDays($next) <= 7;
    }

    /**
     * Backwards compatibility:
     * Keep old method name so dashboard code doesn't break,
     * but it now maps to POLICY anniversary behavior.
     */
    public function anniversaryIsSoon(): bool
    {
        return $this->policyAnniversaryIsSoon();
    }
}
