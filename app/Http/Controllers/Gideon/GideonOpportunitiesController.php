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
     *
     * ✅ UPDATED:
     * - Combine all P1 buckets into a single bucket "p1_all"
     * - "p1_all" count = total of all underlying P1 buckets
     * - Underlying buckets still exist for modal item retrieval and action handling
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

        // Define P1 buckets (unchanged), but we'll present them as one "p1_all" group.
        $buckets = [
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
                'next' => 'Review each lead and set a final disposition — sold, follow up, or not interested.',
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
                    if (str_starts_with($cat, 'note_')) return true;

                    $rule = (string) ($row->rule_code ?? '');
                    return $rule !== '' && str_contains($rule, 'NOTE');
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

        // Build child bucket metadata for P1
        $p1Children = [];
        $p1Total = 0;

        foreach ($buckets as $key => $def) {
            $count = (int) ($counts[$key] ?? 0);
            if ($count <= 0) continue;

            $p1Total += $count;

            $p1Children[] = [
                'bucket' => $key,
                'priority' => (int) $def['priority'],
                'title' => $def['title'],
                'why' => $def['why'],
                'next' => $def['next'],
                'count' => $count,
            ];
        }

        // Output only one row for P1 (dashboard stays clean)
        $out = [];

        if ($p1Total > 0) {
            $out[] = [
                'bucket' => 'p1_all',
                'priority' => 1,
                'title' => 'P1 Critical Actions',
                'why' => 'These are urgent revenue, retention, or service risks that should be handled first.',
                'next' => 'Click “View list” to review the P1 sections and take action.',
                'count' => $p1Total,

                // Optional: allows UI to show sub-counts without extra calls (safe to ignore).
                'children' => array_map(function ($c) {
                    return [
                        'bucket' => $c['bucket'],
                        'title' => $c['title'],
                        'count' => (int) $c['count'],
                    ];
                }, $p1Children),
            ];
        }

        // (Future) Add P2/P3/P4 groups here in the same condensed style.

        return response()->json($out);
    }

    /**
     * Return items for a specific bucket.
     *
     * ✅ UPDATED:
     * - Supports "p1_all" bucket, returning sections:
     *   { bucket, title, why, next, sections: [ {title, why, next, count, items: [...]}, ... ] }
     * - Existing per-bucket behavior remains unchanged for p1_bec, p1_leads_disposition, p1_notes_followup
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
                'next' => 'Review each lead and set a final disposition — sold, follow up, or not interested.',
                'filter' => function ($q) {
                    $q->where(function ($qq) {
                        $qq->where('category', 'lead_disposition_opportunity')
                           ->orWhere('rule_code', 'LIKE', '%LEAD_DISPOSITION%');
                    });
                },
            ],
        ];

        /**
         * ✅ NEW: Combined P1 bucket
         * Returns sections so the UI can render the same three blocks you have today,
         * but under one "P1 Critical Actions" header.
         */
        if ($bucket === 'p1_all') {
            $sectionOrder = ['p1_bec', 'p1_leads_disposition', 'p1_notes_followup'];

            $sections = [];

            foreach ($sectionOrder as $key) {
                if (! isset($defs[$key])) continue;

                $query = $this->baseQueryForAgency($agencyId)->where('status', 'open');
                ($defs[$key]['filter'])($query);

                $opps = $query
                    ->orderByDesc('score')
                    ->orderByDesc('id')
                    ->limit(200)
                    ->get();

                if ($opps->isEmpty()) {
                    continue;
                }

                $opps = $this->hydrateEntityLabels($opps);

                $items = $this->mapOppItems($opps);

                $sections[] = [
                    'bucket' => $key,
                    'priority' => (int) $defs[$key]['priority'],
                    'title' => $defs[$key]['title'],
                    'why' => $defs[$key]['why'],
                    'next' => $defs[$key]['next'],
                    'count' => $items->count(),
                    'items' => $items,
                ];
            }

            return response()->json([
                'bucket' => 'p1_all',
                'title' => 'P1 Critical Actions',
                'why' => 'These are urgent revenue, retention, or service risks that should be handled first.',
                'next' => 'Review each section and take action.',
                'sections' => $sections,
            ]);
        }

        // Existing behavior for single buckets
        if (! isset($defs[$bucket])) {
            return response()->json(['message' => 'Unknown bucket'], 422);
        }

        $query = $this->baseQueryForAgency($agencyId)->where('status', 'open');
        ($defs[$bucket]['filter'])($query);

        $opps = $query
            ->orderByDesc('score')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $opps = $this->hydrateEntityLabels($opps);

        $items = $this->mapOppItems($opps);

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

            $hasFirst = in_array('first_name', $columns, true);
            $hasLast  = in_array('last_name', $columns, true);
            $hasName  = in_array('name', $columns, true);

            $select = array_values(array_filter([
                'id',
                $hasName ? 'name' : null,
                $hasFirst ? 'first_name' : null,
                $hasLast ? 'last_name' : null,
            ]));

            $rows = DB::table('contacts')
                ->whereIn('id', $contactIds)
                ->select($select)
                ->get();

            $contactNamesById = $rows->mapWithKeys(function ($r) use ($hasName, $hasFirst, $hasLast) {
                $label = null;

                if ($hasName && ! empty($r->name)) {
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

            // ✅ BEC snapshots already contain contact_name; use that as fallback
            if (! $opp->entity_label && is_array($snap)) {
                $opp->entity_label = $snap['contact_name'] ?? null;
            }

            return $opp;
        });
    }

    private function mapOppItems(Collection $opps): Collection
    {
        return $opps->map(function ($opp) {
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
    }

    private function buildOpenUrl($opp, array $snap): string
    {
        // If scanner stored a link, use it.
        if (! empty($snap['open_url']) && is_string($snap['open_url'])) {
            return $snap['open_url'];
        }

        // BEC snapshots typically store contact_type + contact_id
        $snapContactId = ! empty($snap['contact_id']) ? (int) $snap['contact_id'] : null;
        $snapContactType = ! empty($snap['contact_type']) ? (string) $snap['contact_type'] : null;

        if ($snapContactId && $snapContactType) {
            try {
                if ($snapContactType === 'book' && Route::has('book.open')) {
                    return route('book.open', $snapContactId);
                }
                if ($snapContactType === 'service' && Route::has('service.open')) {
                    return route('service.open', $snapContactId);
                }
                if ($snapContactType === 'lead' && Route::has('leads.show')) {
                    return route('leads.show', $snapContactId);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $entityType = (string) ($opp->entity_type ?? '');
        $entityId = $opp->entity_id ? (int) $opp->entity_id : null;

        if (! $entityId) return '#';

        try {
            // Prefer book.open when available for contacts.
            if ($entityType === 'contact' && Route::has('book.open')) {
                return route('book.open', $entityId);
            }

            if ($entityType === 'contact' && Route::has('contacts.show')) {
                return route('contacts.show', $entityId);
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
