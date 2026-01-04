<?php

namespace App\Services\Gideon\GlobalNoteScanner;

use Illuminate\Support\Facades\DB;

class FollowUpRepository
{
    /**
     * Check if there is already a future follow-up scheduled
     * for this entity. If yes, Gideon stays quiet.
     *
     * NOTE: This is intentionally a stub for this phase.
     * It will be wired to the real calendar/events table later.
     *
     * Expected eventual fields (example):
     * - agency_id
     * - entity_type (lead|contact|policy|service|etc)
     * - entity_id
     * - scheduled_for (timestamp)
     * - status (scheduled|confirmed|cancelled|done)
     */
    public function hasFutureFollowUp(int $agencyId, string $entityType, int $entityId): bool
    {
        // TEMPORARY SAFE DEFAULT:
        // Until calendar wiring is done, always return false
        // so Gideon does not suppress legitimate opportunities.
        //
        // When ready, remove the line below and wire to real table.
        return false;

        /*
        return DB::table('follow_ups')
            ->where('agency_id', $agencyId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('scheduled_for', '>', now())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->exists();
        */
    }
}
