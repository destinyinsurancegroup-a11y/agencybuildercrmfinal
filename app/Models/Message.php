<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'agency_id',
        'tenant_id',
        'contact_id',
        'created_by',
        'channel',
        'direction',
        'status',
        'to_address',
        'subject',
        'body',
        'provider',
        'provider_message_id',
        'error_message',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }
}
