<?php

namespace App\Http\Controllers\Gideon;

use App\Http\Controllers\Controller;
use App\Models\GideonOpportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GideonOpportunitiesController extends Controller
{
    /**
     * IMPORTANT BEHAVIOR:
     * - If the request expects JSON (AJAX/API), return JSON opportunities (existing behavior).
     * - If the request expects HTML (browser), return a real Blade view.
     *
     * This fixes the "View all shows JSON" problem.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->agency_id) {
            // If HTML request, redirect; if JSON request, return JSON.
            if (! $request->expectsJson()) {
                abort(403, 'Unauthorized or user has no agency scope.');
            }

            return response()->json([
                'message' => 'Unauthorized or user has no agency scope.',
            ], 403);
        }

        // If this is a browser navigation (HTML), return a real page.
        if (! $request->expectsJson()) {
            // We pass only minimal data here; the page can fetch groups/items via endpoints below.
            return view('gideon.opportunities.index', [
                'agencyId' => (int) $user->agency_id,
            ]);
        }

        // Otherwise, keep the existing JSON listing behavior (but improved with actionable filtering by default).
        return $this->listJson($request);
    }

    /**
     * JSON list endpoint (existing behavior, improved):
     * - Defaults to actionable items only (open + not snoozed + due if due_at exists)
     * - Optional query params:
     *   - status (open|completed|dismissed|snoozed)
     *   - category
     *   - include_future_due=1  (if you ever want to see future due items)
     */
    public function listJson(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->agency_id) {
            return response()->json([
                'message' => 'Unauthorized or user has no agency scope.',
            ], 403);
        }

        $query = GideonOpportunity::query()
            ->where('agency_id', $user->agency_id);

        // Default status to "open" unless explicitly set
        $status = $request->query('status', 'open');
        if ($status) {
            $query->where('status', $status);
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        // If snoozed_until exists, treat expired snoozes as visible again
        if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
            $query->where(function ($q) {
                $q->whereNull('snoozed_until')
                  ->orWhere('snoozed_until', '<=', now());
            });
        }

        // Option B support: only show due/overdue items unless include_future_due=1
        $includeFuture = (bool) $request->query('include_future_due', false);
        if (! $includeFuture && Schema::hasColumn('gideon_opportunities', 'due_at')) {
            $query->where(function ($q) {
                $q->whereNull('due_at')
                  ->orWhere('due_at', '<=', now());
            });
        }

        $opportunities = $query
            ->orderByDesc('score')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return response()->json($opportunities);
    }

    /**
     * GROUPED SUMMARY endpoint:
     * Returns "one card per group" data.
     *
     * Example output group:
     * - key, pill, title, explanation, why, next, total, category, source_type
     */
    public function groups(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->agency_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $agencyId = (int) $user->agency_id;

        // Actionable filter: open + not snoozed + due (Option B)
        $base = DB::table('gideon_opportunities')
            ->where('agency_id', $agencyId)
            ->where('status', 'open');

        if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
            $base->where(function ($q) {
                $q->whereNull('snoozed_until')
                  ->orWhere('snoozed_until', '<=', now());
            });
        }

        if (Schema::hasColumn('gideon_opportunities', 'due_at')) {
            $base->where(function ($q) {
                $q->whereNull('due_at')
                  ->orWhere('due_at', '<=', now());
            });
        }

        $rows = $base
            ->select([
                'category',
                'source_type',
                DB::raw('COUNT(*) as total'),
                DB::raw('MAX(score) as max_score'),
            ])
            ->groupBy('category', 'source_type')
            ->orderByDesc('max_score')
            ->get();

        $groups = $rows->map(function ($r) {
            $meta = $this->groupMeta((string) $r->category, (string) ($r->source_type ?? ''));

            return [
                'key' => $meta['key'],
                'pill' => $meta['pill'],
                'title' => $meta['title'],
                'explanation' => $meta['explanation'],
                'why' => $meta['why'],
                'next' => $meta['next'],
                'total' => (int) $r->total,
                'category' => (string) $r->category,
                'source_type' => (string) ($r->source_type ?? ''),
            ];
        })->values();

        return response()->json($groups);
    }

    /**
     * GROUP ITEMS endpoint:
     * Given (category, source_type), return the list of names + proof excerpt (for notes).
     *
     * Query params:
     * - category (required)
     * - source_type (optional)
     */
    public function groupItems(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->agency_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $category = (string) $request->query('category', '');
        $sourceType = (string) $request->query('source_type', '');

        if ($category === '') {
            return response()->json(['message' => 'category is required'], 422);
        }

        $agencyId = (int) $user->agency_id;

        $q = DB::table('gideon_opportunities')
            ->leftJoin('contacts', function ($join) {
                $join->on('contacts.id', '=', 'gideon_opportunities.entity_id');
            })
            ->where('gideon_opportunities.agency_id', $agencyId)
            ->where('gideon_opportunities.status', 'open')
            ->where('gideon_opportunities.category', $category);

        if ($sourceType !== '') {
            $q->where('gideon_opportunities.source_type', $sourceType);
        }

        if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
            $q->where(function ($sub) {
                $sub->whereNull('gideon_opportunities.snoozed_until')
                    ->orWhere('gideon_opportunities.snoozed_until', '<=', now());
            });
        }

        if (Schema::hasColumn('gideon_opportunities', 'due_at')) {
            $q->where(function ($sub) {
                $sub->whereNull('gideon_opportunities.due_at')
                    ->orWhere('gideon_opportunities.due_at', '<=', now());
            });
        }

        $items = $q->select([
                'gideon_opportunities.id',
                'gideon_opportunities.entity_type',
                'gideon_opportunities.entity_id',
                'gideon_opportunities.title',
                'gideon_opportunities.short_reason',
                'gideon_opportunities.recommended_action',
                'gideon_opportunities.source_snapshot',
                'gideon_opportunities.score',
                'contacts.first_name',
                'contacts.last_name',
            ])
            ->orderByDesc('gideon_opportunities.score')
            ->orderByDesc('gideon_opportunities.id')
            ->limit(300)
            ->get()
            ->map(function ($row) {
                $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                if ($name === '') {
                    $name = (string) ($row->title ?? 'Opportunity');
                }

                $snapshot = $row->source_snapshot ? json_decode($row->source_snapshot, true) : null;
                $excerpt = is_array($snapshot) ? ($snapshot['matched_excerpt'] ?? null) : null;

                return [
                    'id' => (int) $row->id,
                    'name' => $name,
                    'entity_type' => (string) $row->entity_type,
                    'entity_id' => $row->entity_id,
                    'short_reason' => $row->short_reason,
                    'recommended_action' => $row->recommended_action,
                    'matched_excerpt' => $excerpt,
                ];
            })
            ->values();

        return response()->json($items);
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
     * Unsnooze an opportunity immediately.
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
     * Dismiss an opportunity.
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
     * Manager-style grouped card copy (NO tab names).
     * You can refine these strings later in the Blade layer, but this keeps behavior consistent.
     */
    private function groupMeta(string $category, string $sourceType): array
    {
        // BEC group
        if ($category === 'beneficiary_emergency_opportunity') {
            return [
                'key' => 'bec',
                'pill' => 'P1',
                'title' => 'Beneficiaries & emergency contacts opportunities available',
                'explanation' => 'These clients are missing key protection details.',
                'why' => 'Fixing this reduces ghosting risk and often opens upsell conversations.',
                'next' => 'Click to see names. Open each client and add at least 2 beneficiaries and 1 emergency contact.',
            ];
        }

        // Notes-based follow-ups group (only shows when due)
        if ($sourceType === 'note_index') {
            return [
                'key' => 'note_followups',
                'pill' => 'P1',
                'title' => 'Follow-ups due from notes',
                'explanation' => 'These are real follow-ups found inside notes that are due now.',
                'why' => 'This protects revenue by catching opportunities you wrote down but didn’t schedule.',
                'next' => 'Click to see names and the note excerpt. Contact them and complete the follow-up.',
            ];
        }

        // Fallback
        return [
            'key' => $category . ':' . $sourceType,
            'pill' => 'P2',
            'title' => 'Opportunities available',
            'explanation' => 'Gideon found actionable items that need attention.',
            'why' => 'These are ranked by business impact.',
            'next' => 'Click to review items and take action.',
        ];
    }
}
