<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GideonOpportunity extends Model
{
    use HasFactory;

    protected $table = 'gideon_opportunities';

    protected $fillable = [
        'agency_id',
        'user_id',
        'entity_type',
        'entity_id',
        'category',
        'title',
        'short_reason',
        'recommended_action',
        'score',
        'status',
        'outcome',
        'outcome_reason',
        'resolved_at',
        'source_snapshot',
    ];

    protected $casts = [
        'score'           => 'integer',
        'source_snapshot' => 'array',
        'resolved_at'     => 'datetime',
    ];
}
