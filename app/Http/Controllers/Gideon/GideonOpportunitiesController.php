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
            // If browser, show 403 page; if json, return json
            if (! $request->expectsJson()) {
                abort(403, 'Unauthorized or user has no agency scope.');
            }

            return response()->json([
                'message' => 'Unauthorized or user has no agency scope.',
            ], 403);
        }

        // ✅ If this is a normal browser page load, return the Blade view
        if (! $request->expectsJson() && ! $request->wantsJson()) {
            // This is your existing file: resources/views/gideon/opportunities/index.blade.php
            return view('gideon.opportunities.index');
        }

        // ✅ Otherwise return JSON list
        $query = $this->baseQueryForAgency((int) $user->agency_id);

        // Optional filters
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

        // Load a small set of fields for grouping fast
        $rows = $base->select(['id', 'category', 'score', 'source_snapshot'])->get();

        // Define buckets (P1 first). We detect based on category + snapshot.
        $buckets = [
            'p1_bec' => [
                'priority' => 1,
                'title' => 'Beneficiary & Emergency Contact fixes',
                'why' => 'Missing or incomplete beneficiary / emergency contact setup causes claim delays and lost retention.',
                'next' => 'Fix the missing info for each person below.',
                'match' => function ($row) {
                    $cat = (string) ($row->category ?? '');
                    if (in_array($cat, ['beneficiary', 'emergency_contact', 'bec'], true)) return true;

                    $snap = is_array($row->source_snapshot) ? $row->source_snapshot : (json_decode($row->source_snapshot ?? 'null', true) ?: []);
                    $rule = (string) ($snap['matched_rule'] ?? '');
                    return str_contains($rule, 'BEC');
                },
            ],
            'p1_notes_followup' => [
                'priority' => 1,
                'title' => 'Follow-ups found in notes',
                'why' => 'These are revenue opportunities mentioned in notes that were not placed on the calendar.',
                'next' => 'Open each record and complete the follow-up.',
                'match' => function ($row) {
                    $snap = is_array($row->source_snapshot) ? $row->source_snapshot : (json_decode($row->source_snapshot ?? 'null', true) ?: []);
                    $sourceType = (string) ($snap['source_type'] ?? '');
                    if ($sourceType === 'note_index') return true;

                    $cat = (string) ($row->category ?? '');
                    if (in_array($cat, ['follow_up', 'note_followup', 'note_opportunity'], true)) return true;

                    $rule = (string) ($snap['matched_rule'] ?? '');
                    return str_contains($rule, 'NOTE');
                },
            ],
        ];

        // Count matches
        $counts = array_fill_keys(array_keys($buckets), 0);

        foreach ($rows as $row) {
            foreach ($buckets as $key => $def) {
                if (($def['match'])($row)) {
                    $counts[$key]++;
                    break;
                }
            }
        }

        // Build response (only non-zero)
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

        // Sort: priority asc, count desc
        usort($out, function ($a, $b) {
            if ($a['priority'] !== $b['priority']) return $a['priority'] <=> $b['priority'];
            return ($b['count'] ?? 0) <=> ($a['count'] ?? 0);
        });

        return response()->json($out);
    }

    /**
     * Return items for a specific bucket.
     *
     * Output shape:
     * {
     *   bucket, title, why, next,
     *   items: [{ id, name, entity_type, entity_id, title, recommended_action, matched_excerpt, open_url }]
     * }
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
                'why' => 'Missing or incomplete beneficiary / emergency contact setup causes claim delays and lost retention.',
                'next' => 'Fix the missing info for each person below.',
                'filter' => function ($q) {
                    $q->whereIn('category', ['beneficiary', 'emergency_contact', 'bec'])
                      ->orWhereJsonContains('source_snapshot->matched_rule', 'BEC'); // safe if json, ignored if not
                },
            ],
            'p1_notes_followup' => [
                'priority' => 1,
                'title' => 'Follow-ups found in notes',
                'why' => 'These are revenue opportunities mentioned in notes that were not placed on the calendar.',
                'next' => 'Open each record and complete the follow-up.',
                'filter' => function ($q) {
                    $q->whereIn('category', ['follow_up', 'note_followup', 'note_opportunity'])
                      ->orWhereJsonContains('source_snapshot->source_type', 'note_index'); // safe if json, ignored if not
                },
            ],
        ];

        if (! isset($defs[$bucket])) {
            return response()->json(['message' => 'Unknown bucket'], 422);
        }

        $query = $this->baseQueryForAgency($agencyId)
            ->where('status', 'open');

        // Apply bucket filter
        ($defs[$bucket]['filter'])($query);

        $opps = $query
            ->orderByDesc('score')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $opps = $this->hydrateEntityLabels($opps);

        $items = $opps->map(function ($opp) {
            $snap = is_array($opp->source_snapshot) ? $opp->source_snapshot : (json_decode($opp->source_snapshot ?? 'null', true) ?: []);
            $excerpt = (string) ($snap['matched_excerpt'] ?? '');

            $openUrl = $this->buildOpenUrl($opp, $snap);

            return [
                'id' => (int) $opp->id,

                // ✅ IMPORTANT: UI expects "name" — we provide real label here
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

    /**
     * Mark an opportunity as completed ("Done").
     */
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

    /**
     * Snooze an opportunity for N days (default 7).
     */
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

    /**
     * Unsnooze an opportunity immediately (optional).
     */
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

    /**
     * Dismiss an opportunity (optional).
     */
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

    /**
     * Shared authorization check (agency scope).
     */
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

    /**
     * Base query with agency scope + snooze visibility rules.
     */
    private function baseQueryForAgency(int $agencyId)
    {
        $query = GideonOpportunity::query()
            ->where('agency_id', $agencyId);

        if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
            $query->where(function ($q) {
                $q->whereNull('snoozed_until')
                  ->orWhere('snoozed_until', '<=', now());
            });
        }

        return $query;
    }

    /**
     * Add entity_label for contacts (and fallback from snapshot contact_name).
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

            $snap = is_array($opp->source_snapshot) ? $opp->source_snapshot : (json_decode($opp->source_snapshot ?? 'null', true) ?: []);

            if (! $opp->entity_label && is_array($snap)) {
                $opp->entity_label = $snap['contact_name'] ?? null;
            }

            return $opp;
        });
    }

    /**
     * Build the "Open" link without exposing tab names.
     * Uses snapshot-provided open_url if present; otherwise safe route guesses.
     */
    private function buildOpenUrl($opp, array $snap): string
    {
        // If scanner stored a link, use it.
        if (! empty($snap['open_url']) && is_string($snap['open_url'])) {
            return $snap['open_url'];
        }

        $entityType = (string) ($opp->entity_type ?? '');
        $entityId = $opp->entity_id ? (int) $opp->entity_id : null;

        if (! $entityId) return '#';

        // Try common routes safely
        try {
            if ($entityType === 'contact' && Route::has('contacts.show')) {
                return route('contacts.show', $entityId);
            }

            if ($entityType === 'lead' && Route::has('leads.show')) {
                return route('leads.show', $entityId);
            }

            // Book/service deep-link helpers exist in your web.php
            if (in_array($entityType, ['client', 'book'], true) && Route::has('book.open')) {
                return route('book.open', $entityId);
            }

            if (in_array($entityType, ['service', 'service_case'], true) && Route::has('service.open')) {
                return route('service.open', $entityId);
            }
        } catch (\Throwable $e) {
            // Never crash UI for link building.
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
