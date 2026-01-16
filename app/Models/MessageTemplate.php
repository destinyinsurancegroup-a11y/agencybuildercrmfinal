<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'message_templates';

    protected $fillable = [
        'agency_id',
        'tenant_id',
        'channel',
        'name',
        'subject',
        'body',
        'variables_json',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'variables_json' => 'array',
        'is_active' => 'boolean',
    ];
}
