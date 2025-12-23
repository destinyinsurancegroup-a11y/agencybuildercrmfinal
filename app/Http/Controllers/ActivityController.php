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
     * FIXES:
     * - AP is ALWAYS computed server-side: premium_collected * 12
     * - Prevent double-inserts WITHOUT migrations by blocking identical submits
     *   that occur within a short window (e.g., accidental double POST).
     * - Return month_totals so dashboard can update instantly.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'leads_worked'       => 'nullable|integer|min:0',
            'calls'              => 'nullable|integer|min:0',
            'stops'              => 'nullable|integer|min:0',
            'presentations'      => 'nullable|integer|min:0',
            'apps_written'       => 'nullable|integer|min:0',
            'premium_collected'  => 'nullable|numeric|min:0',
            'activity_date'      => 'nullable|date',
        ]);

        // Normalize empty to 0
        $data['leads_worked']      = (int)($data['leads_worked'] ?? 0);
        $data['calls']             = (int)($data['calls'] ?? 0);
        $data['stops']             = (int)($data['stops'] ?? 0);
        $data['presentations']     = (int)($data['presentations'] ?? 0);
        $data['apps_written']      = (int)($data['apps_written'] ?? 0);
        $data['premium_collected'] = (float)($data['premium_collected'] ?? 0);

        // ✅ ALWAYS compute AP from premium (Premium * 12)
        $data['ap'] = round($data['premium_collected'] * 12, 2);

        // Auth + tenant
        $user = Auth::user();
        $userId = Auth::id() ?? 1;
        $agencyId = $user->agency_id ?? 1;

        /**
         * ✅ IMPORTANT:
         * This is the "no-migration" dedupe fix.
         * If the same exact activity payload arrives twice within 3 seconds,
         * do NOT create a 2nd row.
         */
        $recentWindowStart = now()->subSeconds(3);

        $duplicate = Activity::where('user_id', $userId)
            ->where('agency_id', $agencyId)
            ->where('created_at', '>=', $recentWindowStart)
            ->where('leads_worked', $data['leads_worked'])
            ->where('calls', $data['calls'])
            ->where('stops', $data['stops'])
            ->where('presentations', $data['presentations'])
            ->where('apps_written', $data['apps_written'])
            ->where('premium_collected', $data['premium_collected'])
            ->exists();

        if (!$duplicate) {
            Activity::create([
                'leads_worked'      => $data['leads_worked'],
                'calls'             => $data['calls'],
                'stops'             => $data['stops'],
                'presentations'     => $data['presentations'],
                'apps_written'      => $data['apps_written'],
                'premium_collected' => $data['premium_collected'],
                'ap'                => $data['ap'],
                'user_id'           => $userId,
                'agency_id'         => $agencyId,
            ]);
        }

        // ✅ Return month totals for instant UI update (the dashboard uses this)
        $monthTotals = $this->computeTotalsForUserRange($userId, 'month');

        return response()->json([
            'success' => true,
            'deduped' => $duplicate,
            'saved' => [
                'premium_collected' => (float)$data['premium_collected'],
                'ap' => (float)$data['ap'],
            ],
            'month_totals' => $monthTotals,
        ]);
    }

    /**
     * Dashboard production totals.
     *
     * FIX:
     * - AP totals should be SUM(premium_collected) * 12
     *   so old/wrong stored ap values do NOT pollute totals.
     */
    public function totals($range)
    {
        $userId = Auth::id() ?? 1;

        $totals = $this->computeTotalsForUserRange($userId, $range);
        if (isset($totals['error'])) {
            return response()->json($totals, 400);
        }

        return response()->json($totals);
    }

    private function computeTotalsForUserRange(int $userId, string $range): array
    {
        $query = Activity::where('user_id', $userId);
        $now = Carbon::now();

        switch ($range) {
            case 'day':
                $query->whereBetween('created_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()]);
                break;

            case 'week':
                $query->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
                break;

            case 'month':
                $query->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);
                break;

            case 'quarter':
                $query->whereBetween('created_at', [$now->copy()->firstOfQuarter(), $now->copy()->lastOfQuarter()]);
                break;

            case 'year':
                $query->whereBetween('created_at', [$now->copy()->startOfYear(), $now->copy()->endOfYear()]);
                break;

            default:
                return ['error' => 'Invalid range'];
        }

        $totals = $query->selectRaw("
            COALESCE(SUM(leads_worked), 0) AS leads_worked,
            COALESCE(SUM(calls), 0) AS calls,
            COALESCE(SUM(stops), 0) AS stops,
            COALESCE(SUM(presentations), 0) AS presentations,
            COALESCE(SUM(apps_written), 0) AS apps_written,
            COALESCE(SUM(premium_collected), 0) AS premium_collected,
            COALESCE(SUM(premium_collected) * 12, 0) AS ap
        ")->first();

        return [
            'leads_worked'      => (int)$totals->leads_worked,
            'calls'             => (int)$totals->calls,
            'stops'             => (int)$totals->stops,
            'presentations'     => (int)$totals->presentations,
            'apps_written'      => (int)$totals->apps_written,
            'premium_collected' => (float)$totals->premium_collected,
            'ap'                => (float)$totals->ap,
        ];
    }
}
