<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;
use Carbon\Carbon;
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
     * OPTION A (INSTANT UI):
     * - Save activity
     * - Compute fresh totals server-side (month + all ranges)
     * - Return totals in the SAME response so dashboard updates instantly (no waiting on /activity/totals)
     *
     * AP rules:
     * - AP must ALWAYS be premium_collected * 12 (server-side)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'activity_date'     => 'nullable|date',
            'leads_worked'      => 'nullable|integer|min:0',
            'calls'             => 'nullable|integer|min:0',
            'stops'             => 'nullable|integer|min:0',
            'presentations'     => 'nullable|integer|min:0',
            'apps_written'      => 'nullable|integer|min:0',
            'premium_collected' => 'nullable|numeric|min:0',
        ]);

        // Default empty to 0
        $data['leads_worked']      = $data['leads_worked']      ?? 0;
        $data['calls']             = $data['calls']             ?? 0;
        $data['stops']             = $data['stops']             ?? 0;
        $data['presentations']     = $data['presentations']     ?? 0;
        $data['apps_written']      = $data['apps_written']      ?? 0;
        $data['premium_collected'] = $data['premium_collected'] ?? 0;

        // ✅ ALWAYS compute AP from premium (Premium * 12)
        $data['ap'] = round(((float) $data['premium_collected']) * 12, 2);

        // Auth + tenant
        $user = Auth::user();
        $data['user_id']   = Auth::id() ?? 1;
        $data['agency_id'] = $user->agency_id ?? 1;

        // If user picked a date, set created_at to that date (so totals align with the selected day/week/month)
        $activityDate = $data['activity_date'] ?? null;
        unset($data['activity_date']);

        $activity = new Activity($data);

        if ($activityDate) {
            // Use the provided date, keep current time-of-day in app timezone
            $nowTz = Carbon::now();
            $dt = Carbon::parse($activityDate, $nowTz->getTimezone())
                ->setTime($nowTz->hour, $nowTz->minute, $nowTz->second);

            $activity->created_at = $dt;
            $activity->updated_at = $dt;
        }

        $activity->save();

        // ✅ Compute fresh totals RIGHT NOW (this is what makes the dashboard instant)
        $userId = $data['user_id'];

        $ranges = ['day', 'week', 'month', 'quarter', 'year'];
        $totalsByRange = [];
        foreach ($ranges as $range) {
            $totalsByRange[$range] = $this->computeTotalsForRange($userId, $range);
        }

        return response()->json([
            'success' => true,
            'saved' => [
                'leads_worked'      => (int) $activity->leads_worked,
                'calls'             => (int) $activity->calls,
                'stops'             => (int) $activity->stops,
                'presentations'     => (int) $activity->presentations,
                'apps_written'      => (int) $activity->apps_written,
                'premium_collected' => (float) $activity->premium_collected,
                'ap'                => (float) round(((float) $activity->premium_collected) * 12, 2),
                'created_at'        => optional($activity->created_at)->toIso8601String(),
            ],
            // This is the payload the dashboard uses to update instantly:
            'month_totals'    => $totalsByRange['month'],
            'totals_by_range' => $totalsByRange,
        ]);
    }

    /**
     * Dashboard production totals.
     *
     * AP totals should be SUM(premium_collected) * 12
     * (so old/wrong stored ap values do NOT pollute totals)
     */
    public function totals($range)
    {
        $userId = Auth::id() ?? 1;
        $totals = $this->computeTotalsForRange($userId, $range);

        if ($totals === null) {
            return response()->json(['error' => 'Invalid range'], 400);
        }

        return response()->json($totals);
    }

    /**
     * Compute totals for a user + range.
     * Returns associative array matching dashboard expectations.
     */
    private function computeTotalsForRange(int $userId, string $range): ?array
    {
        $query = Activity::where('user_id', $userId);

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

            default:
                return null;
        }

        $row = $query->selectRaw("
            COALESCE(SUM(leads_worked), 0) AS leads_worked,
            COALESCE(SUM(calls), 0) AS calls,
            COALESCE(SUM(stops), 0) AS stops,
            COALESCE(SUM(presentations), 0) AS presentations,
            COALESCE(SUM(apps_written), 0) AS apps_written,
            COALESCE(SUM(premium_collected), 0) AS premium_collected,
            COALESCE(SUM(premium_collected) * 12, 0) AS ap
        ")->first();

        return [
            'leads_worked'      => (int) ($row->leads_worked ?? 0),
            'calls'             => (int) ($row->calls ?? 0),
            'stops'             => (int) ($row->stops ?? 0),
            'presentations'     => (int) ($row->presentations ?? 0),
            'apps_written'      => (int) ($row->apps_written ?? 0),
            'premium_collected' => (float) ($row->premium_collected ?? 0),
            'ap'                => (float) ($row->ap ?? 0),
        ];
    }
}
