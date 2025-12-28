<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Gideon\OpportunityScanner;
use App\Models\GideonOpportunity;

class GideonScanController extends Controller
{
    /**
     * Quick Scan (default): lightweight, fast rules.
     */
    public function scan(Request $request, OpportunityScanner $scanner)
    {
        $user = Auth::user();
        if (!$user) abort(403);

        $agencyId = $user->agency_id ?? 1;

        $result = $scanner->run($agencyId, $user->id, false);

        return response()->json([
            'success' => true,
            'mode' => 'quick',
            'result' => $result,
            'open_count' => GideonOpportunity::where('agency_id', $agencyId)->where('status', 'open')->count(),
        ]);
    }

    /**
     * Deep Scan: heavier rules (notes scanning, partner/title sweep, apartment sweep, etc.)
     */
    public function deepScan(Request $request, OpportunityScanner $scanner)
    {
        $user = Auth::user();
        if (!$user) abort(403);

        $agencyId = $user->agency_id ?? 1;

        $result = $scanner->run($agencyId, $user->id, true);

        return response()->json([
            'success' => true,
            'mode' => 'deep',
            'result' => $result,
            'open_count' => GideonOpportunity::where('agency_id', $agencyId)->where('status', 'open')->count(),
        ]);
    }

    /**
     * For the dashboard card: return top 5 opportunities (already scored).
     */
    public function top(Request $request)
    {
        $user = Auth::user();
        if (!$user) abort(403);

        $agencyId = $user->agency_id ?? 1;

        $opps = GideonOpportunity::where('agency_id', $agencyId)
            ->where('status', 'open')
            ->orderByDesc('score')
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'items' => $opps,
        ]);
    }
}
