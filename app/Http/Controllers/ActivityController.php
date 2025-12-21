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
     * FIX:
     * - AP must ALWAYS be calculated as premium_collected * 12 (server-side).
     * - Never trust AP coming from the browser.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'leads_worked'      => 'nullable|integer|min:0',
            'calls'             => 'nullable|integer|min:0',
            'stops'             => 'nullable|integer|min:0',
            'presentations'     => 'nullable|integer|min:0',
            'apps_written'      => 'nullable|integer|min:0',
            'premium_collected' => 'nullable|numeric|min:0',
            // NOTE: we do NOT validate 'ap' anymore because we compute it server-side
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

        Activity::create($data);

        // Optional: return computed values (useful for debugging/UI)
        return response()->json([
            'success' => true,
            'saved' => [
                'premium_collected' => (float) $data['premium_collected'],
                'ap' => (float) $data['ap'],
            ]
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
                return response()->json(['error' => 'Invalid range'], 400);
        }

        // ✅ AP is derived from premium, not stored ap
        $totals = $query->selectRaw("
            COALESCE(SUM(leads_worked), 0) AS leads_worked,
            COALESCE(SUM(calls), 0) AS calls,
            COALESCE(SUM(stops), 0) AS stops,
            COALESCE(SUM(presentations), 0) AS presentations,
            COALESCE(SUM(apps_written), 0) AS apps_written,
            COALESCE(SUM(premium_collected), 0) AS premium_collected,
            COALESCE(SUM(premium_collected) * 12, 0) AS ap
        ")->first();

        return response()->json($totals);
    }
}
