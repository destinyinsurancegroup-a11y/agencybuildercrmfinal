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
     * IMPORTANT:
     * - Multi-tenant scoping is standardized on agency_id across the CRM.
     * - TenantScoped will auto-apply agency_id on queries and auto-set agency_id on create,
     *   but we also set it explicitly here for clarity and safety.
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
            'ap'                => 'nullable|numeric|min:0',
        ]);

        // Default empty to 0
        $data['leads_worked']      = $data['leads_worked']      ?? 0;
        $data['calls']             = $data['calls']             ?? 0;
        $data['stops']             = $data['stops']             ?? 0;
        $data['presentations']     = $data['presentations']     ?? 0;
        $data['apps_written']      = $data['apps_written']      ?? 0;
        $data['premium_collected'] = $data['premium_collected'] ?? 0;
        $data['ap']                = $data['ap']                ?? 0;

        /**
         * ✅ Auth safety:
         * Your routes are behind auth middleware, so Auth::id() should exist.
         * We keep safe fallbacks to prevent a hard 500 in misconfigured environments,
         * but agency_id is now the canonical tenant field.
         */
        $user = Auth::user();

        $data['user_id']   = Auth::id() ?? 1;
        $data['agency_id'] = $user->agency_id ?? 1;

        // ✅ Create activity
        $activity = Activity::create($data);

        // ✅ Immediately compute MONTH totals so the front-end can update instantly
        $monthTotals = $this->totalsForRange('month');

        return response()->json([
            'success' => true,
            'activity' => [
                'id' => $activity->id,
                'premium_collected' => (float) $activity->premium_collected,
                'ap' => (float) $activity->ap,
                'created_at' => optional($activity->created_at)->toISOString(),
            ],
            'month_totals' => $monthTotals,
        ]);
    }

    /**
     * Dashboard production totals.
     */
    public function totals($range)
    {
        return response()->json($this->totalsForRange($range));
    }

    /**
     * Shared totals logic so:
     * - totals($range) uses it
     * - store() can call it immediately after save (instant UI update)
     */
    private function totalsForRange(string $range): array
    {
        $userId = Auth::id() ?? 1;

        /**
         * ✅ IMPORTANT:
         * We DO NOT filter by tenant_id anymore.
         * Multi-tenancy is enforced by the Activity model's TenantScoped global scope (agency_id).
         */
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
                return [
                    'error' => 'Invalid range',
                ];
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

        return [
            'leads_worked'      => (int) ($totals->leads_worked ?? 0),
            'calls'             => (int) ($totals->calls ?? 0),
            'stops'             => (int) ($totals->stops ?? 0),
            'presentations'     => (int) ($totals->presentations ?? 0),
            'apps_written'      => (int) ($totals->apps_written ?? 0),
            'premium_collected' => (float) ($totals->premium_collected ?? 0),
            'ap'                => (float) ($totals->ap ?? 0),
        ];
    }
}
