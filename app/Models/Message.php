<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected $casts = [
        'agency_id'  => 'integer',
        'tenant_id'  => 'integer',
        'contact_id' => 'integer',
        'created_by' => 'integer',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* Optional helper scopes (safe + useful later) */
    public function scopeQueued($query)
    {
        return $query->where('status', 'queued');
    }

    public function scopeSms($query)
    {
        return $query->where('channel', 'sms');
    }

    public function scopeEmail($query)
    {
        return $query->where('channel', 'email');
    }
}
