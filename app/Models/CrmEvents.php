<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmEvent extends Model
{
    protected $table = 'crm_events';

    protected $fillable = [
        'title',
        'start',
        'end',
        'color'
    ];

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
    ];
}
