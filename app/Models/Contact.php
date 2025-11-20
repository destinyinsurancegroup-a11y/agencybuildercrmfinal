<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Note;
use App\Models\Document;
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

        // Policy info
        'policy_type',
        'face_amount',
        'premium_amount',
        'premium_due_date',

        // Legacy notes column (ignored by new notes system)
        'notes',
    ];

    protected $casts = [
        'tags'             => 'array',
        'date_of_birth'    => 'date',
        'premium_due_date' => 'date',
        'face_amount'      => 'decimal:2',
        'premium_amount'   => 'decimal:2',
    ];

    /**
     * Auto-generate full_name when saving
     */
    public static function booted(): void
    {
        static::saving(function (Contact $contact) {
            $contact->full_name = tri_
