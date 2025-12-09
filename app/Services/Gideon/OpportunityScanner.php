<?php

namespace App\Services\Gideon;

use App\Models\GideonOpportunity;
use Illuminate\Support\Facades\Schema;

/**
 * OpportunityScanner
 *
 * Tier 1 service that will:
 * - Read CRM data (Leads, Book, Service, Notes, etc.)
 * - Build context objects for each client/household
 * - Create scored opportunities into gideon_opportunities
 *
 * Right now, we attempt the FIRST REAL RULE:
 *   "Revive old leads" – any lead not touched in 30+ days.
 * If the Lead model/table do not exist, we skip that rule safely.
 */
class OpportunityScanner
{
    /**
     * Run opportunity scan for the given user.
     *
     * For now we only run a "revive old leads" rule (if possible).
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

        $createdCount = 0;
        $messages = [];

        // ---------------------------------------------------------------------
        // RULE #1: REVIVE OLD LEADS (only if Lead model + leads table exist)
        // ---------------------------------------------------------------------
        if (class_exists(\App\Models\Lead::class) && Schema::hasTable('leads')) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = \App\Models\Lead::query();

            // Scope by agency if the column exists (extra safety).
            if (Schema::hasColumn('leads', 'agency_id')) {
                $query->where('agency_id', $agencyId);
            }

            $threshold = now()->subDays(30);

            // Prefer updated_at if it exists; otherwise fall back to created_at.
            if (Schema::hasColumn('leads', 'updated_at')) {
                $query->where('updated_at', '<=', $threshold);
            } elseif (Schema::hasColumn('leads', 'created_at')) {
                $query->where('created_at', '<=', $threshold);
            }

            // Limit for safety while we’re early in development.
            $leads = $query->limit(200)->get();

            foreach ($leads as $lead) {
                // Try to build a friendly display name without assuming exact columns.
                $contactName = null;

                // If there is a contact relation with a full_name, use that
                try {
                    $contact = $lead->contact ?? null;
                    if ($contact && isset($contact->full_name)) {
                        $contactName = $contact->full_name;
                    }
                } catch (\Throwable $e) {
                    // If the relation doesn't exist, ignore and fall back.
                }

                if (! $contactName && isset($lead->name)) {
                    $contactName = $lead->name;
                }

                if (! $contactName && isset($lead->first_name)) {
                    $contactName = trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? ''));
                }

                if (! $contactName) {
                    $contactName = 'Unknown lead';
                }

                $lastTouched = $lead->updated_at ?? $lead->created_at ?? null;

                $title = "Revive old lead: {$contactName}";
                $shortReason = 'This lead has not been touched in over 30 days. Review and consider re-engaging.';
                $recommendedAction = 'Call or text this lead with a fresh angle, updated offer, or follow-up message.';

                // Use updateOrCreate so we don’t create duplicates every time the scan runs.
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
                            'lead_id'      => $lead->id,
                            'last_touched' => $lastTouched ? $lastTouched->toDateTimeString() : null,
                        ],
                    ]
                );

                if ($opp) {
                    $createdCount++;
                }
            }

            $messages[] = "Revive_lead rule executed for {$leads->count()} old leads.";
        } else {
            $messages[] = 'Lead model or leads table not found; revive_lead rule skipped.';
        }

        return [
            'created' => $createdCount,
            'message' => implode(' ', $messages),
        ];
    }
}
