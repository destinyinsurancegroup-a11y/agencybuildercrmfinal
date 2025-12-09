<?php

namespace App\Services\Gideon;

use App\Models\GideonOpportunity;

/**
 * OpportunityScanner
 *
 * Tier 1 skeleton service that will eventually:
 * - Read CRM data (Book of Business, Leads, Service, Notes, etc.)
 * - Build context objects for each client/household
 * - Call GideonRulesEngine / GideonOpportunityEngine
 * - Write scored opportunities into gideon_opportunities
 *
 * Right now, this is a SAFE PLACEHOLDER that simply creates
 * a single test opportunity for the current user's agency.
 */
class OpportunityScanner
{
    /**
     * Run a placeholder scan for the given user.
     *
     * @param  \App\Models\User  $user
     * @return array
     */
    public function runForUser($user): array
    {
        if (! $user) {
            return [
                'created' => 0,
                'message' => 'No authenticated user.',
            ];
        }

        $agencyId = $user->agency_id ?? null;

        if (! $agencyId) {
            return [
                'created' => 0,
                'message' => 'User has no agency_id; cannot scope opportunities.',
            ];
        }

        // In the future, this method will:
        // - Pull real data
        // - Call GideonOpportunityEngine->analyzeAll()
        // - Save many opportunities based on rules
        //
        // For now, we simply create ONE clearly-marked test row.

        $opp = GideonOpportunity::create([
            'agency_id'           => $agencyId,
            'user_id'             => $user->id,
            'entity_type'         => 'scan_placeholder',
            'entity_id'           => null,
            'category'            => 'scan_test',
            'title'               => 'Gideon scan pipeline test opportunity',
            'short_reason'        => 'This is a placeholder created by OpportunityScanner to verify the scan pipeline.',
            'recommended_action'  => 'No action needed – this confirms the Gideon opportunity scan is wired correctly.',
            'score'               => 10,
            'status'              => 'open',
            'source_snapshot'     => [
                'note' => 'Created by OpportunityScanner::runForUser placeholder.',
            ],
        ]);

        return [
            'created'         => 1,
            'opportunity_id'  => $opp->id,
            'message'         => 'Placeholder scan completed. Real rules will be added later.',
        ];
    }
}
