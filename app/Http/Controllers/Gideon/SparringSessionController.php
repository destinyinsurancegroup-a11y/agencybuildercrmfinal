<?php

namespace App\Http\Controllers\Gideon;

use App\Http\Controllers\Controller;
use App\Models\GideonSparringSession;
use App\Services\Gideon\SparringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SparringSessionController extends Controller
{
    public function __construct(
        protected SparringService $sparringService
    ) {
        $this->middleware('auth:sanctum'); // or 'auth' depending on your API setup
    }

    /**
     * Start a new sparring session.
     *
     * POST /api/gideon/sparring/sessions
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $agencyId = $user->agency_id;

        $validated = $request->validate([
            'scenario_code' => ['required', 'string'],
            'mode' => ['nullable', 'string', Rule::in(['prospect_simulation', 'agent_simulation'])],
            'persona' => ['nullable', 'string'],
        ]);

        $mode = $validated['mode'] ?? 'prospect_simulation';

        $sessionData = $this->sparringService->startSession(
            agencyId: $agencyId,
            userId: $user->id,
            scenarioCode: $validated['scenario_code'],
            mode: $mode,
            personaKey: $validated['persona'] ?? null,
        );

        // Policy check: user should own created session
        $this->authorize('view', $sessionData['session']);

        return response()->json([
            'status' => 'ok',
            'session' => $sessionData['session'],
            'first_message' => $sessionData['first_message'] ?? null,
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
        $agencyId = $user->agency_id;

        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        // Tenant scope + authorize (prevents ID guessing)
        $sessionModel = GideonSparringSession::query()
            ->whereKey($session)
            ->where('agency_id', $agencyId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->authorize('update', $sessionModel);

        $result = $this->sparringService->handleAgentMessage(
            sessionId: $session,
            agencyId: $agencyId,
            userId: $user->id,
            agentMessage: $validated['message'],
        );

        return response()->json([
            'status' => 'ok',
            'agent_message' => $result['agent_message'] ?? null,
            'gideon_reply' => $result['gideon_reply'] ?? null,
        ]);
    }

    /**
     * End a sparring session.
     *
     * POST /api/gideon/sparring/sessions/{session}/end
     */
    public function end(Request $request, int $session): JsonResponse
    {
        $user = $request->user();
        $agencyId = $user->agency_id;

        $sessionModel = GideonSparringSession::query()
            ->whereKey($session)
            ->where('agency_id', $agencyId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->authorize('update', $sessionModel);

        $endResult = $this->sparringService->endSession(
            sessionId: $session,
            agencyId: $agencyId,
            userId: $user->id,
        );

        return response()->json([
            'status' => 'ok',
            'session' => $endResult['session'],
            'assessment' => $endResult['assessment'] ?? null,
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
        $agencyId = $user->agency_id;

        $sessionModel = GideonSparringSession::query()
            ->whereKey($session)
            ->where('agency_id', $agencyId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->authorize('view', $sessionModel);

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
