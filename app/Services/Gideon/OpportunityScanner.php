<?php

namespace App\Services\Gideon;

use App\Models\GideonOpportunity;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * OpportunityScanner
 *
 * Tier 1 Gideon scanner that:
 * - Reads CRM data (Leads + Book/Clients)
 * - Writes scored opportunities into gideon_opportunities
 *
 * QUICK SCAN (deep=false):
 *  - Revive old leads (30+ days untouched)
 *  - Missing/insufficient Beneficiaries (needs >= 2)
 *  - Missing Emergency Contacts (needs >= 1)
 *
 * DEEP SCAN (deep=true):
 *  - Policy review opportunities (policies older than X months)
 *  - Referral request opportunities (heuristic)
 */
class OpportunityScanner
{
    /**
     * Backwards-compatible entrypoint (used by /gideon/run-opportunity-scan).
     */
    public function runForUser($user, bool $deep = false): array
    {
        if (! $user) {
            return ['created' => 0, 'message' => 'No authenticated user.'];
        }

        $agencyId = $user->agency_id ?? null;

        if (! $agencyId) {
            return ['created' => 0, 'message' => 'User has no agency_id; cannot scope opportunities.'];
        }

        return $this->run((int) $agencyId, (int) ($user->id ?? 0), $deep);
    }

    /**
     * Controller-friendly entrypoint (used by GideonScanController).
     *
     * @param  int       $agencyId
     * @param  int|null  $userId
     * @param  bool      $deep
     */
    public function run(int $agencyId, ?int $userId = null, bool $deep = false): array
    {
        $createdCount = 0;
        $messages = [];

        // ------------------------------------------------------------
        // RULE #1: REVIVE OLD LEADS (30+ days untouched)
        // ------------------------------------------------------------
        if (class_exists(Lead::class) && Schema::hasTable('contacts')) {
            [$count, $msg] = $this->reviveOldLeads($agencyId, $userId, 30);
            $createdCount += $count;
            $messages[] = $msg;
        } else {
            $messages[] = 'Lead model or contacts table not found; revive_lead rule skipped.';
        }

        // ------------------------------------------------------------
        // QUICK SCAN: Beneficiaries + Emergency Contacts
        // ------------------------------------------------------------
        [$countBec, $msgBec] = $this->scanBeneficiariesAndEmergencyContacts($agencyId, $userId);
        $createdCount += $countBec;
        $messages[] = $msgBec;

        // ------------------------------------------------------------
        // DEEP SCAN: Policy reviews + Referral callbacks
        // ------------------------------------------------------------
        if ($deep) {
            [$countPolicies, $msgPolicies] = $this->scanPolicyReviewOpportunities($agencyId, $userId);
            $createdCount += $countPolicies;
            $messages[] = $msgPolicies;

            [$countRef, $msgRef] = $this->scanReferralRequestOpportunities($agencyId, $userId);
            $createdCount += $countRef;
            $messages[] = $msgRef;
        }

        return [
            'created' => $createdCount,
            'message' => trim(implode(' ', array_filter($messages))),
        ];
    }

    // =====================================================================
    // RULE IMPLEMENTATIONS
    // =====================================================================

    /**
     * Revive old leads using the Lead model (which is a scoped Contact).
     */
    protected function reviveOldLeads(int $agencyId, ?int $userId, int $daysStale = 30): array
    {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Lead::query();

        // Extra safety: scope by agency_id if the column exists.
        if (Schema::hasColumn('contacts', 'agency_id')) {
            $query->where('agency_id', $agencyId);
        }

        $threshold = now()->subDays($daysStale);

        // Prefer updated_at if present; otherwise fall back to created_at.
        if (Schema::hasColumn('contacts', 'updated_at')) {
            $query->where('updated_at', '<=', $threshold);
        } elseif (Schema::hasColumn('contacts', 'created_at')) {
            $query->where('created_at', '<=', $threshold);
        }

        // Safety limit.
        $leads = $query->limit(300)->get();
        $touched = 0;

        foreach ($leads as $lead) {
            $name = $this->guessLeadName($lead);
            $lastTouched = $lead->updated_at ?? $lead->created_at ?? null;

            $opp = GideonOpportunity::updateOrCreate(
                [
                    'agency_id'   => $agencyId,
                    'entity_type' => 'lead',
                    'entity_id'   => $lead->id,
                    'category'    => 'revive_lead',
                ],
                [
                    'user_id'            => $userId ?: null,
                    'title'              => "Revive old lead: {$name}",
                    'short_reason'       => "This lead has not been touched in {$daysStale}+ days. Review and re-engage.",
                    'recommended_action' => 'Call + text with a fresh angle. If no response, set a follow-up date.',
                    'score'              => 70,
                    'status'             => 'open',
                    'source_snapshot'    => [
                        'contact_id'   => $lead->id,
                        'full_name'    => $name,
                        'status'       => $lead->status ?? null,
                        'source'       => $lead->source ?? null,
                        'last_touched' => $lastTouched ? $lastTouched->toDateTimeString() : null,
                    ],
                ]
            );

            if ($opp) $touched++;
        }

        return [$touched, "Revive_lead executed for {$leads->count()} stale leads."];
    }

    /**
     * QUICK SCAN:
     * - Beneficiaries: require >= 2
     * - Emergency Contacts: require >= 1
     *
     * Works even if your app uses either:
     * - beneficiaries + emergency_contacts tables
     * - beneficiaries + emergencies tables
     *
     * If none of these tables exist, this rule safely no-ops.
     */
    protected function scanBeneficiariesAndEmergencyContacts(int $agencyId, ?int $userId): array
    {
        if (! Schema::hasTable('contacts')) {
            return [0, 'Contacts table not found; BEC scan skipped.'];
        }

        // Detect table names
        $benefTable = Schema::hasTable('beneficiaries') ? 'beneficiaries' : null;

        $emergencyTable = null;
        if (Schema::hasTable('emergency_contacts')) $emergencyTable = 'emergency_contacts';
        elseif (Schema::hasTable('emergencies')) $emergencyTable = 'emergencies';

        if (! $benefTable && ! $emergencyTable) {
            return [0, 'Beneficiary/Emergency tables not found; BEC scan skipped.'];
        }

        // Build contacts query (prefer scoping out Lead records if contact_type exists)
        $contactsQ = DB::table('contacts')->select(['id']);

        if (Schema::hasColumn('contacts', 'agency_id')) {
            $contactsQ->where('agency_id', $agencyId);
        }

        // If you have contact_type, exclude leads (we want Book/Clients/Service)
        if (Schema::hasColumn('contacts', 'contact_type')) {
            $contactsQ->where(function ($q) {
                $q->whereNull('contact_type')->orWhere('contact_type', '!=', 'Lead');
            });
        }

        // If you have an "archived" concept, skip archived
        if (Schema::hasColumn('contacts', 'status')) {
            $contactsQ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'archived');
            });
        }

        $touched = 0;
        $scanned = 0;

        $contactsQ->orderBy('id')->chunk(250, function ($rows) use (
            $agencyId, $userId, $benefTable, $emergencyTable, &$touched, &$scanned
        ) {
            foreach ($rows as $c) {
                $scanned++;

                // Beneficiaries count
                $benefCount = null;
                if ($benefTable) {
                    $benefCount = DB::table($benefTable)
                        ->where('contact_id', $c->id)
                        ->count();
                }

                // Emergency contacts count
                $emCount = null;
                if ($emergencyTable) {
                    // common FK is contact_id; if not, this will simply be 0 and no-op safely
                    $emCount = DB::table($emergencyTable)
                        ->where('contact_id', $c->id)
                        ->count();
                }

                // Create opportunities only when there's a real gap
                if ($benefTable && is_int($benefCount) && $benefCount < 2) {
                    $needed = 2 - $benefCount;

                    $opp = GideonOpportunity::updateOrCreate(
                        [
                            'agency_id'   => $agencyId,
                            'entity_type' => 'contact',
                            'entity_id'   => $c->id,
                            'category'    => 'missing_beneficiaries',
                        ],
                        [
                            'user_id'            => $userId ?: null,
                            'title'              => $benefCount === 0
                                ? 'Add beneficiaries (none on file)'
                                : 'Add more beneficiaries (need at least 2)',
                            'short_reason'       => $benefCount === 0
                                ? 'No beneficiaries are on file. This creates claim friction and increases lapse/cancellation risk.'
                                : "Only {$benefCount} beneficiary on file. Minimum standard is 2 for redundancy and clarity.",
                            'recommended_action' => $needed === 1
                                ? 'Ask client for a second beneficiary (backup). Confirm relationship + phone.'
                                : 'Collect 2+ beneficiaries: full name, relationship, phone, and % if applicable.',
                            'score'              => $benefCount === 0 ? 92 : 85,
                            'status'             => 'open',
                            'source_snapshot'    => [
                                'contact_id'           => $c->id,
                                'beneficiaries_count'  => $benefCount,
                                'target_minimum'       => 2,
                                'why_it_matters'       => [
                                    'Prevents claim delays/confusion',
                                    'Improves persistency and reduces cancellations',
                                    'Creates a natural reason for a policy review + family conversation',
                                ],
                            ],
                        ]
                    );

                    if ($opp) $touched++;
                }

                if ($emergencyTable && is_int($emCount) && $emCount < 1) {
                    $opp = GideonOpportunity::updateOrCreate(
                        [
                            'agency_id'   => $agencyId,
                            'entity_type' => 'contact',
                            'entity_id'   => $c->id,
                            'category'    => 'missing_emergency_contact',
                        ],
                        [
                            'user_id'            => $userId ?: null,
                            'title'              => 'Add an emergency contact (none on file)',
                            'short_reason'       => 'No emergency contact is on file. This weakens your ability to protect persistency and support the family.',
                            'recommended_action' => 'Collect 1 emergency contact: full name, relationship, and phone. Confirm best time to reach them.',
                            'score'              => 90,
                            'status'             => 'open',
                            'source_snapshot'    => [
                                'contact_id'            => $c->id,
                                'emergency_count'       => $emCount,
                                'target_minimum'        => 1,
                                'why_it_matters'        => [
                                    'Helps prevent lapses by reaching someone if client is unavailable',
                                    'Creates a second relationship inside the household/family',
                                    'Reduces chargebacks by increasing touchpoints and support',
                                ],
                            ],
                        ]
                    );

                    if ($opp) $touched++;
                }
            }
        });

        return [$touched, "BEC scan executed for {$scanned} contacts."];
    }

    /**
     * DEEP SCAN:
     * Policy review opportunities.
     *
     * Heuristic:
     * - If policies table exists:
     *   - policy_issue_date older than 11 months (or created_at older than 11 months) AND status is active-ish
     */
    protected function scanPolicyReviewOpportunities(int $agencyId, ?int $userId): array
    {
        if (! Schema::hasTable('policies')) {
            return [0, 'Policies table not found; policy review scan skipped.'];
        }

        $issueCol = Schema::hasColumn('policies', 'policy_issue_date') ? 'policy_issue_date' : null;
        $createdCol = Schema::hasColumn('policies', 'created_at') ? 'created_at' : null;

        if (! $issueCol && ! $createdCol) {
            return [0, 'Policies has no recognizable dates; policy review scan skipped.'];
        }

        $threshold = now()->subMonths(11);

        $q = DB::table('policies')->select([
            'id', 'contact_id',
            Schema::hasColumn('policies', 'carrier') ? 'carrier' : DB::raw('NULL as carrier'),
            Schema::hasColumn('policies', 'product') ? 'product' : DB::raw('NULL as product'),
            Schema::hasColumn('policies', 'policy_number') ? 'policy_number' : DB::raw('NULL as policy_number'),
            Schema::hasColumn('policies', 'status') ? 'status' : DB::raw('NULL as status'),
        ]);

        if (Schema::hasColumn('policies', 'agency_id')) {
            $q->where('agency_id', $agencyId);
        } elseif (Schema::hasColumn('policies', 'tenant_id')) {
            // some earlier specs used tenant_id
            $q->where('tenant_id', $agencyId);
        }

        if (Schema::hasColumn('policies', 'status')) {
            $q->where(function ($w) {
                $w->whereNull('status')
                  ->orWhereIn('status', ['active', 'inforce', 'in_force', 'approved']);
            });
        }

        if ($issueCol) {
            $q->whereDate($issueCol, '<=', $threshold->toDateString());
        } else {
            $q->where($createdCol, '<=', $threshold);
        }

        $touched = 0;
        $scanned = 0;

        $q->orderBy('id')->chunk(250, function ($rows) use ($agencyId, $userId, &$touched, &$scanned) {
            foreach ($rows as $p) {
                $scanned++;

                $carrier = $p->carrier ?? null;
                $product = $p->product ?? null;

                $label = trim(implode(' ', array_filter([$carrier, $product])));
                if ($label === '') $label = 'Policy';

                $opp = GideonOpportunity::updateOrCreate(
                    [
                        'agency_id'   => $agencyId,
                        'entity_type' => 'policy',
                        'entity_id'   => $p->id,
                        'category'    => 'policy_review',
                    ],
                    [
                        'user_id'            => $userId ?: null,
                        'title'              => "Policy review due: {$label}",
                        'short_reason'       => 'This policy is approaching an annual check-in window. Annual reviews create retention + upsell opportunities.',
                        'recommended_action' => 'Call client: confirm beneficiaries/emergency contacts, verify address, ask for referrals, and check coverage gaps.',
                        'score'              => 78,
                        'status'             => 'open',
                        'source_snapshot'    => [
                            'policy_id'      => $p->id,
                            'contact_id'     => $p->contact_id ?? null,
                            'carrier'        => $carrier,
                            'product'        => $product,
                            'policy_number'  => $p->policy_number ?? null,
                            'status'         => $p->status ?? null,
                        ],
                    ]
                );

                if ($opp) $touched++;
            }
        });

        return [$touched, "Policy review scan executed for {$scanned} policies."];
    }

    /**
     * DEEP SCAN:
     * Referral request opportunities.
     *
     * Heuristic (safe + simple):
     * - Needs policies table
     * - For each contact with at least 1 active policy older than 45 days,
     *   if notes table exists we avoid duplicates if a recent "referral" note exists.
     */
    protected function scanReferralRequestOpportunities(int $agencyId, ?int $userId): array
    {
        if (! Schema::hasTable('policies')) {
            return [0, 'Policies table not found; referral scan skipped.'];
        }

        $notesTable = Schema::hasTable('notes') ? 'notes' : null;

        // Determine a reasonable "policy age" check
        $dateCol = Schema::hasColumn('policies', 'policy_issue_date')
            ? 'policy_issue_date'
            : (Schema::hasColumn('policies', 'created_at') ? 'created_at' : null);

        if (! $dateCol) {
            return [0, 'Policies has no recognizable dates; referral scan skipped.'];
        }

        $threshold = now()->subDays(45);
        $recentReferralWindow = now()->subDays(60);

        // Get distinct contact_ids with policies older than threshold
        $q = DB::table('policies')->select('contact_id')
            ->whereNotNull('contact_id')
            ->groupBy('contact_id');

        if (Schema::hasColumn('policies', 'agency_id')) {
            $q->where('agency_id', $agencyId);
        } elseif (Schema::hasColumn('policies', 'tenant_id')) {
            $q->where('tenant_id', $agencyId);
        }

        if (Schema::hasColumn('policies', 'status')) {
            $q->where(function ($w) {
                $w->whereNull('status')
                  ->orWhereIn('status', ['active', 'inforce', 'in_force', 'approved']);
            });
        }

        if ($dateCol === 'created_at') {
            $q->where('created_at', '<=', $threshold);
        } else {
            $q->whereDate('policy_issue_date', '<=', $threshold->toDateString());
        }

        $touched = 0;
        $scanned = 0;

        $q->orderBy('contact_id')->chunk(300, function ($rows) use (
            $agencyId, $userId, $notesTable, $recentReferralWindow, &$touched, &$scanned
        ) {
            foreach ($rows as $row) {
                $scanned++;

                $contactId = $row->contact_id;

                // If we have notes, skip if a recent referral note exists (prevents spam)
                if ($notesTable && Schema::hasColumn($notesTable, 'entity_type') && Schema::hasColumn($notesTable, 'entity_id')) {
                    $recentReferralNoteExists = DB::table($notesTable)
                        ->where('entity_type', 'contact')
                        ->where('entity_id', $contactId)
                        ->where(function ($w) {
                            $w->where('body', 'like', '%referral%')
                              ->orWhere('body', 'like', '%refer%')
                              ->orWhere('body', 'like', '%introduced%');
                        })
                        ->when(Schema::hasColumn($notesTable, 'created_at'), function ($w) use ($recentReferralWindow) {
                            $w->where('created_at', '>=', $recentReferralWindow);
                        })
                        ->exists();

                    if ($recentReferralNoteExists) {
                        continue;
                    }
                }

                $opp = GideonOpportunity::updateOrCreate(
                    [
                        'agency_id'   => $agencyId,
                        'entity_type' => 'contact',
                        'entity_id'   => $contactId,
                        'category'    => 'request_referrals',
                    ],
                    [
                        'user_id'            => $userId ?: null,
                        'title'              => 'Request referrals (warm client)',
                        'short_reason'       => 'Client has an in-force policy long enough to justify a referral ask. Referral calls are high-conversion and low-cost.',
                        'recommended_action' => 'Call: “Who else do you care about that would want the same protection?” Ask for 2 names + numbers.',
                        'score'              => 74,
                        'status'             => 'open',
                        'source_snapshot'    => [
                            'contact_id' => $contactId,
                            'tip'        => 'Ask for referrals right after a positive service moment or annual review confirmation.',
                        ],
                    ]
                );

                if ($opp) $touched++;
            }
        });

        return [$touched, "Referral scan executed for {$scanned} policy-holders."];
    }

    /**
     * Best-effort name builder for a lead/contact.
     */
    protected function guessLeadName($lead): string
    {
        if (isset($lead->full_name) && $lead->full_name) {
            return (string) $lead->full_name;
        }

        $first = $lead->first_name ?? '';
        $last  = $lead->last_name ?? '';

        $name = trim($first . ' ' . $last);

        return $name !== '' ? $name : 'Unknown lead';
    }
}
