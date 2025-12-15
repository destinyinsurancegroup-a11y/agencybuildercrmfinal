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
        $this->middleware('auth');
    }

    /**
     * Show the Sparring Partner page.
     */
    public function index(Request $request)
    {
        // If scenarios are global: OK.
        // If scenarios are tenant-specific, add agency scoping here.
        $scenarios = GideonScenario::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('gideon.sparring', [
            'scenarios' => $scenarios,
        ]);
    }

    /**
     * Main sparring endpoint (AJAX/JSON).
     */
    public function ask(Request $request, SparringService $sparring): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'session_id'    => ['nullable', 'integer'], // ✅ no global exists() check
            'scenario_code' => ['required_without:session_id', 'string'],

            // Keep consistent with your current controller
            'mode'          => ['nullable', 'string', 'in:prospect_simulation,agent_simulation'],

            'persona'       => ['nullable', 'string'],
            'message'       => ['required', 'string', 'min:1'],
        ]);

        $mode         = $data['mode'] ?? SparringService::MODE_PROSPECT_SIM;
        $sessionId    = $data['session_id'] ?? null;
        $scenarioCode = $data['scenario_code'] ?? null;
        $personaKey   = $data['persona'] ?? null;
        $message      = $data['message'];

        try {
            $opening = null;

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
                $opening   = $sessionPayload['first_message'] ?? null;
                $sessionId = $session->id;

                // ✅ policy check (defense-in-depth)
                $this->authorize('view', $session);
            } else {
                // Existing session: tenant scope load + authorize
                $session = GideonSparringSession::query()
                    ->whereKey($sessionId)
                    ->where('agency_id', $user->agency_id)
                    ->where('user_id', $user->id)
                    ->firstOrFail();

                $this->authorize('view', $session);
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
                'agent_message'  => $result['agent_message'] ?? null,
                'gideon_reply'   => $result['gideon_reply'] ?? null,
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
            'session_id' => ['required', 'integer'], // ✅ no global exists() check
        ]);

        $sessionId = $data['session_id'];

        try {
            // Tenant scope + policy check before ending
            $session = GideonSparringSession::query()
                ->whereKey($sessionId)
                ->where('agency_id', $user->agency_id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $this->authorize('update', $session);

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
