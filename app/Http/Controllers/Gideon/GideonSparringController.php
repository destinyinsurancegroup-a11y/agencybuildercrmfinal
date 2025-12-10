<?php

namespace App\Http\Controllers\Gideon;

use App\Http\Controllers\Controller;
use App\Models\GideonSparringSession;
use App\Services\Gideon\GideonSparringPartner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GideonSparringController extends Controller
{
    public function __construct(
        protected GideonSparringPartner $sparringPartner
    ) {
        // Use Sanctum, same as routes/api.php
        $this->middleware('auth:sanctum');
    }

    /**
     * Handle an agent message and return Gideon's reply.
     *
     * POST /api/gideon/sparring/ask
     */
    public function ask(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'message'    => ['required', 'string'],
            'mode'       => ['nullable', 'string', 'max:50'],
            'personaKey' => ['nullable', 'string', 'max:100'],
            'session_id' => ['nullable', 'integer'],
        ]);

        $mode       = $data['mode'] ?? 'standard';
        $personaKey = $data['personaKey'] ?? null;

        // Find existing session or start a new one
        if (!empty($data['session_id'])) {
            $session = GideonSparringSession::where('id', $data['session_id'])
                ->where('agency_id', $user->agency_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$session) {
                return response()->json([
                    'error' => 'Session not found or not accessible.',
                ], 404);
            }
        } else {
            $session = $this->sparringPartner->startSession($user, $mode, $personaKey);
        }

        $reply = $this->sparringPartner->handleAgentMessage($session, $data['message']);

        return response()->json([
            'session_id' => $session->id,
            'reply'      => $reply,
        ]);
    }

    /**
     * End a session and return an assessment/scorecard.
     *
     * POST /api/gideon/sparring/end
     */
    public function end(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'session_id' => ['required', 'integer'],
        ]);

        $session = GideonSparringSession::where('id', $data['session_id'])
            ->where('agency_id', $user->agency_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $assessment = $this->sparringPartner->endSessionAndAssess($session);

        return response()->json([
            'session_id'  => $session->id,
            'assessment'  => [
                'scores'       => $assessment->scores,
                'strengths'    => $assessment->strengths,
                'improvements' => $assessment->improvements,
            ],
        ]);
    }
}
