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
        $scenarios = GideonScenario::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('gideon.sparring', [
            'scenarios' => $scenarios,
        ]);
    }

    /**
     * ✅ Start a NEW sparring session (no message required).
     */
    public function start(Request $request, SparringService $sparring): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'scenario_code'  => ['required', 'string'],

            // UI role mode (NOT training mode)
            'mode'           => ['nullable', 'string', 'in:prospect_simulation,agent_simulation'],
            'persona'        => ['nullable', 'string'],

            // training controls
            'training_mode'  => ['nullable', 'string', 'in:stages,discovery_start,full_presentation'],
            'selected_stage' => ['nullable', 'string', 'in:intro,discovery,education,qualify,quote,close'],
            'difficulty'     => ['nullable', 'string', 'in:easy,normal,hard'],
        ]);

        $mode          = $data['mode'] ?? SparringService::UI_MODE_PROSPECT_SIM;
        $personaKey    = $data['persona'] ?? 'adaptive';
        $scenarioCode  = $data['scenario_code'];

        $trainingMode  = $data['training_mode'] ?? null;
        $selectedStage = $data['selected_stage'] ?? null;
        $difficulty    = $data['difficulty'] ?? null;

        try {
            $sessionPayload = $sparring->startSession(
                $user->agency_id,
                $user->id,
                $scenarioCode,
                $mode,
                $personaKey
            );

            $session = $sessionPayload['session'];
            $opening = $sessionPayload['first_message'] ?? null;

            $this->authorize('view', $session);

            // Apply training controls only at session creation time.
            $updates = [];
            if ($trainingMode !== null)  $updates['training_mode'] = $trainingMode;
            if ($selectedStage !== null) $updates['selected_stage'] = $selectedStage;
            if ($difficulty !== null)    $updates['difficulty'] = $difficulty;

            if (!empty($updates)) {
                $config = is_array($session->config) ? $session->config : [];
                $config['training_mode']  = $trainingMode ?? ($config['training_mode'] ?? null);
                $config['selected_stage'] = $selectedStage ?? ($config['selected_stage'] ?? null);
                $config['difficulty']     = $difficulty ?? ($config['difficulty'] ?? null);

                $updates['config'] = $config;

                $session->update($updates);
                $session->refresh();
            }

            return response()->json([
                'session'      => $session,
                'opening_line' => $opening?->content,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Failed to start sparring session.',
            ], 500);
        }
    }

    /**
     * Main sparring endpoint (AJAX/JSON).
     */
    public function ask(Request $request, SparringService $sparring): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'session_id'    => ['nullable', 'integer'],
            'scenario_code' => ['required_without:session_id', 'string'],

            'mode'          => ['nullable', 'string', 'in:prospect_simulation,agent_simulation'],
            'persona'       => ['nullable', 'string'],
            'message'       => ['required', 'string', 'min:1'],

            // training controls (only used when starting a NEW session via ask)
            'training_mode'  => ['nullable', 'string', 'in:stages,discovery_start,full_presentation'],
            'selected_stage' => ['nullable', 'string', 'in:intro,discovery,education,qualify,quote,close'],
            'difficulty'     => ['nullable', 'string', 'in:easy,normal,hard'],
        ]);

        $mode         = $data['mode'] ?? SparringService::UI_MODE_PROSPECT_SIM;
        $sessionId    = $data['session_id'] ?? null;
        $scenarioCode = $data['scenario_code'] ?? null;
        $personaKey   = $data['persona'] ?? 'adaptive';
        $message      = $data['message'];

        $trainingMode  = $data['training_mode'] ?? null;
        $selectedStage = $data['selected_stage'] ?? null;
        $difficulty    = $data['difficulty'] ?? null;

        try {
            $opening = null;

            if (! $sessionId) {
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

                $this->authorize('view', $session);

                $updates = [];
                if ($trainingMode !== null)  $updates['training_mode'] = $trainingMode;
                if ($selectedStage !== null) $updates['selected_stage'] = $selectedStage;
                if ($difficulty !== null)    $updates['difficulty'] = $difficulty;

                if (!empty($updates)) {
                    $config = is_array($session->config) ? $session->config : [];
                    $config['training_mode']  = $trainingMode ?? ($config['training_mode'] ?? null);
                    $config['selected_stage'] = $selectedStage ?? ($config['selected_stage'] ?? null);
                    $config['difficulty']     = $difficulty ?? ($config['difficulty'] ?? null);
                    $updates['config'] = $config;

                    $session->update($updates);
                    $session->refresh();
                }
            } else {
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
            'session_id' => ['required', 'integer'],
        ]);

        $sessionId = $data['session_id'];

        try {
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
