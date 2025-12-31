<?php

namespace App\Http\Controllers\Gideon;

use App\Http\Controllers\Controller;
use App\Models\GideonOpportunity;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class GideonOpportunitiesController extends Controller
{
    /**
     * List Gideon opportunities for the authenticated user's agency.
     *
     * Optional query params:
     * - status   (open|completed|dismissed|snoozed)
     * - category (...)
     *
     * Snooze behavior (7 days):
     * - We store a datetime in snoozed_until (nullable).
     * - While snoozed_until > now(), the item is hidden from normal lists.
     * - If status=snoozed is requested, we return only currently-snoozed items.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->agency_id) {
            return response()->json([
                'message' => 'Unauthorized or user has no agency scope.',
            ], 403);
        }

        $now = Carbon::now();

        $query = GideonOpportunity::query()
            ->where('agency_id', $user->agency_id);

        $status = $request->query('status');
        $category = $request->query('category');

        // Optional category filter
        if ($category) {
            $query->where('category', $category);
        }

        // Snooze filter (safe: only apply if column exists)
        $hasSnoozedUntil = Schema::hasColumn('gideon_opportunities', 'snoozed_until');

        /**
         * Status handling:
         * - If status=snoozed: show items snoozed_until > now() (regardless of status field)
         * - Else if status provided: filter by status, and hide currently snoozed items
         * - Else (no status): default to open, and hide currently snoozed items
         */
        if ($status === 'snoozed') {
            if ($hasSnoozedUntil) {
                $query->whereNotNull('snoozed_until')
                      ->where('snoozed_until', '>', $now);
            } else {
                // If schema doesn't have snoozed_until yet, fall back to status field.
                $query->where('status', 'snoozed');
            }
        } else {
            // Default status is open if not provided
            $query->where('status', $status ?: 'open');

            // Hide currently snoozed from normal lists
            if ($hasSnoozedUntil) {
                $query->where(function ($q) use ($now) {
                    $q->whereNull('snoozed_until')
                      ->orWhere('snoozed_until', '<=', $now);
                });
            } else {
                // If no snoozed_until column, at least avoid status=snoozed items
                $query->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'snoozed');
                });
            }
        }

        // Basic ordering: hottest first
        $opportunities = $query
            ->orderByDesc('score')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json($opportunities);
    }

    /**
     * Snooze an opportunity for N days (default 7).
     * POST /gideon/opportunities/{opportunity}/snooze
     * Body: { "days": 7 }
     */
    public function snooze(Request $request, GideonOpportunity $opportunity): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->agency_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ((int)$opportunity->agency_id !== (int)$user->agency_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $days = (int) ($request->input('days', 7));
        if ($days < 1) $days = 1;
        if ($days > 30) $days = 30; // safety cap

        // If the column doesn't exist yet, we can't truly snooze-by-time.
        if (! Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
            // Best-effort fallback: set status to snoozed
            $opportunity->status = 'snoozed';
            $opportunity->save();

            return response()->json([
                'success' => true,
                'message' => 'Snoozed (status-only fallback). Add snoozed_until column for timed snooze.',
                'item'    => $opportunity->fresh(),
            ]);
        }

        $opportunity->snoozed_until = Carbon::now()->addDays($days);

        // IMPORTANT: keep status as-is (usually "open") so it will re-appear automatically
        // once snoozed_until expires, without needing another status change.
        $opportunity->save();

        return response()->json([
            'success' => true,
            'message' => "Snoozed for {$days} day(s).",
            'item'    => $opportunity->fresh(),
        ]);
    }

    /**
     * Unsnooze immediately (show again).
     * POST /gideon/opportunities/{opportunity}/unsnooze
     */
    public function unsnooze(Request $request, GideonOpportunity $opportunity): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->agency_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ((int)$opportunity->agency_id !== (int)$user->agency_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
            $opportunity->snoozed_until = null;
        }

        // If you used status-only snoozing earlier, normalize back to open
        if (isset($opportunity->status) && $opportunity->status === 'snoozed') {
            $opportunity->status = 'open';
        }

        $opportunity->save();

        return response()->json([
            'success' => true,
            'message' => 'Unsnoozed.',
            'item'    => $opportunity->fresh(),
        ]);
    }

    /**
     * Mark dismissed (user doesn't want to see it).
     * POST /gideon/opportunities/{opportunity}/dismiss
     */
    public function dismiss(Request $request, GideonOpportunity $opportunity): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->agency_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ((int)$opportunity->agency_id !== (int)$user->agency_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $opportunity->status = 'dismissed';

        if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
            $opportunity->snoozed_until = null;
        }

        $opportunity->save();

        return response()->json([
            'success' => true,
            'message' => 'Dismissed.',
            'item'    => $opportunity->fresh(),
        ]);
    }

    /**
     * Mark completed (resolved).
     * POST /gideon/opportunities/{opportunity}/complete
     */
    public function complete(Request $request, GideonOpportunity $opportunity): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->agency_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ((int)$opportunity->agency_id !== (int)$user->agency_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $opportunity->status = 'completed';

        if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
            $opportunity->snoozed_until = null;
        }

        $opportunity->save();

        return response()->json([
            'success' => true,
            'message' => 'Completed.',
            'item'    => $opportunity->fresh(),
        ]);
    }
}
