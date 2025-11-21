<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Note;
use App\Models\Beneficiary;
use App\Models\EmergencyContact;

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
        'policy_type',
        'face_amount',
        'premium_amount',
        'premium_due_date',
        'notes',

        // NEW BOOK FIELDS
        'carrier',
        'anniversary',
        'policy_issue_date',
        'premium_due_text',
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

    public static function booted(): void
    {
        static::saving(function (Contact $contact) {
            $contact->full_name = trim($contact->first_name . ' ' . $contact->last_name);
        });
    }

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

    /* =======================================================
     * NOTES RELATIONSHIP (CORRECTED)
     * Loads ALL notes ever made for this client
     * Ordered newest → oldest
     * ======================================================= */
    public function notes()
    {
        return $this->hasMany(Note::class, 'contact_id')->orderBy('created_at', 'desc');
    }

    /* =======================================================
     * BENEFICIARIES RELATIONSHIP
     * ======================================================= */
    public function beneficiaries()
    {
        return $this->hasMany(Beneficiary::class);
    }

    /* =======================================================
     * EMERGENCY CONTACTS RELATIONSHIP
     * ======================================================= */
    public function emergencyContacts()
    {
        return $this->hasMany(EmergencyContact::class);
    }

    /* =======================================================
     * AUTO-CALCULATED AGE
     * ======================================================= */
    public function getAgeAttribute()
    {
        return $this->date_of_birth
            ? $this->date_of_birth->age
            : null;
    }
}
