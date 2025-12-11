<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GideonSparringSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'agency_id',
        'user_id',
        'mode',
        'persona_key',
        'config',
        'state',      // 👈 new: store emotional / scenario state as JSON
        'status',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'config'     => 'array',
        'state'      => 'array',    // 👈 new: automatically cast to array
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(GideonSparringMessage::class, 'session_id');
    }

    public function assessment()
    {
        return $this->hasOne(GideonSparringAssessment::class, 'session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}
