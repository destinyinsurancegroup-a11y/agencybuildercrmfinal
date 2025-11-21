<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leads_worked',
        'calls',
        'stops',
        'presentations',
        'apps_written',
        'premium_collected',
        'ap',
    ];
}
