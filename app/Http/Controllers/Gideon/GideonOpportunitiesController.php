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
     * ✅ UPDATED:
     * - Combine all P1 buckets into ONE: bucket = "p1_all"
     * - Keep underlying P1 bucket definitions for item retrieval
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

        $rows = $base->select(['id', 'category', 'source_type', 'rule_code'])->get();

        // Same P1 matchers you already had
        $p1Matchers = [
            'p1_bec' => function ($row) {
                return (string) ($row->category ?? '') === 'beneficiary_emergency_opportunity';
            },
            'p1_notes_followup' => function ($row) {
                if ((string) ($row->source_type ?? '') === 'note_index') return true;

                $cat = (string) ($row->category ?? '');
                if (str_starts_with($cat, 'note_')) return true;

                $rule = (string) ($row->rule_code ?? '');
                return $rule !== '' && str_contains($rule, 'NOTE');
            },
            'p1_leads_disposition' => function ($row) {
                $cat = (string) ($row->category ?? '');
                if ($cat === 'lead_disposition_opportunity') return true;

                $rule = (string) ($row->rule_code ?? '');
                return $rule !== '' && str_contains($rule, 'LEAD_DISPOSITION');
            },
        ];

        $p1Total = 0;

        foreach ($rows as $row) {
            foreach ($p1Matchers as $match) {
                if ($match($row)) {
                    $p1Total++;
                    break;
                }
            }
        }

        // Only return ONE P1 row (clean dashboard)
        $out = [];

        if ($p1Total > 0) {
            $out[] = [
                'bucket' => 'p1_all',
                'priority' => 1,
                'title' => 'P1 Critical Actions',
                'why' => 'These are urgent revenue, retention, or service risks that should be handled first.',
                'next' => 'Click “View list” to review the P1 categories and take action.',
                'count' => $p1Total,
            ];
        }

        return response()->json($out);
    }

    /**
     * Return items for a specific bucket.
     *
     * ✅ UPDATED:
     * - bucket=p1_all returns CATEGORY ROWS as items[] (so your current modal renderer shows them)
     * - Clicking a category INSIDE the modal requires a tiny UI hook.
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
                'next' => 'Review each lead and set a final disposition — sold, not interested, nurture, invalid, or reassign if needed.',
                'filter' => function ($q) {
                    $q->where(function ($qq) {
                        $qq->where('category', 'lead_disposition_opportunity')
                           ->orWhere('rule_code', 'LIKE', '%LEAD_DISPOSITION%');
                    });
                },
            ],
        ];

        /**
         * ✅ Combined P1 bucket:
         * Return the CATEGORY LIST as "items" so your existing modal UI displays it (not empty).
         *
         * Each item includes:
         * - bucket_key: which bucket to load next
         * - open_url: points to group-items endpoint for that bucket
         *
         * NOTE: To make clicking the row load the bucket within the modal,
         * the frontend must call the open_url instead of treating it like a normal deep-link.
         */
        if ($bucket === 'p1_all') {
            $sectionOrder = ['p1_bec', 'p1_leads_disposition', 'p1_notes_followup'];

            $categoryItems = collect();

            foreach ($sectionOrder as $key) {
                if (! isset($defs[$key])) continue;

                $query = $this->baseQueryForAgency($agencyId)->where('status', 'open');
                ($defs[$key]['filter'])($query);

                $count = (int) $query->count();

                if ($count <= 0) continue;

                // Use a deterministic negative ID so it never collides with real opportunity IDs
                $fakeId = -1 * (abs(crc32($key)) ?: 1);

                $openUrl = url('/gideon/opportunities/group-items?bucket=' . urlencode($key));

                $categoryItems->push([
                    'id' => $fakeId,
                    'name' => $defs[$key]['title'],
                    'entity_type' => 'gideon_category',
                    'entity_id' => null,
                    'title' => $defs[$key]['title'],
                    'recommended_action' => $defs[$key]['next'],
                    'matched_excerpt' => $defs[$key]['why'],
                    'open_url' => $openUrl,

                    // extra metadata for the UI (safe to ignore)
                    'bucket_key' => $key,
                    'count' => $count,
                ]);
            }

            return response()->json([
                'bucket' => 'p1_all',
                'title' => 'P1 Critical Actions',
                'why' => 'These are urgent revenue, retention, or service risks that should be handled first.',
                'next' => 'Click a category to review its opportunities.',
                'items' => $categoryItems->values(),
            ]);
        }

        // Normal single-bucket behavior (unchanged)
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

            if (! $opp->entity_label && is_array($snap)) {
                $opp->entity_label = $snap['contact_name'] ?? null;
            }

            return $opp;
        });
    }

    private function buildOpenUrl($opp, array $snap): string
    {
        if (! empty($snap['open_url']) && is_string($snap['open_url'])) {
            return $snap['open_url'];
        }

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
