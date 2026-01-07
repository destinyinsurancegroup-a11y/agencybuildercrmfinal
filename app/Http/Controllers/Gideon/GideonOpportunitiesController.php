<?php

namespace App\Http\Controllers\Gideon;

use App\Http\Controllers\Controller;
use App\Models\GideonOpportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class GideonOpportunitiesController extends Controller
{
    /**
     * If the request is a browser navigation (not AJAX), return HTML.
     * If it's fetch() / Accept: application/json, return JSON.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->agency_id) {
            abort(403, 'Unauthorized or user has no agency scope.');
        }

        // If user clicked "View all" in browser, they expect a page, not raw JSON.
        if (! $request->expectsJson()) {
            return view('gideon.opportunities.index');
        }

        $query = GideonOpportunity::query()
            ->where('agency_id', $user->agency_id);

        // Treat expired snoozes as visible again
        if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
            $query->where(function ($q) {
                $q->whereNull('snoozed_until')
                  ->orWhere('snoozed_until', '<=', now());
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        $opportunities = $query
            ->orderByDesc('score')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json($opportunities);
    }

    /**
     * STEP 3B: Returns grouped cards for dashboard.
     * Example:
     * - P1 — 15 Beneficiary & Emergency Contact fixes
     * - P1 — 3 Follow-ups found in notes
     */
    public function groups(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->agency_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $query = GideonOpportunity::query()
            ->where('agency_id', $user->agency_id)
            ->where('status', 'open');

        if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
            $query->where(function ($q) {
                $q->whereNull('snoozed_until')
                  ->orWhere('snoozed_until', '<=', now());
            });
        }

        $all = $query->orderByDesc('score')->orderByDesc('id')->get();

        // Grouping rules (LOCKED design):
        // - BEC is a grouped bucket
        // - Note-based opportunities grouped by rule_code bucket (or one bucket)
        $groups = [];

        foreach ($all as $opp) {
            $bucket = $this->bucketKey($opp);

            if (!isset($groups[$bucket])) {
                $meta = $this->bucketMeta($bucket);

                $groups[$bucket] = [
                    'bucket' => $bucket,
                    'priority' => $meta['priority'],
                    'title' => $meta['title'],
                    'why' => $meta['why'],
                    'next' => $meta['next'],
                    'count' => 0,
                    'score_max' => 0,
                ];
            }

            $groups[$bucket]['count']++;
            $groups[$bucket]['score_max'] = max($groups[$bucket]['score_max'], (int)($opp->score ?? 0));
        }

        // Sort: P1 first, then by score_max desc, then count desc
        $groups = array_values($groups);
        usort($groups, function ($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $a['priority'] <=> $b['priority']; // P1 before P2, etc.
            }
            if ($a['score_max'] !== $b['score_max']) {
                return $b['score_max'] <=> $a['score_max'];
            }
            return $b['count'] <=> $a['count'];
        });

        return response()->json($groups);
    }

    /**
     * STEP 3B: Returns the list of items inside a group (names + open links).
     */
    public function groupItems(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->agency_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $bucket = (string) $request->query('bucket', '');
        if ($bucket === '') {
            return response()->json(['message' => 'Missing bucket.'], 422);
        }

        $query = GideonOpportunity::query()
            ->where('agency_id', $user->agency_id)
            ->where('status', 'open');

        if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
            $query->where(function ($q) {
                $q->whereNull('snoozed_until')
                  ->orWhere('snoozed_until', '<=', now());
            });
        }

        $items = $query->orderByDesc('score')->orderByDesc('id')->get()
            ->filter(fn($opp) => $this->bucketKey($opp) === $bucket)
            ->values()
            ->map(function ($opp) {
                $snapshot = is_array($opp->source_snapshot) ? $opp->source_snapshot : (json_decode($opp->source_snapshot ?? '[]', true) ?: []);

                // Prefer snapshot name if present
                $name = $snapshot['contact_name'] ?? $snapshot['lead_name'] ?? $snapshot['client_name'] ?? ('#' . ($opp->entity_id ?? $opp->id));

                return [
                    'id' => $opp->id,
                    'entity_type' => $opp->entity_type,
                    'entity_id' => $opp->entity_id,
                    'name' => $name,
                    'title' => $opp->title,
                    'short_reason' => $opp->short_reason,
                    'recommended_action' => $opp->recommended_action,
                    'score' => (int)($opp->score ?? 0),
                    'open_url' => $this->openUrlFor($opp, $snapshot),
                ];
            });

        $meta = $this->bucketMeta($bucket);

        return response()->json([
            'bucket' => $bucket,
            'priority' => $meta['priority'],
            'title' => $meta['title'],
            'why' => $meta['why'],
            'next' => $meta['next'],
            'items' => $items,
        ]);
    }

    /**
     * Mark an opportunity as completed ("Done").
     */
    public function complete(Request $request, GideonOpportunity $opportunity): JsonResponse
    {
        try {
            $user = $request->user();
            $auth = $this->authorizeOpportunity($user, $opportunity);
            if ($auth !== true) return $auth;

            $opportunity->status = 'completed';

            if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
                $opportunity->snoozed_until = null;
            }

            $opportunity->save();

            return response()->json(['success' => true, 'item' => $opportunity->fresh()]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    /**
     * Snooze an opportunity for N days (default 7).
     */
    public function snooze(Request $request, GideonOpportunity $opportunity): JsonResponse
    {
        try {
            $user = $request->user();
            $auth = $this->authorizeOpportunity($user, $opportunity);
            if ($auth !== true) return $auth;

            $days = (int) ($request->input('days', 7));
            if ($days < 1) $days = 1;
            if ($days > 30) $days = 30;

            $opportunity->status = 'snoozed';

            if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
                $opportunity->snoozed_until = now()->addDays($days);
            }

            $opportunity->save();

            return response()->json(['success' => true, 'item' => $opportunity->fresh()]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    public function unsnooze(Request $request, GideonOpportunity $opportunity): JsonResponse
    {
        try {
            $user = $request->user();
            $auth = $this->authorizeOpportunity($user, $opportunity);
            if ($auth !== true) return $auth;

            $opportunity->status = 'open';

            if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
                $opportunity->snoozed_until = null;
            }

            $opportunity->save();

            return response()->json(['success' => true, 'item' => $opportunity->fresh()]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    public function dismiss(Request $request, GideonOpportunity $opportunity): JsonResponse
    {
        try {
            $user = $request->user();
            $auth = $this->authorizeOpportunity($user, $opportunity);
            if ($auth !== true) return $auth;

            $opportunity->status = 'dismissed';

            if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
                $opportunity->snoozed_until = null;
            }

            $opportunity->save();

            return response()->json(['success' => true, 'item' => $opportunity->fresh()]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    /**
     * Shared authorization check (agency scope).
     */
    protected function authorizeOpportunity($user, GideonOpportunity $opportunity)
    {
        if (! $user || ! $user->agency_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ((int) $opportunity->agency_id !== (int) $user->agency_id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        return true;
    }

    /**
     * Bucket grouping logic (BEC grouped + NOTE grouped).
     */
    private function bucketKey($opp): string
    {
        // BEC bucket
        if (($opp->category ?? '') === 'beneficiary_emergency_opportunity') {
            return 'P1_BEC';
        }

        // Note opportunities bucket
        if (($opp->source_type ?? '') === 'note_index') {
            return 'P1_NOTE';
        }

        // fallback (still grouped so you never spam cards)
        return 'OTHER';
    }

    private function bucketMeta(string $bucket): array
    {
        return match ($bucket) {
            'P1_BEC' => [
                'priority' => 1,
                'title' => 'P1 — Beneficiary & Emergency Contact fixes',
                'why' => 'This prevents claims chaos and reduces cancellations when clients go dark.',
                'next' => 'Open each client and add/fix beneficiaries and emergency contacts.',
            ],
            'P1_NOTE' => [
                'priority' => 1,
                'title' => 'P1 — Follow-ups found in notes',
                'why' => 'These are “forgotten” buying signals and service saves hiding in written notes.',
                'next' => 'Open each item and execute the follow-up.',
            ],
            default => [
                'priority' => 9,
                'title' => 'Other opportunities',
                'why' => 'Items Gideon found that may matter.',
                'next' => 'Review and take action.',
            ],
        };
    }

    private function openUrlFor($opp, array $snapshot): string
    {
        $entityType = (string) ($opp->entity_type ?? '');
        $entityId   = (int) ($opp->entity_id ?? 0);

        // If snapshot tells us book/service context, prefer that
        $contactType = (string) ($snapshot['contact_type'] ?? '');

        if ($entityType === 'contact' || $contactType === 'book') {
            return url("/book/open/{$entityId}");
        }

        if ($contactType === 'service' || $entityType === 'service') {
            return url("/service/open/{$entityId}");
        }

        if ($entityType === 'lead') {
            return url("/leads/{$entityId}");
        }

        // Fallback: contacts show page
        if ($entityType === 'contact') {
            return url("/contacts/{$entityId}");
        }

        return url('/dashboard');
    }
}
