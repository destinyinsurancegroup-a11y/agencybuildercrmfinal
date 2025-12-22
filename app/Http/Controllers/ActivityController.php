<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    /**
     * Show the full Activity page (normal web page).
     */
    public function index()
    {
        return view('activity.index');
    }

    /**
     * Load the Activity POPUP modal content.
     */
    public function popup()
    {
        return view('activity.popup');
    }

    /**
     * Store a new activity entry.
     *
     * FIXES:
     * - AP is ALWAYS computed server-side as premium_collected * 12.
     * - Returns month_totals so the Goal Card can update instantly after save.
     * - Uses authenticated user + agency_id (NO unsafe fallbacks like ?? 1).
     * - Supports optional activity_date from the modal; if provided, we stamp created_at to that date.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }
        if (empty($user->agency_id)) {
            return response()->json(['success' => false, 'message' => 'No agency is associated with this user.'], 403);
        }

        $data = $request->validate([
            'activity_date'     => 'nullable|date', // supports modal date picker
            'leads_worked'      => 'nullable|integer|min:0',
            'calls'             => 'nullable|integer|min:0',
            'stops'             => 'nullable|integer|min:0',
            'presentations'     => 'nullable|integer|min:0',
            'apps_written'      => 'nullable|integer|min:0',
            'premium_collected' => 'nullable|numeric|min:0',
            // NOTE: do NOT accept/validate 'ap' from browser; we compute it server-side
        ]);

        // Default empty numeric fields to 0
        $data['leads_worked']      = $data['leads_worked']      ?? 0;
        $data['calls']             = $data['calls']             ?? 0;
        $data['stops']             = $data['stops']             ?? 0;
        $data['presentations']     = $data['presentations']     ?? 0;
        $data['apps_written']      = $data['apps_written']      ?? 0;
        $data['premium_collected'] = $data['premium_collected'] ?? 0;

        // ✅ ALWAYS compute AP from premium (Premium * 12)
        $data['ap'] = round(((float) $data['premium_collected']) * 12, 2);

        // ✅ Auth + agency scope (no insecure fallbacks)
        $data['user_id']   = (int) $user->id;
        $data['agency_id'] = (int) $user->agency_id;

        // ✅ If activity_date is provided, stamp created_at to that local date (keeping current time-of-day).
        // This makes day/week/month totals reflect the date the user selected.
        $createdAt = null;
        if (!empty($data['activity_date'])) {
            $activityDate = Carbon::parse($data['activity_date']);
            $now = Carbon::now();
            $createdAt = $activityDate->copy()->setTime($now->hour, $now->minute, $now->second);
        }

        // Create row (respecting optional created_at override)
        $activity = new Activity();
        $activity->fill($data);
        if ($createdAt) {
            $activity->created_at = $createdAt;
        }
        $activity->save();

        // ✅ Return month totals immediately so dashboard Goal Card can update instantly.
        $monthTotals = $this->computeTotalsForRange('month', $data['user_id'], $data['agency_id']);

        return response()->json([
            'success' => true,
            'saved' => [
                'id' => $activity->id,
                'premium_collected' => (float) $data['premium_collected'],
                'ap' => (float) $data['ap'],
                'created_at' => optional($activity->created_at)->toDateTimeString(),
            ],
            'month_totals' => [
                'leads_worked' => (int) ($monthTotals->leads_worked ?? 0),
                'calls' => (int) ($monthTotals->calls ?? 0),
                'stops' => (int) ($monthTotals->stops ?? 0),
                'presentations' => (int) ($monthTotals->presentations ?? 0),
                'apps_written' => (int) ($monthTotals->apps_written ?? 0),
                'premium_collected' => (float) ($monthTotals->premium_collected ?? 0),
                'ap' => (float) ($monthTotals->ap ?? 0),
            ],
        ]);
    }

    /**
     * Dashboard production totals.
     *
     * FIXES:
     * - Scopes to authenticated user + agency_id (no unsafe ?? 1 fallbacks).
     * - AP totals are derived from premium totals: SUM(premium_collected) * 12
     *   so old/wrong stored ap values do NOT pollute totals.
     */
    public function totals($range)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }
        if (empty($user->agency_id)) {
            return response()->json(['error' => 'No agency is associated with this user.'], 403);
        }

        $range = strtolower((string) $range);
        if (!in_array($range, ['day', 'week', 'month', 'quarter', 'year'], true)) {
            return response()->json(['error' => 'Invalid range'], 400);
        }

        $totals = $this->computeTotalsForRange($range, (int) $user->id, (int) $user->agency_id);

        return response()->json($totals);
    }

    /**
     * Internal helper: compute totals for a given range, scoped to user + agency.
     */
    private function computeTotalsForRange(string $range, int $userId, int $agencyId)
    {
        $query = Activity::query()
            ->where('user_id', $userId)
            ->where('agency_id', $agencyId);

        $now = Carbon::now();

        switch ($range) {
            case 'day':
                $query->whereBetween('created_at', [
                    $now->copy()->startOfDay(),
                    $now->copy()->endOfDay(),
                ]);
                break;

            case 'week':
                $query->whereBetween('created_at', [
                    $now->copy()->startOfWeek(),
                    $now->copy()->endOfWeek(),
                ]);
                break;

            case 'month':
                $query->whereBetween('created_at', [
                    $now->copy()->startOfMonth(),
                    $now->copy()->endOfMonth(),
                ]);
                break;

            case 'quarter':
                $query->whereBetween('created_at', [
                    $now->copy()->firstOfQuarter(),
                    $now->copy()->lastOfQuarter(),
                ]);
                break;

            case 'year':
                $query->whereBetween('created_at', [
                    $now->copy()->startOfYear(),
                    $now->copy()->endOfYear(),
                ]);
                break;
        }

        // ✅ AP is derived from premium, not stored ap
        return $query->selectRaw("
            COALESCE(SUM(leads_worked), 0) AS leads_worked,
            COALESCE(SUM(calls), 0) AS calls,
            COALESCE(SUM(stops), 0) AS stops,
            COALESCE(SUM(presentations), 0) AS presentations,
            COALESCE(SUM(apps_written), 0) AS apps_written,
            COALESCE(SUM(premium_collected), 0) AS premium_collected,
            COALESCE(SUM(premium_collected) * 12, 0) AS ap
        ")->first();
    }
}
