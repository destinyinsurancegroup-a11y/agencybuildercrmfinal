<?php

namespace App\Http\Controllers\Gideon;

use App\Http\Controllers\Controller;
use App\Models\GideonScenario;
use App\Models\GideonSparringSession;
use App\Services\Gideon\SparringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GideonSparringController extends Controller
{
    public function __construct()
    {
        // Routes already have auth middleware, this is extra safety
        $this->middleware('auth');
    }

    /**
     * Show the Sparring Partner page.
     *
     * Loads available scenarios and passes them into the view.
     */
    public function index(Request $request)
    {
        $scenarios = GideonScenario::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('gideon.sparring', [
            'scenarios' => $scenarios,
        ]);
    }

    /**
     * Main sparring endpoint (AJAX/JSON).
     *
     * If session_id is null:
     *   - starts a new session using scenario_code + persona
     *   - sends the first agent message
     *
     * If session_id is provided:
     *   - just sends the agent message to the existing session
     */
    public function ask(Request $request, SparringService $sparring): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'session_id'    => ['nullable', 'integer', 'exists:gideon_sparring_sessions,id'],
            'scenario_code' => ['required_without:session_id', 'string'],
            'mode'          => ['nullable', 'string', 'in:prospect_simulation,role_reversal'],
            // Let the service normalize / validate persona keys so we can evolve them freely
            'persona'       => ['nullable', 'string'],
            'message'       => ['required', 'string', 'min:1'],
        ]);

        $mode         = $data['mode'] ?? 'prospect_simulation';
        $sessionId    = $data['session_id'] ?? null;
        $scenarioCode = $data['scenario_code'] ?? null;
        $personaKey   = $data['persona'] ?? null;
        $message      = $data['message'];

        try {
            if (! $sessionId) {
                // New session
                $sessionPayload = $sparring->startSession(
                    $user->agency_id,
                    $user->id,
                    $scenarioCode,
                    $mode,
                    $personaKey,
                );

                $session   = $sessionPayload['session'];
                $opening   = $sessionPayload['first_message'];
                $sessionId = $session->id;
            } else {
                // Existing session
                $session = GideonSparringSession::where('id', $sessionId)
                    ->where('agency_id', $user->agency_id)
                    ->where('user_id', $user->id)
                    ->firstOrFail();

                $opening = null;
            }

            $result = $sparring->handleAgentMessage(
                $sessionId,
                $user->agency_id,
                $user->id,
                $message
            );

            $session->refresh();

            return response()->json([
                'session'        => $session,
                'state'          => $session->state ?? null,
                'opening_line'   => $opening?->content,
                'agent_message'  => $result['agent_message'],
                'gideon_reply'   => $result['gideon_reply'],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Gideon sparring error.',
            ], 500);
        }
    }

    /**
     * End the session and return assessment summary.
     */
    public function end(Request $request, SparringService $sparring): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'session_id' => ['required', 'integer', 'exists:gideon_sparring_sessions,id'],
        ]);

        $sessionId = $data['session_id'];

        try {
            $result = $sparring->endSession(
                $sessionId,
                $user->agency_id,
                $user->id
            );

            return response()->json([
                'session'    => $result['session'],
                'assessment' => $result['assessment'],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Failed to end Gideon sparring session.',
            ], 500);
        }
    }
}
