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
 *  - Beneficiary/Emergency Contact opportunities (Option 1: ONE opportunity per client)
 *
 * DEEP SCAN (deep=true):
 *  - Policy review opportunities (policies older than X months)
 *  - Referral request opportunities (heuristic)
 *  - P4: Service Archive note scan (Not Interested + archived 120–365 days ago + keyword signals)
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
        // QUICK SCAN: Beneficiaries + Emergency Contacts (Book of Business)
        // ------------------------------------------------------------
        [$countBec, $msgBec] = $this->scanBeneficiariesAndEmergencyContacts($agencyId, $userId);
        $createdCount += $countBec;
        $messages[] = $msgBec;

        // ------------------------------------------------------------
        // DEEP SCAN: Policy reviews + Referral callbacks + P4 Service Archive
        // ------------------------------------------------------------
        if ($deep) {
            [$countPolicies, $msgPolicies] = $this->scanPolicyReviewOpportunities($agencyId, $userId);
            $createdCount += $countPolicies;
            $messages[] = $msgPolicies;

            [$countRef, $msgRef] = $this->scanReferralRequestOpportunities($agencyId, $userId);
            $createdCount += $countRef;
            $messages[] = $msgRef;

            // P4: Service Archive notes scan (ONLY Service Archive, ONLY notes)
            [$countP4, $msgP4] = $this->scanServiceArchiveNotes($agencyId, $userId, 120, 365);
            $createdCount += $countP4;
            $messages[] = $msgP4;
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
     * QUICK SCAN (Book of Business only) — Option 1: ONE opportunity per client.
     *
     * Your actual data model:
     * - Beneficiaries/Emergency contacts live in `contact_relations`
     * - `contact_relations.type` is 'beneficiary' or 'emergency'
     * - `contact_relations.contacted` (0 = No / not contacted yet)
     *
     * Create ONE opportunity per Book-of-Business client when ANY is true:
     * - No relations at all (tabs empty)
     * - Beneficiaries < 2
     * - Emergency contacts < 1
     * - Any relation has contacted = 0
     */
    protected function scanBeneficiariesAndEmergencyContacts(int $agencyId, ?int $userId): array
    {
        if (! Schema::hasTable('contacts')) {
            return [0, 'Contacts table not found; BEC scan skipped.'];
        }

        if (! Schema::hasTable('contact_relations')) {
            return [0, 'contact_relations table not found; BEC scan skipped.'];
        }

        // Required columns (safe guards)
        foreach (['contact_id', 'type', 'contacted'] as $col) {
            if (! Schema::hasColumn('contact_relations', $col)) {
                return [0, "contact_relations missing required column {$col}; BEC scan skipped."];
            }
        }

        // Build select list dynamically (so we can show client name)
        $select = ['id'];

        $hasFullName = Schema::hasColumn('contacts', 'full_name');
        $hasFirst    = Schema::hasColumn('contacts', 'first_name');
        $hasLast     = Schema::hasColumn('contacts', 'last_name');

        if ($hasFullName) $select[] = 'full_name';
        if ($hasFirst)    $select[] = 'first_name';
        if ($hasLast)     $select[] = 'last_name';

        $hasContactType = Schema::hasColumn('contacts', 'contact_type');
        $hasInBoB       = Schema::hasColumn('contacts', 'in_book_of_business');

        if ($hasContactType) $select[] = 'contact_type';
        if ($hasInBoB)       $select[] = 'in_book_of_business';

        // Book of Business contacts only
        $contactsQ = DB::table('contacts')->select($select);

        if (Schema::hasColumn('contacts', 'agency_id')) {
            $contactsQ->where('agency_id', $agencyId);
        }

        // Prefer the real flag your app uses
        if ($hasInBoB) {
            $contactsQ->where('in_book_of_business', 1);
        } elseif ($hasContactType) {
            $contactsQ->where('contact_type', 'book');
        }

        // Skip archived if present
        if (Schema::hasColumn('contacts', 'status')) {
            $contactsQ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'archived');
            });
        }

        $touched = 0;
        $scanned = 0;

        $contactsQ->orderBy('id')->chunk(200, function ($rows) use ($agencyId, $userId, $hasFullName, $hasFirst, $hasLast, $hasContactType, $hasInBoB, &$touched, &$scanned) {
            foreach ($rows as $c) {
                $scanned++;

                // Build client name for dashboard display
                $clientName = '';
                if ($hasFullName && ! empty($c->full_name)) {
                    $clientName = (string) $c->full_name;
                } else {
                    $first = $hasFirst ? (string) ($c->first_name ?? '') : '';
                    $last  = $hasLast  ? (string) ($c->last_name ?? '') : '';
                    $clientName = trim($first . ' ' . $last);
                }
                if ($clientName === '') {
                    $clientName = "Client #{$c->id}";
                }

                $contactType = $hasContactType ? (string) ($c->contact_type ?? '') : '';
                $inBoB       = $hasInBoB ? (int) ($c->in_book_of_business ?? 0) : 0;

                $relsQ = DB::table('contact_relations')
                    ->where('contact_id', $c->id)
                    ->whereIn('type', ['beneficiary', 'emergency']);

                // Scope by agency_id if the column exists
                if (Schema::hasColumn('contact_relations', 'agency_id')) {
                    $relsQ->where('agency_id', $agencyId);
                }

                $rels = $relsQ->get(['type', 'contacted']);

                $total = $rels->count();

                $benefTotal = 0;
                $emTotal = 0;
                $benefUncontacted = 0;
                $emUncontacted = 0;

                foreach ($rels as $r) {
                    $type = strtolower((string) ($r->type ?? ''));
                    $contacted = (int) ($r->contacted ?? 0);

                    if ($type === 'beneficiary') {
                        $benefTotal++;
                        if ($contacted === 0) $benefUncontacted++;
                    } elseif ($type === 'emergency') {
                        $emTotal++;
                        if ($contacted === 0) $emUncontacted++;
                    }
                }

                // Your rules
                $tabsEmpty = ($total === 0);
                $missingBenefMin = ($benefTotal < 2);
                $missingEmergencyMin = ($emTotal < 1);
                $hasUncontacted = ($benefUncontacted > 0 || $emUncontacted > 0);

                // Only create if there is a REAL opportunity
                if (! ($tabsEmpty || $missingBenefMin || $missingEmergencyMin || $hasUncontacted)) {
                    continue;
                }

                // Option 1: ONE opportunity per client (do not spam)
                $category = 'beneficiary_emergency_opportunity';

                // Score: strongest if completely missing; then missing minimums; then uncontacted follow-up
                $score = 75;
                if ($tabsEmpty) $score = 92;
                elseif ($missingBenefMin || $missingEmergencyMin) $score = 88;
                elseif ($hasUncontacted) $score = 85;

                $title = "Beneficiaries & emergency contacts need attention — {$clientName}";

                if ($tabsEmpty) {
                    $shortReason = 'This client has no beneficiaries or emergency contacts on file.';
                    $recommendedAction = 'Open the client and add at least 2 beneficiaries and 1 emergency contact (name, relationship, phone).';
                } elseif ($missingBenefMin || $missingEmergencyMin) {
                    $parts = [];
                    if ($missingBenefMin) $parts[] = 'needs at least 2 beneficiaries';
                    if ($missingEmergencyMin) $parts[] = 'needs at least 1 emergency contact';
                    $shortReason = 'This client is missing required protection contacts: ' . implode(' and ', $parts) . '.';
                    $recommendedAction = 'Collect missing contacts (name, relationship, phone) and save them to the client.';
                } else {
                    $shortReason = 'At least one listed beneficiary or emergency contact is marked “No” for contacted.';
                    $recommendedAction = 'Call/text the listed contacts, confirm details, and mark them as contacted.';
                }

                $whyItMatters = [
                    'Right thing to do: beneficiaries should know coverage exists.',
                    'Can save your deal: reduces lapses/cancellations if the client goes dark.',
                    'More premium: beneficiaries/emergency contacts can become new policies.',
                ];

                $opp = GideonOpportunity::updateOrCreate(
                    [
                        'agency_id'   => $agencyId,
                        'entity_type' => 'contact',
                        'entity_id'   => $c->id,
                        'category'    => $category,
                    ],
                    [
                        'user_id'            => $userId ?: null,
                        'title'              => $title,
                        'short_reason'       => $shortReason,
                        'recommended_action' => $recommendedAction,
                        'score'              => $score,
                        'status'             => 'open',
                        'source_snapshot'    => [
                            'contact_id'          => $c->id,
                            'contact_name'        => $clientName,
                            'contact_type'        => $contactType,
                            'in_book_of_business' => $inBoB,
                            'counts' => [
                                'beneficiaries_total'       => $benefTotal,
                                'emergency_total'           => $emTotal,
                                'beneficiaries_uncontacted' => $benefUncontacted,
                                'emergency_uncontacted'     => $emUncontacted,
                                'total_relations'           => $total,
                            ],
                            'rules_triggered' => [
                                'tabs_empty'            => $tabsEmpty,
                                'missing_benef_min_2'   => $missingBenefMin,
                                'missing_em_min_1'      => $missingEmergencyMin,
                                'has_uncontacted'       => $hasUncontacted,
                            ],
                            'why_it_matters' => $whyItMatters,
                        ],
                    ]
                );

                if ($opp) $touched++;
            }
        });

        return [$touched, "BEC scan (contact_relations) executed for {$scanned} book contacts."];
    }

    /**
     * P4 — Service Archive notes scan (ONLY Service Archive, ONLY notes)
     *
     * Criteria:
     * - contacts.service_status = 'Not Interested'
     * - contacts.service_archived_at is between (now - $maxDays) and (now - $minDays)
     * - contacts.notes contains one or more recovery keywords
     * - contacts.notes does NOT contain any hard DNC/legal keywords
     */
    protected function scanServiceArchiveNotes(int $agencyId, ?int $userId, int $minDays = 120, int $maxDays = 365): array
    {
        if (! Schema::hasTable('contacts')) {
            return [0, 'Contacts table not found; P4 scan skipped.'];
        }

        foreach (['service_status', 'service_archived_at', 'notes'] as $col) {
            if (! Schema::hasColumn('contacts', $col)) {
                return [0, "contacts missing {$col}; P4 scan skipped."];
            }
        }

        $category = 'service_archive_recovery';

        $allowlist = [
            'found something cheaper',
            'cheaper',
            'lower premium',
            'too expensive',
            "couldn't afford",
            'cannot afford',
            'price too high',

            'got something better',
            'something better',
            'better coverage',
            'went with another company',
            'another agent',
            'switched',

            'already covered',
            'got coverage',
            'have coverage now',

            'misled',
            'confused',
            'not what i thought',
            'inferior coverage',
            'inferor coverage',
        ];

        $blocklist = [
            'do not contact',
            'stop calling',
            'never call',
            'remove me',
            'leave me alone',

            'attorney',
            'lawsuit',
            'complaint',
            'insurance department',

            'fraud',
            'scam',
        ];

        $now = now();
        $olderThan = $now->copy()->subDays($minDays); // archived <= this means at least minDays old
        $newerThan = $now->copy()->subDays($maxDays); // archived >= this means not older than maxDays

        $q = DB::table('contacts')
            ->select(['id', 'notes', 'service_archived_at'])
            ->where('agency_id', $agencyId)
            ->where('service_status', 'Not Interested')
            ->whereNotNull('service_archived_at')
            ->whereBetween('service_archived_at', [$newerThan, $olderThan])
            ->whereNotNull('notes');

        $touched = 0;
        $scanned = 0;

        $q->orderBy('id')->chunk(200, function ($rows) use ($agencyId, $userId, $category, $allowlist, $blocklist, $minDays, $maxDays, &$touched, &$scanned) {
            foreach ($rows as $row) {
                $scanned++;

                $notes = strtolower((string) ($row->notes ?? ''));
                if ($notes === '') {
                    continue;
                }

                // Disqualifiers first
                if ($this->textContainsAny($notes, $blocklist)) {
                    continue;
                }

                // Must have at least one recovery keyword
                $matchedSignal = $this->firstMatchedNeedle($notes, $allowlist);
                if (! $matchedSignal) {
                    continue;
                }

                $opp = GideonOpportunity::updateOrCreate(
                    [
                        'agency_id'   => $agencyId,
                        'entity_type' => 'contact',
                        'entity_id'   => (int) $row->id,
                        'category'    => $category,
                    ],
                    [
                        'user_id'            => $userId ?: null,
                        'title'              => 'Service Archive: possible recovery opportunity',
                        'short_reason'       => 'Service Archive notes suggest price/coverage switch or confusion (may be worth a re-check-in).',
                        'recommended_action' => 'Review prior quote vs current options and do a soft check-in.',
                        'score'              => 60,
                        'status'             => 'open',
                        'source_snapshot'    => [
                            'service_status'      => 'Not Interested',
                            'service_archived_at' => (string) ($row->service_archived_at ?? null),
                            'window_days'         => "{$minDays}-{$maxDays}",
                            'matched_signal'      => $matchedSignal,
                            'signal_source'       => 'contacts.notes',
                        ],
                    ]
                );

                if ($opp) $touched++;
            }
        });

        return [$touched, "P4 service archive notes scan executed for {$scanned} archived contacts ({$minDays}-{$maxDays} days)."];
    }

    protected function textContainsAny(string $haystack, array $needles): bool
    {
        $haystack = strtolower($haystack);
        foreach ($needles as $n) {
            $n = strtolower(trim((string) $n));
            if ($n !== '' && str_contains($haystack, $n)) {
                return true;
            }
        }
        return false;
    }

    protected function firstMatchedNeedle(string $haystack, array $needles): ?string
    {
        $haystack = strtolower($haystack);
        foreach ($needles as $n) {
            $n = strtolower(trim((string) $n));
            if ($n !== '' && str_contains($haystack, $n)) {
                return $n;
            }
        }
        return null;
    }

    /**
     * DEEP SCAN:
     * Policy review opportunities.
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
     */
    protected function scanReferralRequestOpportunities(int $agencyId, ?int $userId): array
    {
        if (! Schema::hasTable('policies')) {
            return [0, 'Policies table not found; referral scan skipped.'];
        }

        $notesTable = Schema::hasTable('notes') ? 'notes' : null;

        $dateCol = Schema::hasColumn('policies', 'policy_issue_date')
            ? 'policy_issue_date'
            : (Schema::hasColumn('policies', 'created_at') ? 'created_at' : null);

        if (! $dateCol) {
            return [0, 'Policies has no recognizable dates; referral scan skipped.'];
        }

        $threshold = now()->subDays(45);
        $recentReferralWindow = now()->subDays(60);

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
