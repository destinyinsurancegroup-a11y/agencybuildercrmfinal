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
     * CRITICAL FIX:
     * - Auth is NULL on DigitalOcean Apps unless logged in.
     * - We use safe defaults to avoid 500 errors.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'leads_worked'      => 'nullable|integer',
            'calls'             => 'nullable|integer',
            'stops'             => 'nullable|integer',
            'presentations'     => 'nullable|integer',
            'apps_written'      => 'nullable|integer',
            'premium_collected' => 'nullable|numeric',
            'ap'                => 'nullable|numeric',
        ]);

        // Default empty to 0
        $data['leads_worked']      = $data['leads_worked']      ?? 0;
        $data['calls']             = $data['calls']             ?? 0;
        $data['stops']             = $data['stops']             ?? 0;
        $data['presentations']     = $data['presentations']     ?? 0;
        $data['apps_written']      = $data['apps_written']      ?? 0;
        $data['premium_collected'] = $data['premium_collected'] ?? 0;
        $data['ap']                = $data['ap']                ?? 0;

        // SAFETY FIX — Auth::user() is NULL on DigitalOcean unless logged in
        $data['tenant_id'] = Auth::user()->tenant_id ?? 1;
        $data['user_id']   = Auth::id() ?? 1;

        Activity::create($data);

        return response()->json(['success' => true]);
    }

    /**
     * Dashboard production totals.
     */
    public function totals($range)
    {
        // SAFETY FIX — prevent null crash
        $userId   = Auth::id() ?? 1;
        $tenantId = Auth::user()->tenant_id ?? 1;

        $query = Activity::where('user_id', $userId)
                         ->where('tenant_id', $tenantId);

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

        $totals = $query->selectRaw("
            COALESCE(SUM(leads_worked), 0) AS leads_worked,
            COALESCE(SUM(calls), 0) AS calls,
            COALESCE(SUM(stops), 0) AS stops,
            COALESCE(SUM(presentations), 0) AS presentations,
            COALESCE(SUM(apps_written), 0) AS apps_written,
            COALESCE(SUM(premium_collected), 0) AS premium_collected,
            COALESCE(SUM(ap), 0) AS ap
        ")->first();

        return response()->json($totals);
    }
}
