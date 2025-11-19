<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'notes', // legacy column, still safe to keep
    ];

    protected $casts = [
        'tags'             => 'array',
        'date_of_birth'    => 'date',
        'premium_due_date' => 'date',
        'face_amount'      => 'decimal:2',
        'premium_amount'   => 'decimal:2',
    ];

    /**
     * Automatically keep full_name synced with first + last name
     */
    public static function booted(): void
    {
        static::saving(function (Contact $contact) {
            $contact->full_name = trim($contact->first_name . ' ' . $contact->last_name);
        });
    }

    /**
     * Multi-tenant scope for safety
     */
    public function scopeForCurrentTenant(Builder $query): Builder
    {
        $tenantId = auth()->user()?->tenant_id;

        return $query->where('tenant_id', $tenantId);
    }

    /**
     * User who created this contact
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User assigned to this contact
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Relationship: historical notes for this contact
     * Uses the correct Notes model and orders newest-first.
     */
    public function notes()
    {
        return $this->hasMany(Note::class)->latest();
    }
}
