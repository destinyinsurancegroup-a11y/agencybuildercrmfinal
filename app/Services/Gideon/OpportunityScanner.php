<?php

namespace App\Services\Gideon;

use App\Models\GideonOpportunity;
use App\Models\Lead;
use Illuminate\Support\Facades\Schema;

/**
 * OpportunityScanner
 *
 * Tier 1 service that will:
 * - Read CRM data (starting with Leads)
 * - Create scored opportunities into gideon_opportunities
 *
 * FIRST REAL RULE:
 *   "Revive old leads" – any lead (contact_type = 'Lead')
 *   not touched in 30+ days.
 */
class OpportunityScanner
{
    /**
     * Run opportunity scan for the given user.
     *
     * @param  \App\Models\User  $user
     * @return array{created:int,message:string}
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

        $createdCount = 0;
        $messages     = [];

        // ---------------------------------------------------------------------
        // RULE #1: REVIVE OLD LEADS
        // ---------------------------------------------------------------------
        if (class_exists(Lead::class) && Schema::hasTable('contacts')) {
            [$count, $msg] = $this->reviveOldLeads($user, $agencyId);
            $createdCount += $count;
            $messages[]    = $msg;
        } else {
            $messages[] = 'Lead model or contacts table not found; revive_lead rule skipped.';
        }

        return [
            'created' => $createdCount,
            'message' => trim(implode(' ', array_filter($messages))),
        ];
    }

    // =====================================================================
    // INTERNAL RULE IMPLEMENTATION
    // =====================================================================

    /**
     * Revive old leads using the Lead model (which is a scoped Contact).
     *
     * @param  \App\Models\User  $user
     * @param  int               $agencyId
     * @return array{0:int,1:string}  [createdCount, message]
     */
    protected function reviveOldLeads($user, int $agencyId): array
    {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Lead::query();

        // Extra safety: scope by agency_id if the column exists.
        if (Schema::hasColumn('contacts', 'agency_id')) {
            $query->where('agency_id', $agencyId);
        }

        $threshold = now()->subDays(30);

        // Prefer updated_at if present; otherwise fall back to created_at.
        if (Schema::hasColumn('contacts', 'updated_at')) {
            $query->where('updated_at', '<=', $threshold);
        } elseif (Schema::hasColumn('contacts', 'created_at')) {
            $query->where('created_at', '<=', $threshold);
        }

        // Safety limit while we’re early in development.
        $leads        = $query->limit(200)->get();
        $createdCount = 0;

        foreach ($leads as $lead) {
            // Build a friendly display name.
            $name = $this->guessLeadName($lead);

            $lastTouched = $lead->updated_at ?? $lead->created_at ?? null;

            $title = "Revive old lead: {$name}";
            $shortReason = 'This lead has not been touched in over 30 days. Review and consider re-engaging.';
            $recommendedAction = 'Call or text this lead with a fresh angle, updated offer, or follow-up message.';

            $opp = GideonOpportunity::updateOrCreate(
                [
                    'agency_id'   => $agencyId,
                    'entity_type' => 'lead',
                    'entity_id'   => $lead->id,
                    'category'    => 'revive_lead',
                ],
                [
                    'user_id'            => $user->id,
                    'title'              => $title,
                    'short_reason'       => $shortReason,
                    'recommended_action' => $recommendedAction,
                    'score'              => 60, // simple fixed score for now
                    'status'             => 'open',
                    'source_snapshot'    => [
                        'contact_id'   => $lead->id,
                        'full_name'    => $this->guessLeadName($lead),
                        'status'       => $lead->status ?? null,
                        'source'       => $lead->source ?? null,
                        'last_touched' => $lastTouched ? $lastTouched->toDateTimeString() : null,
                    ],
                ]
            );

            if ($opp) {
                $createdCount++;
            }
        }

        return [
            $createdCount,
            "Revive_lead rule executed for {$leads->count()} stale leads.",
        ];
    }

    /**
     * Best-effort name builder for a lead/contact.
     */
    protected function guessLeadName($lead): string
    {
        if (isset($lead->full_name) && $lead->full_name) {
            return $lead->full_name;
        }

        $first = $lead->first_name ?? '';
        $last  = $lead->last_name  ?? '';

        $name = trim($first . ' ' . $last);

        if ($name !== '') {
            return $name;
        }

        return 'Unknown lead';
    }
}
