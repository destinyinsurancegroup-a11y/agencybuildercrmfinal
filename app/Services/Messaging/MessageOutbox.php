<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\DB;

final class MessageOutbox
{
    /**
     * Inserts into your existing `messages` table.
     * NO provider/sending code here.
     */
    public function queue(array $payload): int
    {
        return (int) DB::table('messages')->insertGetId([
            'agency_id'   => $payload['agency_id'] ?? null,
            'tenant_id'   => $payload['tenant_id'] ?? null,

            'contact_id'  => $payload['contact_id'],
            'created_by'  => $payload['created_by'] ?? null,

            'campaign_enrollment_step_id' => $payload['campaign_enrollment_step_id'] ?? null,

            'channel'     => $payload['channel'],          // sms|email
            'direction'   => 'outbound',
            'status'      => 'queued',

            'to_address'  => $payload['to_address'],
            'subject'     => $payload['subject'] ?? null,
            'body'        => $payload['body'],

            // Provider fields intentionally null
            'provider'            => null,
            'provider_message_id' => null,
            'error_message'       => null,

            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
