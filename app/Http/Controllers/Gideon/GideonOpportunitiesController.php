<?php

namespace App\Http\Controllers\Gideon;

use App\Http\Controllers\Controller;
use App\Models\GideonOpportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GideonOpportunitiesController extends Controller
{
    /**
     * List Gideon opportunities for the authenticated user's agency.
     *
     * SECURITY:
     * - Requires auth:sanctum on the route.
     * - Always scoped by agency_id to prevent cross-tenant leakage.
     *
     * Optional query params:
     * - status   (open|completed|dismissed|snoozed)
     * - category (cross_sell|renewal|referral|revive_lead|test|...)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->agency_id) {
            return response()->json([
                'message' => 'Unauthorized or user has no agency scope.',
            ], 403);
        }

        $query = GideonOpportunity::query()
            ->where('agency_id', $user->agency_id);

        // Optional filters
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        // Basic ordering: hottest first
        $opportunities = $query
            ->orderByDesc('score')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json($opportunities);
    }
}
