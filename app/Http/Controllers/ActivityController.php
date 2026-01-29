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
     * Guarantees:
     * - Tenant isolation (tenant_id) is enforced explicitly.
     * - AP is computed server-side: premium_collected * 12
     * - Prevents accidental double POSTs (same payload within 3 seconds).
     * - Returns month_totals so dashboard can update instantly.
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

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $userId = (int) $user->id;

        // ✅ Canonical tenant key used by TenantScoped (tenant_id column)
        // Tenant::id() maps to $user->agency_id, so this stays consistent everywhere.
        $tenantId = (int) ($user->agency_id ?? 0);
        if ($tenantId <= 0) {
            // Hard fail: activities table tenant_id is NOT NULL in migrations
            return response()->json(['success' => false, 'message' => 'Tenant not set for user'], 403);
        }

        // Normalize empty to 0
        $leadsWorked      = (int)($data['leads_worked'] ?? 0);
        $calls            = (int)($data['calls'] ?? 0);
        $stops            = (int)($data['stops'] ?? 0);
        $presentations    = (int)($data['presentations'] ?? 0);
        $appsWritten      = (int)($data['apps_written'] ?? 0);
        $premiumCollected = (float)($data['premium_collected'] ?? 0);

        // ✅ ALWAYS compute AP from premium (Premium * 12)
        $ap = round($premiumCollected * 12, 2);

        /**
         * Optional: If you want the "Date" field in the modal to affect reporting,
         * you can set created_at to that day.
         * Keeping time "now" so you can still see the exact save time.
         */
        $createdAt = now();
        if (!empty($data['activity_date'])) {
            $d = Carbon::parse($data['activity_date']);
            $createdAt = $d->setTimeFromTimeString(now()->format('H:i:s'));
        }

        // ✅ "No migration" dedupe: same payload within 3 seconds
        $recentWindowStart = now()->subSeconds(3);

        $duplicate = Activity::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('created_at', '>=', $recentWindowStart)
            ->where('leads_worked', $leadsWorked)
            ->where('calls', $calls)
            ->where('stops', $stops)
            ->where('presentations', $presentations)
            ->where('apps_written', $appsWritten)
            ->where('premium_collected', $premiumCollected)
            ->exists();

        if (!$duplicate) {
            Activity::create([
                'tenant_id'          => $tenantId,
                'user_id'            => $userId,
                'leads_worked'       => $leadsWorked,
                'calls'              => $calls,
                'stops'              => $stops,
                'presentations'      => $presentations,
                'apps_written'       => $appsWritten,
                'premium_collected'  => $premiumCollected,
                'ap'                 => $ap,
                'created_at'         => $createdAt,
            ]);
        }

        $monthTotals = $this->computeTotalsForUserRange($tenantId, $userId, 'month');

        return response()->json([
            'success' => true,
            'deduped' => $duplicate,
            'saved' => [
                'premium_collected' => $premiumCollected,
                'ap' => $ap,
            ],
            'month_totals' => $monthTotals,
        ]);
    }

    /**
     * Dashboard production totals.
     *
     * NOTE:
     * - Explicit tenant_id scoping to guarantee isolation and correct totals.
     * - AP totals computed as SUM(premium_collected) * 12
     *   so stored ap values never pollute totals.
     */
    public function totals($range)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $tenantId = (int) ($user->agency_id ?? 0);
        if ($tenantId <= 0) {
            return response()->json(['error' => 'Tenant not set for user'], 403);
        }

        $userId = (int) $user->id;

        $totals = $this->computeTotalsForUserRange($tenantId, $userId, (string)$range);
        if (isset($totals['error'])) {
            return response()->json($totals, 400);
        }

        return response()->json($totals);
    }

    private function computeTotalsForUserRange(int $tenantId, int $userId, string $range): array
    {
        // Use withoutGlobalScopes + explicit tenant filter to avoid any scope/middleware weirdness.
        $query = Activity::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId);

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
