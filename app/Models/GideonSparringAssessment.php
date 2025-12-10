<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GideonSparringAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'agency_id',
        'user_id',
        'scores',
        'strengths',
        'improvements',
        'meta',
    ];

    protected $casts = [
        'scores' => 'array',
        'meta'   => 'array',
    ];

    public function session()
    {
        return $this->belongsTo(GideonSparringSession::class, 'session_id');
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
