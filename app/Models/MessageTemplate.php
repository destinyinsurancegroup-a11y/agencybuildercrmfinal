<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agency_id','tenant_id','channel','name','subject','body',
        'is_active','variables_json','created_by','updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'variables_json' => 'array',
    ];
}
