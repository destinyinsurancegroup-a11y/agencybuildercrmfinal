<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\TenantScoped;

class ContactPolicy extends Model
{
    use HasFactory, TenantScoped;

    protected $table = 'contact_policies';

    protected $fillable = [
        'agency_id',
        'contact_id',
        'carrier',
        'policy_type',
        'face_amount',
        'premium_amount',
        'policy_issue_date',
        'premium_due_date',
        'premium_due_text',
    ];

    protected $casts = [
        'policy_issue_date' => 'date',
        'premium_due_date'  => 'date',
        'face_amount'       => 'decimal:2',
        'premium_amount'    => 'decimal:2',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
