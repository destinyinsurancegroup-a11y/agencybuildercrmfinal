<?php

namespace App\Http\Controllers\Gideon;

use App\Http\Controllers\Controller;
use App\Models\GideonOpportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class GideonOpportunitiesController extends Controller
{
    /**
     * "View all" page OR JSON list endpoint.
     *
     * - Browser navigation (Accept: text/html) -> returns Blade page
     * - AJAX (Accept: application/json) -> returns JSON list
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->agency_id) {
            if (! $request->expectsJson()) {
                abort(403, 'Unauthorized or user has no agency scope.');
            }

            return response()->json([
                'message' => 'Unauthorized or user has no agency scope.',
            ], 403);
        }

        // ✅ Normal browser page load -> Blade view
        if (! $request->expectsJson() && ! $request->wantsJson()) {
            return view('gideon.opportunities.index');
        }

        // ✅ JSON list
        $query = $this->baseQueryForAgency((int) $user->agency_id);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Allow ?category=a or ?category=a,b,c
        if ($category = $request->query('category')) {
            $cats = array_values(array_filter(array_map('trim', explode(',', $category))));
            if (count($cats) === 1) {
                $query->where('category', $cats[0]);
            } elseif (count($cats) > 1) {
                $query->whereIn('category', $cats);
            }
        }

        $opportunities = $query
            ->orderByDesc('score')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $opportunities = $this->hydrateEntityLabels($opportunities);

        return response()->json($opportunities);
    }

    /**
     * Return grouped opportunity buckets for the dashboard/page UI.
     *
     * Output shape:
     * [
     *   { bucket, priority, title, why, next, count }
     * ]
     */
    public function groups(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->agency_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $agencyId = (int) $user->agency_id;

        // Only open opportunities should show in groups
        $base = $this->baseQueryForAgency($agencyId)->where('status', 'open');

        // IMPORTANT:
        // Do NOT rely on JSON operators for source_snapshot here (it may be TEXT in MySQL).
        // Use stable columns we know exist: category, source_type, rule_code.
        $rows = $base->select(['id', 'category', 'source_type', 'rule_code'])->get();

        $buckets = [
            // =========================
            // P1 Buckets
            // =========================
            'p1_bec' => [
                'priority' => 1,
                'title' => 'Beneficiary & Emergency Contact fixes',
                'why' => 'This prevents claims chaos and reduces cancellations when clients go dark.',
                'next' => 'Open each client and add/fix beneficiaries and emergency contacts.',
                'match' => function ($row) {
                    return (string) ($row->category ?? '') === 'beneficiary_emergency_opportunity';
                },
            ],

            'p1_leads_disposition' => [
                'priority' => 1,
                'title' => 'Leads need disposition',
                'why' => 'These leads have been sitting in your CRM for 14+ days without a final outcome. Undispositioned leads slow follow-up, distort pipeline reports, and cause real opportunities to slip through the cracks.',
                'next' => 'Review each lead and set a final disposition — sold, not interested, follow up.',
                'match' => function ($row) {
                    $cat = (string) ($row->category ?? '');
                    if ($cat === 'lead_disposition_opportunity') return true;

                    $rule = (string) ($row->rule_code ?? '');
                    return $rule !== '' && str_contains($rule, 'LEAD_DISPOSITION');
                },
            ],

            'p1_notes_followup' => [
                'priority' => 1,
                'title' => 'Follow-ups found in notes',
                'why' => 'These are “forgotten” buying signals and service saves hiding in written notes.',
                'next' => 'Open each item and execute the follow-up.',
                'match' => function ($row) {
                    if ((string) ($row->source_type ?? '') === 'note_index') {
                        return true;
                    }

                    $cat = (string) ($row->category ?? '');
                    if ($cat !== '' && str_starts_with($cat, 'note_')) return true;

                    $rule = (string) ($row->rule_code ?? '');
                    return $rule !== '' && str_contains($rule, 'NOTE');
                },
            ],

            // =========================
            // P2 Bucket
            // =========================
            'p2_policy_reviews' => [
                'priority' => 2,
                'title' => 'Policy reviews due (6-month touch)',
                'why' => 'Keeps clients protected as life changes, reduces lapse/cancellation risk, and creates natural referral opportunities.',
                'next' => 'Open each client and complete a quick policy review. Log a note/task so Gideon knows it’s been handled.',
                'match' => function ($row) {
                    $cat  = strtoupper((string) ($row->category ?? ''));
                    $rule = strtoupper((string) ($row->rule_code ?? ''));

                    if ($cat === 'POLICY_REVIEW_DUE_OPPORTUNITY') return true;
                    if ($cat === 'POLICY_REVIEW_OPPORTUNITY') return true;
                    if ($cat === 'POLICY_REVIEW_DUE') return true;

                    if ($rule !== '' && str_contains($rule, 'POLICY_REVIEW')) return true;
                    if ($rule !== '' && str_contains($rule, 'REVIEW_DUE')) return true;

                    return false;
                },
            ],

            // =========================
            // ✅ P3 Bucket (renamed from old P4)
            // Includes archived Service notes + archived Lead notes
            // =========================
            'p3_recovery' => [
                'priority' => 3,
                'title' => 'Archived re-engagements (notes signals) (P3)',
                'why' => 'These are archived Service contacts and archived Leads where notes suggest “price/coverage/timing” hesitation — worth a soft check-in.',
                'next' => 'Open each record, read the notes, then do a soft check-in + quick re-quote/comparison if appropriate.',
                'match' => function ($row) {
                    $cat = (string) ($row->category ?? '');
                    // ✅ No regression: accept old category too, in case any rows still exist
                    return $cat === 'p3_recovery' || $cat === 'p4_service_recovery';
                },
            ],
        ];

        $counts = array_fill_keys(array_keys($buckets), 0);

        foreach ($rows as $row) {
            foreach ($buckets as $key => $def) {
                if (($def['match'])($row)) {
                    $counts[$key]++;
                    break;
                }
            }
        }

        $out = [];
        foreach ($buckets as $key => $def) {
            $count = (int) ($counts[$key] ?? 0);
            if ($count <= 0) continue;

            $out[] = [
                'bucket' => $key,
                'priority' => (int) $def['priority'],
                'title' => $def['title'],
                'why' => $def['why'],
                'next' => $def['next'],
                'count' => $count,
            ];
        }

        usort($out, function ($a, $b) {
            if ($a['priority'] !== $b['priority']) return $a['priority'] <=> $b['priority'];
            return ($b['count'] ?? 0) <=> ($a['count'] ?? 0);
        });

        return response()->json($out);
    }

    /**
     * Return items for a specific bucket.
     */
    public function groupItems(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->agency_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $agencyId = (int) $user->agency_id;
        $bucket = (string) $request->query('bucket', '');

        if ($bucket === '') {
            return response()->json(['message' => 'Missing bucket'], 422);
        }

        $defs = [
            // =========================
            // P1 Buckets
            // =========================
            'p1_bec' => [
                'priority' => 1,
                'title' => 'Beneficiary & Emergency Contact fixes',
                'why' => 'This prevents claims chaos and reduces cancellations when clients go dark.',
                'next' => 'Open each client and add/fix beneficiaries and emergency contacts.',
                'filter' => function ($q) {
                    $q->where('category', 'beneficiary_emergency_opportunity');
                },
            ],

            'p1_notes_followup' => [
                'priority' => 1,
                'title' => 'Follow-ups found in notes',
                'why' => 'These are “forgotten” buying signals and service saves hiding in written notes.',
                'next' => 'Open each item and execute the follow-up.',
                'filter' => function ($q) {
                    $q->where('source_type', 'note_index');
                },
            ],

            'p1_leads_disposition' => [
                'priority' => 1,
                'title' => 'Leads need disposition',
                'why' => 'These leads have been sitting in your CRM for 14+ days without a final outcome. Undispositioned leads slow follow-up, distort pipeline reports, and cause real opportunities to slip through the cracks.',
                'next' => 'Review each lead and set a final disposition — sold, not interested, follow up.',
                'filter' => function ($q) {
                    $q->where(function ($qq) {
                        $qq->where('category', 'lead_disposition_opportunity')
                           ->orWhere('rule_code', 'LIKE', '%LEAD_DISPOSITION%');
                    });
                },
            ],

            // =========================
            // P2 Bucket
            // =========================
            'p2_policy_reviews' => [
                'priority' => 2,
                'title' => 'Policy reviews due (6-month touch)',
                'why' => 'Keeps clients protected as life changes, reduces lapse/cancellation risk, and creates natural referral opportunities.',
                'next' => 'Open each client and complete a quick policy review. Log a note/task so Gideon knows it’s been handled.',
                'filter' => function ($q) {
                    $q->where(function ($qq) {
                        $qq->whereIn('category', [
                            'policy_review_due_opportunity',
                            'policy_review_opportunity',
                            'policy_review_due',
                        ])->orWhere('rule_code', 'LIKE', '%POLICY_REVIEW%')
                          ->orWhere('rule_code', 'LIKE', '%REVIEW_DUE%');
                    });
                },
            ],

            // =========================
            // ✅ P3 Bucket (renamed from old P4)
            // =========================
            'p3_recovery' => [
                'priority' => 3,
                'title' => 'Archived re-engagements (notes signals) (P3)',
                'why' => 'These are archived Service contacts and archived Leads where notes suggest “price/coverage/timing” hesitation — worth a soft check-in.',
                'next' => 'Open each record, read the notes, then do a soft check-in + quick re-quote/comparison if appropriate.',
                'filter' => function ($q) {
                    $q->where(function ($qq) {
                        // ✅ No regression: include old category too
                        $qq->where('category', 'p3_recovery')
                           ->orWhere('category', 'p4_service_recovery');
                    });
                },
            ],
        ];

        if (! isset($defs[$bucket])) {
            return response()->json(['message' => 'Unknown bucket'], 422);
        }

        $query = $this->baseQueryForAgency($agencyId)->where('status', 'open');

        // Apply bucket filter
        ($defs[$bucket]['filter'])($query);

        $opps = $query
            ->orderByDesc('score')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $opps = $this->hydrateEntityLabels($opps);

        $items = $opps->map(function ($opp) {
            $snap = is_array($opp->source_snapshot)
                ? $opp->source_snapshot
                : (json_decode($opp->source_snapshot ?? 'null', true) ?: []);

            $excerpt = (string) ($snap['matched_excerpt'] ?? '');
            $openUrl = $this->buildOpenUrl($opp, $snap);

            return [
                'id' => (int) $opp->id,
                'name' => (string) ($opp->entity_label ?: $this->fallbackEntityName($opp)),
                'entity_type' => (string) ($opp->entity_type ?? ''),
                'entity_id' => $opp->entity_id ? (int) $opp->entity_id : null,
                'title' => (string) ($opp->title ?? ''),
                'recommended_action' => (string) ($opp->recommended_action ?? ''),
                'matched_excerpt' => $excerpt,
                'open_url' => $openUrl,
            ];
        })->values();

        return response()->json([
            'bucket' => $bucket,
            'title' => $defs[$bucket]['title'],
            'why' => $defs[$bucket]['why'],
            'next' => $defs[$bucket]['next'],
            'items' => $items,
        ]);
    }

    public function complete(Request $request, GideonOpportunity $opportunity): JsonResponse
    {
        try {
            $user = $request->user();
            $auth = $this->authorizeOpportunity($user, $opportunity);
            if ($auth !== true) return $auth;

            $opportunity->status = 'completed';

            if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
                $opportunity->snoozed_until = null;
            }

            $opportunity->save();

            return response()->json(['success' => true, 'item' => $opportunity->fresh()]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    public function snooze(Request $request, GideonOpportunity $opportunity): JsonResponse
    {
        try {
            $user = $request->user();
            $auth = $this->authorizeOpportunity($user, $opportunity);
            if ($auth !== true) return $auth;

            $days = (int) ($request->input('days', 7));
            if ($days < 1) $days = 1;
            if ($days > 30) $days = 30;

            $opportunity->status = 'snoozed';

            if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
                $opportunity->snoozed_until = now()->addDays($days);
            }

            $opportunity->save();

            return response()->json(['success' => true, 'item' => $opportunity->fresh()]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    public function unsnooze(Request $request, GideonOpportunity $opportunity): JsonResponse
    {
        try {
            $user = $request->user();
            $auth = $this->authorizeOpportunity($user, $opportunity);
            if ($auth !== true) return $auth;

            $opportunity->status = 'open';

            if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
                $opportunity->snoozed_until = null;
            }

            $opportunity->save();

            return response()->json(['success' => true, 'item' => $opportunity->fresh()]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    public function dismiss(Request $request, GideonOpportunity $opportunity): JsonResponse
    {
        try {
            $user = $request->user();
            $auth = $this->authorizeOpportunity($user, $opportunity);
            if ($auth !== true) return $auth;

            $opportunity->status = 'dismissed';

            if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
                $opportunity->snoozed_until = null;
            }

            $opportunity->save();

            return response()->json(['success' => true, 'item' => $opportunity->fresh()]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    protected function authorizeOpportunity($user, GideonOpportunity $opportunity)
    {
        if (! $user || ! $user->agency_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ((int) $opportunity->agency_id !== (int) $user->agency_id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        return true;
    }

    private function baseQueryForAgency(int $agencyId)
    {
        $query = GideonOpportunity::query()->where('agency_id', $agencyId);

        // Hide currently-snoozed items; show expired snoozes again
        if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
            $query->where(function ($q) {
                $q->whereNull('snoozed_until')
                  ->orWhere('snoozed_until', '<=', now());
            });
        }

        return $query;
    }

    /**
     * Hydrate entity labels for UI display.
     *
     * ✅ supports contacts.full_name in addition to name/first_name/last_name
     * (no regression: still works if full_name does not exist)
     */
    private function hydrateEntityLabels(Collection $opportunities): Collection
    {
        $contactIds = $opportunities
            ->where('entity_type', 'contact')
            ->pluck('entity_id')
            ->filter()
            ->unique()
            ->values();

        $contactNamesById = collect();

        if ($contactIds->isNotEmpty() && Schema::hasTable('contacts')) {
            $columns = Schema::getColumnListing('contacts');

            $hasFullName = in_array('full_name', $columns, true);
            $hasName     = in_array('name', $columns, true);
            $hasFirst    = in_array('first_name', $columns, true);
            $hasLast     = in_array('last_name', $columns, true);

            $select = array_values(array_filter([
                'id',
                $hasFullName ? 'full_name' : null,
                $hasName ? 'name' : null,
                $hasFirst ? 'first_name' : null,
                $hasLast ? 'last_name' : null,
            ]));

            $rows = DB::table('contacts')
                ->whereIn('id', $contactIds)
                ->select($select)
                ->get();

            $contactNamesById = $rows->mapWithKeys(function ($r) use ($hasFullName, $hasName, $hasFirst, $hasLast) {
                $label = null;

                if ($hasFullName && ! empty($r->full_name)) {
                    $label = trim((string) $r->full_name);
                } elseif ($hasName && ! empty($r->name)) {
                    $label = trim((string) $r->name);
                } else {
                    $parts = [];
                    if ($hasFirst && ! empty($r->first_name)) $parts[] = trim((string) $r->first_name);
                    if ($hasLast  && ! empty($r->last_name))  $parts[] = trim((string) $r->last_name);
                    $label = trim(implode(' ', $parts));
                }

                if ($label === '') $label = null;

                return [(int) $r->id => $label];
            });
        }

        return $opportunities->map(function ($opp) use ($contactNamesById) {
            $opp->entity_label = null;

            if ($opp->entity_type === 'contact' && $opp->entity_id) {
                $opp->entity_label = $contactNamesById->get((int) $opp->entity_id);
            }

            $snap = is_array($opp->source_snapshot)
                ? $opp->source_snapshot
                : (json_decode($opp->source_snapshot ?? 'null', true) ?: []);

            // snapshots may contain contact_name; use that as fallback
            if (! $opp->entity_label && is_array($snap)) {
                $opp->entity_label = $snap['contact_name'] ?? null;
            }

            return $opp;
        });
    }

    /**
     * Build an "Open" URL for the UI.
     *
     * ✅ Updated for P3 (renamed from old P4):
     * - P3 can be service OR lead, so we DO NOT force service.
     * - If snapshot is missing contact_type/contact_id, we try to detect contact_type from contacts table.
     * - No regressions: existing snapshot-based routing still wins.
     */
    private function buildOpenUrl($opp, array $snap): string
    {
        // If scanner stored a link, use it.
        if (! empty($snap['open_url']) && is_string($snap['open_url'])) {
            return $snap['open_url'];
        }

        $snapContactId = ! empty($snap['contact_id']) ? (int) $snap['contact_id'] : null;
        $snapContactType = ! empty($snap['contact_type']) ? (string) $snap['contact_type'] : null;

        $category = (string) ($opp->category ?? '');

        // ✅ No-regression: if older P4 rows exist, treat them as P3-ish for routing purposes
        $isP3 = ($category === 'p3_recovery' || $category === 'p4_service_recovery');

        // If we have explicit snapshot routing, use it.
        if ($snapContactId && $snapContactType) {
            try {
                if ($snapContactType === 'book') {
                    if (Route::has('book.open')) return route('book.open', $snapContactId);
                    if (Route::has('book.index')) return route('book.index', ['selected' => $snapContactId]);
                }

                if ($snapContactType === 'service') {
                    if (Route::has('service.open')) return route('service.open', $snapContactId);
                    if (Route::has('service.index')) return route('service.index', ['selected' => $snapContactId]);
                }

                if ($snapContactType === 'lead' && Route::has('leads.show')) {
                    return route('leads.show', $snapContactId);
                }

                if ($snapContactType === 'client') {
                    if (Route::has('book.open')) return route('book.open', $snapContactId);
                    if (Route::has('book.index')) return route('book.index', ['selected' => $snapContactId]);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $entityType = (string) ($opp->entity_type ?? '');
        $entityId = $opp->entity_id ? (int) $opp->entity_id : null;

        if (! $entityId) return '#';

        try {
            // ✅ P3/P4 fallback: if snapshot is missing contact_type, attempt detection from contacts table
            if ($entityType === 'contact' && $isP3 && Schema::hasTable('contacts') && Schema::hasColumn('contacts', 'contact_type')) {
                $detected = DB::table('contacts')->where('id', $entityId)->value('contact_type');
                $detected = is_string($detected) ? strtolower(trim($detected)) : '';

                if ($detected === 'service') {
                    if (Route::has('service.open')) return route('service.open', $entityId);
                    if (Route::has('service.index')) return route('service.index', ['selected' => $entityId]);
                }

                if ($detected === 'lead') {
                    if (Route::has('leads.show')) return route('leads.show', $entityId);
                }

                if ($detected === 'book' || $detected === 'client') {
                    if (Route::has('book.open')) return route('book.open', $entityId);
                    if (Route::has('book.index')) return route('book.index', ['selected' => $entityId]);
                }
            }

            // Default Contacts: prefer Book if that's your primary "open"
            if ($entityType === 'contact') {
                if (Route::has('book.open')) return route('book.open', $entityId);
                if (Route::has('book.index')) return route('book.index', ['selected' => $entityId]);

                // Support service selection too
                if (Route::has('service.index')) return route('service.index', ['selected' => $entityId]);

                if (Route::has('contacts.show')) return route('contacts.show', $entityId);
            }

            if ($entityType === 'lead' && Route::has('leads.show')) {
                return route('leads.show', $entityId);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return '#';
    }

    private function fallbackEntityName($opp): string
    {
        $type = (string) ($opp->entity_type ?? 'Item');
        $id = $opp->entity_id ? (int) $opp->entity_id : (int) $opp->id;
        return ucfirst($type) . ' #' . $id;
    }
}
