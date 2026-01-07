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
     * List Gideon opportunities for the authenticated user's agency.
     *
     * Optional query params:
     * - status   (open|completed|dismissed|snoozed)
     * - category (... or comma-separated list)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->agency_id) {
            return response()->json([
                'message' => 'Unauthorized or user has no agency scope.',
            ], 403);
        }

        $query = GideonOpportunity::query()
            ->where('agency_id', (int) $user->agency_id);

        // If snoozed_until exists, treat expired snoozes as visible again for list calls
        if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
            $query->where(function ($q) {
                $q->whereNull('snoozed_until')
                  ->orWhere('snoozed_until', '<=', now());
            });
        }

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

        // Basic ordering: hottest first
        $opportunities = $query
            ->orderByDesc('score')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        // -----------------------------
        // HYDRATE entity_label (client name)
        // -----------------------------
        $contactIds = $opportunities
            ->where('entity_type', 'contact')
            ->pluck('entity_id')
            ->filter()
            ->unique()
            ->values();

        $contactNamesById = collect();

        if ($contactIds->isNotEmpty() && Schema::hasTable('contacts')) {
            // Try common contact name patterns safely.
            $columns = Schema::getColumnListing('contacts');

            $hasFirst = in_array('first_name', $columns, true);
            $hasLast  = in_array('last_name', $columns, true);
            $hasName  = in_array('name', $columns, true);

            $rows = DB::table('contacts')
                ->whereIn('id', $contactIds)
                ->select(array_values(array_filter([
                    'id',
                    $hasName ? 'name' : null,
                    $hasFirst ? 'first_name' : null,
                    $hasLast ? 'last_name' : null,
                ])))
                ->get();

            $contactNamesById = $rows->mapWithKeys(function ($r) use ($hasName, $hasFirst, $hasLast) {
                $label = null;

                if ($hasName && !empty($r->name)) {
                    $label = trim((string) $r->name);
                } else {
                    $parts = [];
                    if ($hasFirst && !empty($r->first_name)) $parts[] = trim((string) $r->first_name);
                    if ($hasLast  && !empty($r->last_name))  $parts[] = trim((string) $r->last_name);
                    $label = trim(implode(' ', $parts));
                }

                if ($label === '') $label = null;

                return [(int) $r->id => $label];
            });
        }

        // Attach a computed field without changing DB schema
        $opportunities->transform(function ($opp) use ($contactNamesById) {
            $opp->entity_label = null;

            if ($opp->entity_type === 'contact' && $opp->entity_id) {
                $opp->entity_label = $contactNamesById->get((int) $opp->entity_id);
            }

            // Fallback: if scanner snapshot already had contact_name
            if (! $opp->entity_label && is_array($opp->source_snapshot)) {
                $opp->entity_label = $opp->source_snapshot['contact_name'] ?? null;
            }

            return $opp;
        });

        return response()->json($opportunities);
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
            if ($days > 30) $days = 30; // guardrail

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
}
