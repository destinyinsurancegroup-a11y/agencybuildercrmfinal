<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'event_date',
        'event_type',
        'notes',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
