<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GideonSparringMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'agency_id',
        'user_id',
        'sender',
        'content',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
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
