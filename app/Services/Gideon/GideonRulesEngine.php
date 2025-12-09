<?php

namespace App\Services\Gideon;

/**
 * GideonRulesEngine
 *
 * This class contains the "if this, then that" RULES that decide
 * where potential opportunities might be hiding in the data.
 *
 * IMPORTANT:
 * - This class DOES NOT talk to the database.
 * - It just takes in structured data (arrays) and returns opportunity "candidates".
 * - GideonOpportunityEngine will handle loading data & saving to DB.
 */
class GideonRulesEngine
{
    /**
     * Evaluate all rules against the given context.
     *
     * @param  array  $context  An array of data about a client/household/etc.
     *                          Example shape (we will refine this later):
     *                          [
     *                              'contact'   => [...],
     *                              'policies'  => [...],
     *                              'leads'     => [...],
     *                              'service'   => [...],
     *                              'notes'     => [...],
     *                              'tags'      => [...],
     *                              'household' => [...],
     *                          ]
     *
     * @return array  List of opportunity candidates.
     *                Each item is an array like:
     *                [
     *                    'category'           => 'cross_sell',
     *                    'score'              => 70,
     *                    'title'              => 'Cross-sell final expense coverage',
     *                    'short_reason'       => 'Client has Medicare but no FE policy.',
     *                    'recommended_action' => 'Call client to discuss FE for burial costs.',
     *                ]
     */
    public function evaluate(array $context): array
    {
        $opportunities = [];

        // Each of these methods returns an array of opportunities (or empty array).
        $opportunities = array_merge($opportunities, $this->beneficiaryOpportunities($context));
        $opportunities = array_merge($opportunities, $this->crossSellOpportunities($context));
        $opportunities = array_merge($opportunities, $this->lapseRiskOpportunities($context));
        $opportunities = array_merge($opportunities, $this->renewalOpportunities($context));
        $opportunities = array_merge($opportunities, $this->householdOpportunities($context));
        $opportunities = array_merge($opportunities, $this->referralPartnerOpportunities($context));
        $opportunities = array_merge($opportunities, $this->reviveLeadOpportunities($context));

        return $opportunities;
    }

    /**
     * Beneficiary / beneficiary update opportunities.
     *
     * Example (future logic):
     * - Policy has no beneficiary or contingent beneficiary
     * - Notes mention marriage/divorce/new baby but beneficiary not updated
     */
    protected function beneficiaryOpportunities(array $context): array
    {
        // TODO: implement real rules using $context['policies'], $context['notes'], etc.
        return [];
    }

    /**
     * Cross-sell opportunities.
     *
     * Example (future logic):
     * - Client has Medicare policy, but no FE policy
     * - Household has one spouse insured, the other not insured
     */
    protected function crossSellOpportunities(array $context): array
    {
        // TODO: implement real rules using policies, household, tags, etc.
        return [];
    }

    /**
     * Lapse / save-the-business opportunities.
     *
     * Example (future logic):
     * - Service notes mention payment trouble
     * - Policy is close to lapse date
     */
    protected function lapseRiskOpportunities(array $context): array
    {
        // TODO: implement real rules based on policy status + service records.
        return [];
    }

    /**
     * Renewal / expiration opportunities.
     *
     * Example (future logic):
     * - Term policy approaching end-of-term
     * - Annual review due
     */
    protected function renewalOpportunities(array $context): array
    {
        // TODO: implement renewal rules based on policy dates.
        return [];
    }

    /**
     * Household-based opportunities.
     *
     * Example (future logic):
     * - Multiple adults in household but only one policy
     * - Children reaching certain ages
     */
    protected function householdOpportunities(array $context): array
    {
        // TODO: implement household rules (spouse, children, etc.).
        return [];
    }

    /**
     * Referral / partner opportunities (Funeral Directors, Pastors, CPAs, etc.).
     *
     * Example (future logic):
     * - Tagged contacts with no recent activity
     * - Partners that used to send referrals but stopped
     */
    protected function referralPartnerOpportunities(array $context): array
    {
        // TODO: implement rules using tags + custom tabs + notes.
        return [];
    }

    /**
     * Revive old leads / win-back opportunities.
     *
     * Example (future logic):
     * - Lead archived as "too expensive" a long time ago
     * - Lead said "call me after X" and X is in the past now
     */
    protected function reviveLeadOpportunities(array $context): array
    {
        // TODO: implement rules using leads + notes + dispositions.
        return [];
    }
}
