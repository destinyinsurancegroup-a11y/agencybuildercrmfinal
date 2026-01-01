<?php

namespace App\Http\Controllers\Gideon;

use App\Http\Controllers\Controller;
use App\Models\GideonOpportunity;
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
     * IMPORTANT:
     * - Default is OPEN-only (so Done/Snoozed disappear immediately from the list).
     * - Snoozed items only re-appear after snoozed_until has passed.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->agency_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized or user has no agency scope.',
            ], 403);
        }

        $hasSnooze = Schema::hasColumn('gideon_opportunities', 'snoozed_until');

        $query = GideonOpportunity::query()
            ->where('agency_id', $user->agency_id);

        // Optional user scoping (if column exists)
        if (Schema::hasColumn('gideon_opportunities', 'user_id')) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('user_id')->orWhere('user_id', $user->id);
            });
        }

        // If snoozed_until exists, hide items that are snoozed into the future
        if ($hasSnooze) {
            $query->where(function ($q) {
                $q->whereNull('snoozed_until')
                  ->orWhere('snoozed_until', '<=', now());
            });
        }

        // Filters
        $status   = $request->query('status');
        $category = $request->query('category');

        /**
         * ✅ KEY FIX:
         * If no status filter was provided, default to OPEN-only.
         * This guarantees Done/Snoozed items disappear from the UI immediately.
         *
         * Also treat "expired snoozed" as open again.
         */
        if ($status) {
            $query->where('status', $status);
        } else {
            $query->where(function ($q) use ($hasSnooze) {
                $q->whereNull('status')
                  ->orWhere('status', 'open');

                if ($hasSnooze) {
                    // If status is still "snoozed" but snoozed_until has passed,
                    // allow it to show again in the "open" view.
                    $q->orWhere(function ($w) {
                        $w->where('status', 'snoozed')
                          ->where('snoozed_until', '<=', now());
                    });
                }
            });
        }

        if ($category) {
            $query->where('category', $category);
        }

        $opportunities = $query
            ->orderByDesc('score')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'items'   => $opportunities,
        ]);
    }

    /**
     * Mark an opportunity as completed ("Done").
     */
    public function complete(Request $request, GideonOpportunity $opportunity): JsonResponse
    {
        return $this->guardedUpdate($request, $opportunity, function (GideonOpportunity $opp) {
            $opp->status = 'completed';

            if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
                $opp->snoozed_until = null;
            }

            $opp->save();
        });
    }

    /**
     * ✅ Alias for older front-end buttons that POST to /done instead of /complete
     */
    public function done(Request $request, GideonOpportunity $opportunity): JsonResponse
    {
        return $this->complete($request, $opportunity);
    }

    /**
     * Snooze an opportunity for N days (default 7).
     */
    public function snooze(Request $request, GideonOpportunity $opportunity): JsonResponse
    {
        return $this->guardedUpdate($request, $opportunity, function (GideonOpportunity $opp) use ($request) {
            $days = (int) ($request->input('days', 7));
            if ($days < 1) $days = 1;
            if ($days > 30) $days = 30;

            $opp->status = 'snoozed';

            if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
                $opp->snoozed_until = now()->addDays($days);
            }

            $opp->save();
        });
    }

    /**
     * Unsnooze: back to open immediately.
     */
    public function unsnooze(Request $request, GideonOpportunity $opportunity): JsonResponse
    {
        return $this->guardedUpdate($request, $opportunity, function (GideonOpportunity $opp) {
            $opp->status = 'open';

            if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
                $opp->snoozed_until = null;
            }

            $opp->save();
        });
    }

    /**
     * Dismiss: hides from default open list.
     */
    public function dismiss(Request $request, GideonOpportunity $opportunity): JsonResponse
    {
        return $this->guardedUpdate($request, $opportunity, function (GideonOpportunity $opp) {
            $opp->status = 'dismissed';

            if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
                $opp->snoozed_until = null;
            }

            $opp->save();
        });
    }

    // ------------------------------------------------------------------
    // Internal helper
    // ------------------------------------------------------------------

    protected function guardedUpdate(Request $request, GideonOpportunity $opportunity, callable $mutator): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->agency_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ((int) $opportunity->agency_id !== (int) $user->agency_id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        // Optional user scoping (if you store user_id on the row)
        if (Schema::hasColumn('gideon_opportunities', 'user_id') && $opportunity->user_id) {
            if ((int) $opportunity->user_id !== (int) $user->id) {
                return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
            }
        }

        try {
            $mutator($opportunity);

            return response()->json([
                'success' => true,
                'item'    => $opportunity->fresh(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Update failed. Check laravel.log.',
            ], 500);
        }
    }
}
