<?php

namespace App\Services\Gideon\GlobalNoteScanner;

use Illuminate\Support\Facades\DB;

class FollowUpRepository
{
    /**
     * IMPORTANT: This is a stub.
     *
     * Replace the query with your real calendar/tasks tables.
     *
     * For now it assumes you have (or will create) a generic follow_ups table:
     * - tenant_id
     * - entity_type (lead|client|contact|service_case)
     * - entity_id
     * - scheduled_for (timestamp)
     * - status (scheduled|confirmed|cancelled|done)
     */
    public function hasFutureFollowUp(string $tenantId, string $entityType, int $entityId): bool
    {
        // If you don't have this table yet, you can temporarily return false
        // so Gideon doesn't suppress everything:
        //
        // return false;

        return DB::table('follow_ups')
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('scheduled_for', '>', now())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->exists();
    }
}
