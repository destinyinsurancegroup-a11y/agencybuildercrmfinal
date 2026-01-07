<?php

namespace App\Services\Gideon\Opportunities;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class GideonOpportunityQuery
{
    /**
     * Base query for actionable opportunities for the current agency.
     *
     * Actionable = open AND (not snoozed) AND (due now if due_at exists)
     */
    public static function actionableForAgency(int $agencyId): Builder
    {
        return DB::table('gideon_opportunities')
            ->where('agency_id', $agencyId)
            ->where('status', 'open')
            ->where(function ($q) {
                $q->whereNull('snoozed_until')
                  ->orWhere('snoozed_until', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('due_at')
                  ->orWhere('due_at', '<=', now());
            });
    }
}
