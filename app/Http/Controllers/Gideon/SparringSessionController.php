<?php

namespace App\Http\Controllers\Gideon;

use App\Http\Controllers\Controller;
use App\Services\Gideon\SparringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SparringSessionController extends Controller
{
    public function __construct(
        protected SparringService $sparringService
    ) {
    }

    /**
     * Start a new sparring session.
     *
     * POST /api/gideon/sparring/sessions
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $agencyId = $user->agency_id ?? null;

        $validated = $request->validate([
            'scenario_code' => ['required', 'string'],
            'mode' => ['nullable', 'string', Rule::in(['prospect_simulation', 'agent_demo'])],
        ]);

        $mode = $validated['mode'] ?? 'prospect_simulation';

        $sessionData = $this->sparringService->startSession(
            agencyId: $agencyId,
            userId: $user->id,
            scenarioCode: $validated['scenario_code'],
            mode: $mode,
        );

        return response()->json([
            'status' => 'ok',
            'session' => $sessionData['session'],
            'first_message' => $sessionData['first_message'],
        ]);
    }

    /**
     * Agent sends a message and receives Gideon's reply.
     *
     * POST /api/gideon/sparring/sessions/{session}/message
     */
    public function sendMessage(Request $request, int $session): JsonResponse
    {
        $user = $request->user();
        $agencyId = $user->agency_id ?? null;

        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $result = $this->sparringService->handleAgentMessage(
            sessionId: $session,
            agencyId: $agencyId,
            userId: $user->id,
            agentMessage: $validated['message'],
        );

        return response()->json([
            'status' => 'ok',
            'agent_message' => $result['agent_message'],
            'gideon_reply' => $result['gideon_reply'],
        ]);
    }

    /**
     * End a sparring session (later: trigger assessment logic).
     *
     * POST /api/gideon/sparring/sessions/{session}/end
     */
    public function end(Request $request, int $session): JsonResponse
    {
        $user = $request->user();
        $agencyId = $user->agency_id ?? null;

        $endResult = $this->sparringService->endSession(
            sessionId: $session,
            agencyId: $agencyId,
            userId: $user->id,
        );

        return response()->json([
            'status' => 'ok',
            'session' => $endResult['session'],
            'assessment' => $endResult['assessment'],
        ]);
    }

    /**
     * Fetch session + transcript for review.
     *
     * GET /api/gideon/sparring/sessions/{session}
     */
    public function show(Request $request, int $session): JsonResponse
    {
        $user = $request->user();
        $agencyId = $user->agency_id ?? null;

        $data = $this->sparringService->getSessionTranscript(
            sessionId: $session,
            agencyId: $agencyId,
            userId: $user->id,
        );

        return response()->json([
            'status' => 'ok',
            'session' => $data['session'],
            'messages' => $data['messages'],
        ]);
    }
}
