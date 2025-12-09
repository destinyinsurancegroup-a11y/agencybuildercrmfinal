<?php

namespace App\Http\Controllers\Gideon;

use App\Http\Controllers\Controller;
use App\Models\GideonOpportunity;
use Illuminate\Http\Request;

class GideonInsightsController extends Controller
{
    /**
     * Gideon "Second Brain" overview for the current agency.
     *
     * - Scopes everything by agency_id
     * - Shows counts, recent activity, and top open opportunities
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->agency_id) {
            abort(403, 'Unauthorized or user has no agency scope.');
        }

        $agencyId = $user->agency_id;

        // Base query scoped by agency
        $baseQuery = GideonOpportunity::where('agency_id', $agencyId);

        // High-level status counts
        $openCount      = (clone $baseQuery)->where('status', 'open')->count();
        $completedCount = (clone $baseQuery)->where('status', 'completed')->count();
        $dismissedCount = (clone $baseQuery)->where('status', 'dismissed')->count();
        $snoozedCount   = (clone $baseQuery)->where('status', 'snoozed')->count();

        // Category distribution (top 6)
        $categories = (clone $baseQuery)
            ->selectRaw("COALESCE(category, 'uncategorized') as category, COUNT(*) as total")
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        // Recent movement (last 7 days)
        $recentNew = (clone $baseQuery)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $recentResolved = (clone $baseQuery)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', now()->subDays(7))
            ->count();

        // Top open opportunities (by score)
        $topOpen = (clone $baseQuery)
            ->where('status', 'open')
            ->orderByDesc('score')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('gideon.second_brain', [
            'user'           => $user,
            'openCount'      => $openCount,
            'completedCount' => $completedCount,
            'dismissedCount' => $dismissedCount,
            'snoozedCount'   => $snoozedCount,
            'categories'     => $categories,
            'recentNew'      => $recentNew,
            'recentResolved' => $recentResolved,
            'topOpen'        => $topOpen,
        ]);
    }
}
