<?php

namespace App\Services\Gideon;

use App\Models\GideonOpportunity;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * OpportunityScanner
 *
 * P1 (quick):
 *  - Revive old leads (30+ days untouched)
 *  - Beneficiary/Emergency Contact opportunities (ONE per client)
 *
 * P2 (deep):
 *  - Policy review opportunities
 *  - Referral request opportunities
 *
 * P3 (archived note re-engagement)  <-- renamed from old P4
 *  - Service Archive notes scan (Not Interested + archived 90+ days ago)
 *  - Archived Leads notes scan (archived 90+ days ago)
 */
class OpportunityScanner
{
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

    public function run(int $agencyId, ?int $userId = null, bool $deep = false): array
    {
        $createdCount = 0;
        $messages = [];

        // ---------------------------
        // P1: Revive old leads
        // ---------------------------
        if (class_exists(Lead::class) && Schema::hasTable('contacts')) {
            [$count, $msg] = $this->reviveOldLeads($agencyId, $userId, 30);
            $createdCount += $count;
            $messages[] = $msg;
        } else {
            $messages[] = 'Lead model or contacts table not found; revive_lead rule skipped.';
        }

        // ---------------------------
        // P1: Beneficiary + Emergency
        // ---------------------------
        [$countBec, $msgBec] = $this->scanBeneficiariesAndEmergencyContacts($agencyId, $userId);
        $createdCount += $countBec;
        $messages[] = $msgBec;

        // ---------------------------
        // P2 + P3: deep scan
        // ---------------------------
        if ($deep) {
            // P2
            [$countPolicies, $msgPolicies] = $this->scanPolicyReviewOpportunities($agencyId, $userId);
            $createdCount += $countPolicies;
            $messages[] = $msgPolicies;

            [$countRef, $msgRef] = $this->scanReferralRequestOpportunities($agencyId, $userId);
            $createdCount += $countRef;
            $messages[] = $msgRef;

            // P3 (renamed from old P4)
            // Priority window 90–180 first, then older.
            [$countP3, $msgP3] = $this->scanArchivedNotesP3($agencyId, $userId, 90, 180);
            $createdCount += $countP3;
            $messages[] = $msgP3;
        }

        return [
            'created' => $createdCount,
            'message' => trim(implode(' ', array_filter($messages))),
        ];
    }

    // =====================================================================
    // P1
    // =====================================================================

    protected function reviveOldLeads(int $agencyId, ?int $userId, int $daysStale = 30): array
    {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Lead::query();

        if (Schema::hasColumn('contacts', 'agency_id')) {
            $query->where('agency_id', $agencyId);
        }

        $threshold = now()->subDays($daysStale);

        if (Schema::hasColumn('contacts', 'updated_at')) {
            $query->where('updated_at', '<=', $threshold);
        } elseif (Schema::hasColumn('contacts', 'created_at')) {
            $query->where('created_at', '<=', $threshold);
        }

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
                        'contact_type' => 'lead',
                    ],
                ]
            );

            if ($opp) $touched++;
        }

        return [$touched, "Revive_lead executed for {$leads->count()} stale leads."];
    }

    protected function scanBeneficiariesAndEmergencyContacts(int $agencyId, ?int $userId): array
    {
        if (! Schema::hasTable('contacts')) {
            return [0, 'Contacts table not found; BEC scan skipped.'];
        }

        if (! Schema::hasTable('contact_relations')) {
            return [0, 'contact_relations table not found; BEC scan skipped.'];
        }

        foreach (['contact_id', 'type', 'contacted'] as $col) {
            if (! Schema::hasColumn('contact_relations', $col)) {
                return [0, "contact_relations missing required column {$col}; BEC scan skipped."];
            }
        }

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

        $contactsQ = DB::table('contacts')->select($select);

        if (Schema::hasColumn('contacts', 'agency_id')) {
            $contactsQ->where('agency_id', $agencyId);
        }

        if ($hasInBoB) {
            $contactsQ->where('in_book_of_business', 1);
        } elseif ($hasContactType) {
            $contactsQ->where('contact_type', 'book');
        }

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

                $clientName = '';
                if ($hasFullName && ! empty($c->full_name)) {
                    $clientName = (string) $c->full_name;
                } else {
                    $first = $hasFirst ? (string) ($c->first_name ?? '') : '';
                    $last  = $hasLast  ? (string) ($c->last_name ?? '') : '';
                    $clientName = trim($first . ' ' . $last);
                }
                if ($clientName === '') $clientName = "Client #{$c->id}";

                $contactType = $hasContactType ? (string) ($c->contact_type ?? '') : '';
                $inBoB       = $hasInBoB ? (int) ($c->in_book_of_business ?? 0) : 0;

                $relsQ = DB::table('contact_relations')
                    ->where('contact_id', $c->id)
                    ->whereIn('type', ['beneficiary', 'emergency']);

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

                $tabsEmpty = ($total === 0);
                $missingBenefMin = ($benefTotal < 2);
                $missingEmergencyMin = ($emTotal < 1);
                $hasUncontacted = ($benefUncontacted > 0 || $emUncontacted > 0);

                if (! ($tabsEmpty || $missingBenefMin || $missingEmergencyMin || $hasUncontacted)) {
                    continue;
                }

                $category = 'beneficiary_emergency_opportunity';

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
                        ],
                    ]
                );

                if ($opp) $touched++;
            }
        });

        return [$touched, "BEC scan (contact_relations) executed for {$scanned} book contacts."];
    }

    // =====================================================================
    // P3 (renamed from old P4) - Archived note re-engagement
    // =====================================================================

    /**
     * P3 — Archived note re-engagement scan:
     * - Service archived contacts (service_status Not Interested + service_archived_at old)
     * - Archived leads (status=archived OR archived_at set) + old enough
     *
     * Notes:
     * - Notes are stored in `notes` table with `contact_id`
     * - We look for a text column in notes: note/body/content/text
     * - allowlist / blocklist keyword heuristics
     */
    protected function scanArchivedNotesP3(int $agencyId, ?int $userId, int $minDays = 90, int $pivotDays = 180): array
    {
        if (! Schema::hasTable('contacts')) {
            return [0, 'Contacts table not found; P3 archived-notes scan skipped.'];
        }

        if (! Schema::hasTable('notes') || ! Schema::hasColumn('notes', 'contact_id')) {
            return [0, 'notes table (with contact_id) not found; P3 archived-notes scan skipped.'];
        }

        // Find the note text column
        $noteCols = Schema::getColumnListing('notes');
        $noteTextCol = null;
        foreach (['note', 'body', 'content', 'text'] as $candidate) {
            if (in_array($candidate, $noteCols, true)) {
                $noteTextCol = $candidate;
                break;
            }
        }
        if (! $noteTextCol) {
            return [0, 'notes table has no recognizable text column (note/body/content/text); P3 archived-notes scan skipped.'];
        }

        $category = 'p3_recovery'; // <-- formerly p4_service_recovery

        $allowlist = [
            'found something cheaper',
            'cheaper',
            'lower premium',
            'too expensive',
            "couldn't afford",
            'cannot afford',
            'price too high',
            'premium too high',
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
            'call back',
            'follow up',
            'down the road',
            'revisit',
            'might come back',
            'might be interested',
            'later',
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
        $minCutoff   = $now->copy()->subDays($minDays);
        $pivotCutoff = $now->copy()->subDays($pivotDays);

        $touched = 0;
        $scanned = 0;

        // ------------------------------------------------------------
        // PASS A: Service archive contacts (priority window then older)
        // ------------------------------------------------------------
        [$tA, $sA] = $this->scanServiceArchivedContactsForP3(
            $agencyId, $userId, $category, $noteTextCol, $allowlist, $blocklist, $minDays, $pivotDays
        );
        $touched += $tA; $scanned += $sA;

        // ------------------------------------------------------------
        // PASS B: Archived leads (priority window then older)
        // ------------------------------------------------------------
        [$tB, $sB] = $this->scanArchivedLeadsForP3(
            $agencyId, $userId, $category, $noteTextCol, $allowlist, $blocklist, $minDays, $pivotDays
        );
        $touched += $tB; $scanned += $sB;

        return [$touched, "P3 archived-notes scan executed for {$scanned} contacts/leads (priority {$minDays}-{$pivotDays}, then older)."];
    }

    protected function scanServiceArchivedContactsForP3(
        int $agencyId,
        ?int $userId,
        string $category,
        string $noteTextCol,
        array $allowlist,
        array $blocklist,
        int $minDays,
        int $pivotDays
    ): array {
        // Require service archive columns
        foreach (['service_status', 'service_archived_at'] as $col) {
            if (! Schema::hasColumn('contacts', $col)) {
                return [0, 0]; // silently skip service portion if schema doesn't support it
            }
        }

        $now = now();
        $minCutoff   = $now->copy()->subDays($minDays);
        $pivotCutoff = $now->copy()->subDays($pivotDays);

        $serviceArchiveStatuses = ['Not Interested', 'not interested', 'not_interested', 'NOT INTERESTED'];

        $touched = 0;
        $scanned = 0;

        // PASS 1: 90–180 window
        $q1 = DB::table('contacts')->select(['id', 'service_archived_at', 'service_status']);

        if (Schema::hasColumn('contacts', 'agency_id')) $q1->where('agency_id', $agencyId);
        if (Schema::hasColumn('contacts', 'contact_type')) $q1->where('contact_type', 'service');

        $q1->whereNotNull('service_archived_at')
            ->whereBetween('service_archived_at', [$pivotCutoff, $minCutoff])
            ->whereIn('service_status', $serviceArchiveStatuses);

        $q1->orderBy('service_archived_at', 'asc')->chunk(200, function ($rows) use (
            $agencyId, $userId, $category, $noteTextCol, $allowlist, $blocklist, $minDays, $pivotDays,
            &$touched, &$scanned
        ) {
            foreach ($rows as $row) {
                $scanned++;
                $contactId = (int) $row->id;

                if ($this->p3Exists($agencyId, $contactId, $category)) continue;

                $corpus = $this->buildNotesCorpus($contactId, $noteTextCol);
                if ($corpus === null) continue;

                if ($this->textContainsAny($corpus, $blocklist)) continue;

                $matchedSignal = $this->firstMatchedNeedle($corpus, $allowlist);
                if (! $matchedSignal) continue;

                $opp = GideonOpportunity::updateOrCreate(
                    [
                        'agency_id'   => $agencyId,
                        'entity_type' => 'contact',
                        'entity_id'   => $contactId,
                        'category'    => $category,
                    ],
                    [
                        'user_id'            => $userId ?: null,
                        'title'              => 'P3: Archived re-engagement (notes signal)',
                        'short_reason'       => 'Archived notes show hesitation (price/coverage/timing). Client may be open to revisiting.',
                        'recommended_action' => 'Soft check-in, ask what they chose, and offer a quick comparison or re-quote if there are gaps.',
                        'score'              => 35,
                        'status'             => 'open',
                        'source_snapshot'    => [
                            'contact_id'          => $contactId,
                            'contact_type'        => 'service',
                            'service_status'      => (string) ($row->service_status ?? ''),
                            'service_archived_at' => (string) ($row->service_archived_at ?? null),
                            'priority_window'     => "{$minDays}-{$pivotDays}",
                            'matched_signal'      => $matchedSignal,
                            'signal_source'       => "notes.{$noteTextCol}",
                        ],
                    ]
                );

                if ($opp) $touched++;
            }
        });

        // PASS 2: older than pivot
        $q2 = DB::table('contacts')->select(['id', 'service_archived_at', 'service_status']);

        if (Schema::hasColumn('contacts', 'agency_id')) $q2->where('agency_id', $agencyId);
        if (Schema::hasColumn('contacts', 'contact_type')) $q2->where('contact_type', 'service');

        $q2->whereNotNull('service_archived_at')
            ->where('service_archived_at', '<=', $pivotCutoff)
            ->whereIn('service_status', $serviceArchiveStatuses);

        $q2->orderBy('service_archived_at', 'asc')->chunk(200, function ($rows) use (
            $agencyId, $userId, $category, $noteTextCol, $allowlist, $blocklist, $pivotDays,
            &$touched, &$scanned
        ) {
            foreach ($rows as $row) {
                $scanned++;
                $contactId = (int) $row->id;

                if ($this->p3Exists($agencyId, $contactId, $category)) continue;

                $corpus = $this->buildNotesCorpus($contactId, $noteTextCol);
                if ($corpus === null) continue;

                if ($this->textContainsAny($corpus, $blocklist)) continue;

                $matchedSignal = $this->firstMatchedNeedle($corpus, $allowlist);
                if (! $matchedSignal) continue;

                $opp = GideonOpportunity::updateOrCreate(
                    [
                        'agency_id'   => $agencyId,
                        'entity_type' => 'contact',
                        'entity_id'   => $contactId,
                        'category'    => $category,
                    ],
                    [
                        'user_id'            => $userId ?: null,
                        'title'              => 'P3: Archived re-engagement (notes signal)',
                        'short_reason'       => 'Archived notes show hesitation (price/coverage/timing). Client may be open to revisiting.',
                        'recommended_action' => 'Soft check-in, ask what they chose, and offer a quick comparison or re-quote if there are gaps.',
                        'score'              => 35,
                        'status'             => 'open',
                        'source_snapshot'    => [
                            'contact_id'          => $contactId,
                            'contact_type'        => 'service',
                            'service_status'      => (string) ($row->service_status ?? ''),
                            'service_archived_at' => (string) ($row->service_archived_at ?? null),
                            'priority_window'     => ">{$pivotDays}",
                            'matched_signal'      => $matchedSignal,
                            'signal_source'       => "notes.{$noteTextCol}",
                        ],
                    ]
                );

                if ($opp) $touched++;
            }
        });

        return [$touched, $scanned];
    }

    protected function scanArchivedLeadsForP3(
        int $agencyId,
        ?int $userId,
        string $category,
        string $noteTextCol,
        array $allowlist,
        array $blocklist,
        int $minDays,
        int $pivotDays
    ): array {
        if (! Schema::hasTable('contacts')) {
            return [0, 0];
        }

        $now = now();
        $minCutoff   = $now->copy()->subDays($minDays);
        $pivotCutoff = $now->copy()->subDays($pivotDays);

        $hasAgency = Schema::hasColumn('contacts', 'agency_id');
        $hasType   = Schema::hasColumn('contacts', 'contact_type');
        $hasStatus = Schema::hasColumn('contacts', 'status');
        $hasArchivedAt = Schema::hasColumn('contacts', 'archived_at');
        $hasUpdatedAt  = Schema::hasColumn('contacts', 'updated_at');
        $hasCreatedAt  = Schema::hasColumn('contacts', 'created_at');

        // Choose best "archived date" column we can:
        // archived_at > updated_at > created_at
        $dateCol = $hasArchivedAt ? 'archived_at' : ($hasUpdatedAt ? 'updated_at' : ($hasCreatedAt ? 'created_at' : null));
        if (! $dateCol) {
            // No reliable date column; skip lead-archive scan to avoid noise
            return [0, 0];
        }

        $touched = 0;
        $scanned = 0;

        // Helper to build base query for archived leads
        $baseLeadQuery = function () use ($agencyId, $hasAgency, $hasType, $hasStatus, $dateCol) {
            $q = DB::table('contacts')->select(['id', $dateCol]);

            if ($hasAgency) $q->where('agency_id', $agencyId);

            // Try to limit to leads if your schema supports it
            if ($hasType) $q->where('contact_type', 'lead');

            // Archived condition:
            // - status = archived (if exists)
            // - OR archived_at is not null (if archived_at exists and is our dateCol)
            if ($hasStatus) {
                $q->where('status', 'archived');
            } else {
                // If there's no status column, require archived_at not null if archived_at exists
                if (Schema::hasColumn('contacts', 'archived_at')) {
                    $q->whereNotNull('archived_at');
                }
            }

            return $q;
        };

        // PASS 1: 90–180
        $q1 = $baseLeadQuery();
        $q1->whereNotNull($dateCol)
            ->whereBetween($dateCol, [$pivotCutoff, $minCutoff])
            ->orderBy($dateCol, 'asc')
            ->chunk(200, function ($rows) use (
                $agencyId, $userId, $category, $noteTextCol, $allowlist, $blocklist, $minDays, $pivotDays, $dateCol,
                &$touched, &$scanned
            ) {
                foreach ($rows as $row) {
                    $scanned++;
                    $contactId = (int) $row->id;

                    if ($this->p3Exists($agencyId, $contactId, $category)) continue;

                    $corpus = $this->buildNotesCorpus($contactId, $noteTextCol);
                    if ($corpus === null) continue;

                    if ($this->textContainsAny($corpus, $blocklist)) continue;

                    $matchedSignal = $this->firstMatchedNeedle($corpus, $allowlist);
                    if (! $matchedSignal) continue;

                    $opp = GideonOpportunity::updateOrCreate(
                        [
                            'agency_id'   => $agencyId,
                            'entity_type' => 'contact', // leads are contacts in your DB model
                            'entity_id'   => $contactId,
                            'category'    => $category,
                        ],
                        [
                            'user_id'            => $userId ?: null,
                            'title'              => 'P3: Archived lead re-engagement (notes signal)',
                            'short_reason'       => 'Archived lead notes show a buying signal or “later” language—may be worth a soft re-touch.',
                            'recommended_action' => 'Soft check-in text/call: “Still shopping? Want me to see if pricing improved?”',
                            'score'              => 33,
                            'status'             => 'open',
                            'source_snapshot'    => [
                                'contact_id'      => $contactId,
                                'contact_type'    => 'lead',
                                'archived_date'   => (string) ($row->{$dateCol} ?? null),
                                'archived_date_col' => $dateCol,
                                'priority_window' => "{$minDays}-{$pivotDays}",
                                'matched_signal'  => $matchedSignal,
                                'signal_source'   => "notes.{$noteTextCol}",
                            ],
                        ]
                    );

                    if ($opp) $touched++;
                }
            });

        // PASS 2: older than pivot
        $q2 = $baseLeadQuery();
        $q2->whereNotNull($dateCol)
            ->where($dateCol, '<=', $pivotCutoff)
            ->orderBy($dateCol, 'asc')
            ->chunk(200, function ($rows) use (
                $agencyId, $userId, $category, $noteTextCol, $allowlist, $blocklist, $pivotDays, $dateCol,
                &$touched, &$scanned
            ) {
                foreach ($rows as $row) {
                    $scanned++;
                    $contactId = (int) $row->id;

                    if ($this->p3Exists($agencyId, $contactId, $category)) continue;

                    $corpus = $this->buildNotesCorpus($contactId, $noteTextCol);
                    if ($corpus === null) continue;

                    if ($this->textContainsAny($corpus, $blocklist)) continue;

                    $matchedSignal = $this->firstMatchedNeedle($corpus, $allowlist);
                    if (! $matchedSignal) continue;

                    $opp = GideonOpportunity::updateOrCreate(
                        [
                            'agency_id'   => $agencyId,
                            'entity_type' => 'contact',
                            'entity_id'   => $contactId,
                            'category'    => $category,
                        ],
                        [
                            'user_id'            => $userId ?: null,
                            'title'              => 'P3: Archived lead re-engagement (notes signal)',
                            'short_reason'       => 'Archived lead notes show a buying signal or “later” language—may be worth a soft re-touch.',
                            'recommended_action' => 'Soft check-in text/call: “Still shopping? Want me to see if pricing improved?”',
                            'score'              => 33,
                            'status'             => 'open',
                            'source_snapshot'    => [
                                'contact_id'        => $contactId,
                                'contact_type'      => 'lead',
                                'archived_date'     => (string) ($row->{$dateCol} ?? null),
                                'archived_date_col' => $dateCol,
                                'priority_window'   => ">{$pivotDays}",
                                'matched_signal'    => $matchedSignal,
                                'signal_source'     => "notes.{$noteTextCol}",
                            ],
                        ]
                    );

                    if ($opp) $touched++;
                }
            });

        return [$touched, $scanned];
    }

    protected function p3Exists(int $agencyId, int $contactId, string $category): bool
    {
        return GideonOpportunity::query()
            ->where('agency_id', $agencyId)
            ->where('entity_type', 'contact')
            ->where('entity_id', $contactId)
            ->where('category', $category)
            ->exists();
    }

    protected function buildNotesCorpus(int $contactId, string $noteTextCol): ?string
    {
        $parts = DB::table('notes')
            ->where('contact_id', $contactId)
            ->pluck($noteTextCol)
            ->filter()
            ->all();

        if (empty($parts)) return null;

        return strtolower(implode(' ', $parts));
    }

    // =====================================================================
    // P2
    // =====================================================================

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

        if ($issueCol) $q->whereDate($issueCol, '<=', $threshold->toDateString());
        else $q->where($createdCol, '<=', $threshold);

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
                            'policy_id'     => $p->id,
                            'contact_id'    => $p->contact_id ?? null,
                            'carrier'       => $carrier,
                            'product'       => $product,
                            'policy_number' => $p->policy_number ?? null,
                            'status'        => $p->status ?? null,
                        ],
                    ]
                );

                if ($opp) $touched++;
            }
        });

        return [$touched, "Policy review scan executed for {$scanned} policies."];
    }

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

        if (Schema::hasColumn('policies', 'agency_id')) $q->where('agency_id', $agencyId);
        elseif (Schema::hasColumn('policies', 'tenant_id')) $q->where('tenant_id', $agencyId);

        if (Schema::hasColumn('policies', 'status')) {
            $q->where(function ($w) {
                $w->whereNull('status')
                  ->orWhereIn('status', ['active', 'inforce', 'in_force', 'approved']);
            });
        }

        if ($dateCol === 'created_at') $q->where('created_at', '<=', $threshold);
        else $q->whereDate('policy_issue_date', '<=', $threshold->toDateString());

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

                    if ($recentReferralNoteExists) continue;
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
                        'short_reason'       => 'Client has an in-force policy long enough to justify a referral ask.',
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

    // =====================================================================
    // helpers
    // =====================================================================

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
