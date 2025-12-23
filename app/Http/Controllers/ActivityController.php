<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    public function index()
    {
        return view('activity.index');
    }

    public function popup()
    {
        return view('activity.popup');
    }

    /**
     * Store a new activity entry.
     *
     * HARD FIX:
     * - AP is always computed server-side as premium_collected * 12
     * - Server-side duplicate guard: prevents "double insert" if request fires twice
     * - Returns month_totals so the dashboard can update instantly without waiting
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'activity_date'     => 'nullable|date', // optional (UI provides it)
            'leads_worked'      => 'nullable|integer|min:0',
            'calls'             => 'nullable|integer|min:0',
            'stops'             => 'nullable|integer|min:0',
            'presentations'     => 'nullable|integer|min:0',
            'apps_written'      => 'nullable|integer|min:0',
            'premium_collected' => 'nullable|numeric|min:0',
        ]);

        // Normalize empties
        $data['leads_worked']      = (int) ($data['leads_worked'] ?? 0);
        $data['calls']             = (int) ($data['calls'] ?? 0);
        $data['stops']             = (int) ($data['stops'] ?? 0);
        $data['presentations']     = (int) ($data['presentations'] ?? 0);
        $data['apps_written']      = (int) ($data['apps_written'] ?? 0);
        $data['premium_collected'] = (float) ($data['premium_collected'] ?? 0);

        // Always compute AP (never trust client)
        $data['ap'] = round($data['premium_collected'] * 12, 2);

        // Auth + tenant
        $user = Auth::user();
        $userId = Auth::id() ?? 1;
        $agencyId = $user->agency_id ?? 1;

        $data['user_id'] = $userId;
        $data['agency_id'] = $agencyId;

        /**
         * ✅ DUPLICATE GUARD (idempotency-like)
         * If the same payload arrives twice within a short window (ex: double click / duplicate JS),
         * we return success WITHOUT creating a second row.
         */
        $windowSeconds = 5;
        $since = now()->subSeconds($windowSeconds);

        $existing = Activity::where('user_id', $userId)
            ->where('agency_id', $agencyId)
            ->where('created_at', '>=', $since)
            ->where('leads_worked', $data['leads_worked'])
            ->where('calls', $data['calls'])
            ->where('stops', $data['stops'])
            ->where('presentations', $data['presentations'])
            ->where('apps_written', $data['apps_written'])
            ->where('premium_collected', $data['premium_collected'])
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            // Return month totals so dashboard updates instantly
            $monthTotals = $this->totalsForUserRange($userId, 'month');

            return response()->json([
                'success' => true,
                'deduped' => true,
                'activity_id' => $existing->id,
                'saved' => [
                    'premium_collected' => (float) $existing->premium_collected,
                    'ap' => round(((float) $existing->premium_collected) * 12, 2),
                ],
                'month_totals' => $monthTotals,
            ]);
        }

        $created = Activity::create($data);

        // Return month totals so dashboard updates instantly
        $monthTotals = $this->totalsForUserRange($userId, 'month');

        return response()->json([
            'success' => true,
            'deduped' => false,
            'activity_id' => $created->id,
            'saved' => [
                'premium_collected' => (float) $data['premium_collected'],
                'ap' => (float) $data['ap'],
            ],
            'month_totals' => $monthTotals,
        ]);
    }

    public function totals($range)
    {
        $userId = Auth::id() ?? 1;

        return response()->json(
            $this->totalsForUserRange($userId, $range)
        );
    }

    /**
     * Shared totals logic so store() can return month_totals instantly.
     * AP totals are derived from premium (SUM(premium_collected) * 12).
     */
    private function totalsForUserRange(int $userId, string $range): array
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
            'leads_worked' => (int) $totals->leads_worked,
            'calls' => (int) $totals->calls,
            'stops' => (int) $totals->stops,
            'presentations' => (int) $totals->presentations,
            'apps_written' => (int) $totals->apps_written,
            'premium_collected' => (float) $totals->premium_collected,
            'ap' => (float) $totals->ap,
        ];
    }
}
