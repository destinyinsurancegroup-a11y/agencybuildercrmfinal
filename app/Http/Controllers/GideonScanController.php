<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Services\Gideon\OpportunityScanner;
use App\Models\GideonOpportunity;

class GideonScanController extends Controller
{
    /**
     * Quick Scan (default): lightweight, fast rules.
     * POST /gideon/scan
     */
    public function scan(Request $request, OpportunityScanner $scanner)
    {
        return $this->runScan($scanner, false);
    }

    /**
     * Deep Scan: heavier rules (notes scanning, partner/title sweep, apartment sweep, etc.)
     * POST /gideon/scan/deep
     */
    public function deepScan(Request $request, OpportunityScanner $scanner)
    {
        return $this->runScan($scanner, true);
    }

    /**
     * For the dashboard card: return top 5 opportunities (already scored).
     * GET /gideon/top
     *
     * ✅ Updated:
     * - Show OPEN items
     * - Show SNOOZED items ONLY when snoozed_until <= now() (i.e., snooze expired)
     * - If snoozed_until column doesn't exist yet, safely falls back to OPEN only
     */
    public function top(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        $agencyId = (int) ($user->agency_id ?? 0);
        if ($agencyId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'User has no agency_id; cannot scope Gideon results.',
                'items'   => [],
            ], 422);
        }

        $now = now();

        $query = GideonOpportunity::query()
            ->where('agency_id', $agencyId);

        // If your table has user_id, keep results user-specific.
        if (Schema::hasColumn('gideon_opportunities', 'user_id')) {
            $query->where('user_id', $user->id);
        }

        // ✅ Status logic with safe fallback if snoozed_until doesn't exist yet
        if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
            $query->where(function ($q) use ($now) {
                $q->where('status', 'open')
                  ->orWhere(function ($q2) use ($now) {
                      $q2->where('status', 'snoozed')
                         ->whereNotNull('snoozed_until')
                         ->where('snoozed_until', '<=', $now);
                  });
            });
        } else {
            // Hard-safe fallback
            $query->where('status', 'open');
        }

        $items = $query
            ->orderByDesc('score')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'items'   => $items,
        ]);
    }

    // ---------------------------------------------------------------------
    // Internal
    // ---------------------------------------------------------------------

    /**
     * Shared handler for scan + deep scan.
     */
    protected function runScan(OpportunityScanner $scanner, bool $deep)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        $agencyId = (int) ($user->agency_id ?? 0);
        if ($agencyId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'User has no agency_id; cannot run Gideon scan.',
            ], 422);
        }

        try {
            $result = $scanner->run($agencyId, (int) $user->id, $deep);

            // Count "actionable" items the same way the dashboard shows them
            $now = now();

            $countQuery = GideonOpportunity::query()
                ->where('agency_id', $agencyId);

            if (Schema::hasColumn('gideon_opportunities', 'user_id')) {
                $countQuery->where('user_id', $user->id);
            }

            if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
                $countQuery->where(function ($q) use ($now) {
                    $q->where('status', 'open')
                      ->orWhere(function ($q2) use ($now) {
                          $q2->where('status', 'snoozed')
                             ->whereNotNull('snoozed_until')
                             ->where('snoozed_until', '<=', $now);
                      });
                });
            } else {
                $countQuery->where('status', 'open');
            }

            $openCount = (int) $countQuery->count();

            return response()->json([
                'success'    => true,
                'mode'       => $deep ? 'deep' : 'quick',
                'result'     => $result,     // { created:int, message:string }
                'open_count' => $openCount,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gideon scan failed. Check laravel.log for details.',
            ], 500);
        }
    }
}
