<?php

namespace App\Services\Gideon;

use App\Models\GideonScenario;
use App\Models\GideonSparringAssessment;
use App\Models\GideonSparringMessage;
use App\Models\GideonSparringSession;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SparringService
{
    /**
     * Start a new sparring session based on a scenario.
     *
     * @return array{session: GideonSparringSession, first_message: GideonSparringMessage|null}
     */
    public function startSession(
        ?int $agencyId,
        int  $userId,
        string $scenarioCode,
        string $mode = 'prospect_simulation',
    ): array {
        $scenario = GideonScenario::where('code', $scenarioCode)->first();

        if (! $scenario) {
            throw ValidationException::withMessages([
                'scenario_code' => ['Invalid scenario code.'],
            ]);
        }

        return DB::transaction(function () use ($agencyId, $userId, $scenario, $mode) {
            $config = [
                'scenario_code' => $scenario->code,
                'mode'          => $mode,
            ];

            $prospectProfile = $scenario->prospect_profile ?? [];
            $personaKey      = is_array($prospectProfile)
                ? Arr::get($prospectProfile, 'persona')
                : null;

            // basic starting emotional state for the prospect
            $initialState = [
                'trust'       => 35,
                'urgency'     => 30,
                'motivation'  => 40,
                'resistance'  => 60,
            ];

            $session = GideonSparringSession::create([
                'agency_id'   => $agencyId,
                'user_id'     => $userId,
                'mode'        => $mode,
                'persona_key' => $personaKey,
                'config'      => $config,
                'status'      => 'active',
                'started_at'  => now(),
                'state'       => $initialState,
            ]);

            $scriptEngine = $scenario->script_engine ?? [];
            $openingLine  = is_array($scriptEngine)
                ? Arr::get($scriptEngine, 'opening_line')
                : null;

            $firstMessage = null;

            if ($openingLine) {
                $firstMessage = GideonSparringMessage::create([
                    'session_id' => $session->id,
                    'agency_id'  => $agencyId,
                    'user_id'    => $userId,
                    'sender'     => 'gideon',
                    'content'    => $openingLine,
                    'meta'       => [
                        'source'        => 'scenario_opening_line',
                        'scenario_code' => $scenario->code,
                    ],
                ]);
            }

            return [
                'session'       => $session->fresh(),
                'first_message' => $firstMessage,
            ];
        });
    }

    /**
     * Handle an agent message + generate Gideon's reply (v1 logic with state & tactics).
     *
     * @return array{agent_message: GideonSparringMessage, gideon_reply: GideonSparringMessage}
     */
    public function handleAgentMessage(
        int    $sessionId,
        ?int   $agencyId,
        int    $userId,
        string $agentMessage,
    ): array {
        $session = GideonSparringSession::where('id', $sessionId)
            ->where('agency_id', $agencyId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        if ($session->status !== 'active') {
            throw ValidationException::withMessages([
                'session' => ['This session is not active.'],
            ]);
        }

        $config       = $session->config ?? [];
        $scenarioCode = is_array($config) ? ($config['scenario_code'] ?? null) : null;

        $scenario = $scenarioCode
            ? GideonScenario::where('code', $scenarioCode)->first()
            : null;

        return DB::transaction(function () use (
            $session,
            $agencyId,
            $userId,
            $agentMessage,
            $scenario
        ) {
            // 1) Store agent message
            $agentMsg = GideonSparringMessage::create([
                'session_id' => $session->id,
                'agency_id'  => $agencyId,
                'user_id'    => $userId,
                'sender'     => 'agent',
                'content'    => $agentMessage,
                'meta'       => [
                    'analysis' => null, // later: pattern analysis goes here
                ],
            ]);

            // 2) Load current emotional state
            $state = $session->state ?? [
                'trust'       => 35,
                'urgency'     => 30,
                'motivation'  => 40,
                'resistance'  => 60,
            ];

            if (! is_array($state)) {
                $state = [
                    'trust'       => 35,
                    'urgency'     => 30,
                    'motivation'  => 40,
                    'resistance'  => 60,
                ];
            }

            // 3) Choose tactic + reply based on state & scenario
            $tactic = $this->chooseTactic($state, $scenario, $agentMessage);

            $replyText = $this->buildReplyFromTactic(
                $tactic,
                $state,
                $scenario,
                $agentMessage
            );

            // 4) Adjust state based on the tactic (very simple v1)
            $newState = $this->updateStateFromTactic($state, $tactic);

            $session->update([
                'state' => $newState,
            ]);

            // 5) Store Gideon reply
            $gideonMsg = GideonSparringMessage::create([
                'session_id' => $session->id,
                'agency_id'  => $agencyId,
                'user_id'    => $userId,
                'sender'     => 'gideon',
                'content'    => $replyText,
                'meta'       => [
                    'source'        => 'scenario_v1_logic_with_state',
                    'scenario_code' => $scenario?->code,
                    'tactic'        => $tactic['label'] ?? null,
                ],
            ]);

            return [
                'agent_message' => $agentMsg,
                'gideon_reply'  => $gideonMsg,
            ];
        });
    }

    /**
     * End a session; for now, create a placeholder assessment.
     *
     * @return array{session: GideonSparringSession, assessment: GideonSparringAssessment}
     */
    public function endSession(
        int  $sessionId,
        ?int $agencyId,
        int  $userId,
    ): array {
        $session = GideonSparringSession::where('id', $sessionId)
            ->where('agency_id', $agencyId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $session->update([
            'status'   => 'completed',
            'ended_at' => now(),
        ]);

        $assessment = GideonSparringAssessment::create([
            'session_id'   => $session->id,
            'agency_id'    => $agencyId,
            'user_id'      => $userId,
            'scores'       => [
                'rapport'        => 5,
                'discovery'      => 5,
                'deal_killers'   => 5,
                'closing_clarity'=> 5,
            ],
            'strengths'    => 'Placeholder assessment. Automated coaching logic to be implemented.',
            'improvements' => 'Placeholder assessment. Automated coaching logic to be implemented.',
            'meta'         => [],
        ]);

        return [
            'session'    => $session->fresh(),
            'assessment' => $assessment,
        ];
    }

    /**
     * Fetch a session + its messages for playback / review.
     *
     * @return array{session: GideonSparringSession, messages: Collection<int, GideonSparringMessage>}
     */
    public function getSessionTranscript(
        int  $sessionId,
        ?int $agencyId,
        int  $userId,
    ): array {
        $session = GideonSparringSession::where('id', $sessionId)
            ->where('agency_id', $agencyId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $messages = GideonSparringMessage::where('session_id', $session->id)
            ->orderBy('created_at')
            ->get();

        return [
            'session'  => $session,
            'messages' => $messages,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | TACTIC ENGINE V1
    |--------------------------------------------------------------------------
    | Very simple, but gives us:
    | - different “moves” depending on trust / resistance
    | - hooks for later when we wire in your Science of Sales content
    */

    /**
     * Decide what kind of move Gideon should make next.
     *
     * @return array{label:string, style:string}
     */
    protected function chooseTactic(
        array $state,
        ?GideonScenario $scenario,
        string $agentMessage,
    ): array {
        $trust      = (int) ($state['trust'] ?? 35);
        $urgency    = (int) ($state['urgency'] ?? 30);
        $motivation = (int) ($state['motivation'] ?? 40);
        $resistance = (int) ($state['resistance'] ?? 60);

        // Very high resistance: go to empathy / label feelings
        if ($resistance >= 70) {
            return [
                'label' => 'tactical_empathy',
                'style' => 'softening',
            ];
        }

        // Low clarity / mid resistance: clarify and go deeper
        if ($resistance >= 50 && $trust < 60) {
            return [
                'label' => 'clarifying_question',
                'style' => 'socratic',
            ];
        }

        // Decent trust but low urgency: build consequences / “what happens if nothing changes”
        if ($trust >= 55 && $urgency < 50) {
            return [
                'label' => 'future_pacing',
                'style' => 'gentle_consequence',
            ];
        }

        // Trust & urgency both reasonable: small soft close / test commitment
        if ($trust >= 60 && $urgency >= 55 && $resistance <= 50) {
            return [
                'label' => 'test_close',
                'style' => 'if_then',
            ];
        }

        // Default: reflect + ask one clean question
        return [
            'label' => 'reflection_plus_question',
            'style' => 'neutral',
        ];
    }

    /**
     * Build Gideon's reply text for the chosen tactic.
     */
    protected function buildReplyFromTactic(
        array $tactic,
        array $state,
        ?GideonScenario $scenario,
        string $agentMessage,
    ): string {
        $scenarioLabel = $scenario?->name ?? 'this situation';

        $label = $tactic['label'] ?? 'reflection_plus_question';

        switch ($label) {
            case 'tactical_empathy':
                return
                    "I hear what you’re saying, and I get why you’d feel that way. "
                    . "From where I’m sitting in {$scenarioLabel}, a lot of this comes down to comfort and trust. "
                    . "What specifically is making you most hesitant right now?";

            case 'clarifying_question':
                return
                    "Got it. It sounds like there’s still something that doesn’t feel quite right. "
                    . "When you think about {$scenarioLabel}, what part feels the most unclear or risky to you?";

            case 'future_pacing':
                return
                    "That makes sense. I don’t want you to rush into anything and regret it either. "
                    . "If you and I decide to leave things exactly as they are, what do you see happening over the next 6–12 months?";
            
            case 'test_close':
                return
                    "Totally fair. Let me ask you this though – "
                    . "if we were able to lay everything out so that it made sense and fit your budget, "
                    . "would there be anything else that would stop you from moving forward?";

            case 'reflection_plus_question':
            default:
                return
                    "Okay, I appreciate you sharing that. "
                    . "When you look at {$scenarioLabel} as a whole, what would need to be true for you to feel genuinely comfortable moving ahead?";
        }
    }

    /**
     * Adjust the internal state based on the tactic we just used.
     */
    protected function updateStateFromTactic(array $state, array $tactic): array
    {
        $trust      = (int) ($state['trust'] ?? 35);
        $urgency    = (int) ($state['urgency'] ?? 30);
        $motivation = (int) ($state['motivation'] ?? 40);
        $resistance = (int) ($state['resistance'] ?? 60);

        $label = $tactic['label'] ?? 'reflection_plus_question';

        switch ($label) {
            case 'tactical_empathy':
                $trust      += 5;
                $resistance -= 5;
                break;

            case 'clarifying_question':
                $trust      += 3;
                $resistance -= 2;
                break;

            case 'future_pacing':
                $urgency    += 5;
                $motivation += 4;
                break;

            case 'test_close':
                // small bump in urgency; resistance can go up or down in real life,
                // but here we’ll assume you handled it well.
                $urgency    += 3;
                $resistance -= 2;
                break;

            case 'reflection_plus_question':
            default:
                $trust += 2;
                break;
        }

        // keep in 0–100 range
        $trust      = max(0, min(100, $trust));
        $urgency    = max(0, min(100, $urgency));
        $motivation = max(0, min(100, $motivation));
        $resistance = max(0, min(100, $resistance));

        return [
            'trust'       => $trust,
            'urgency'     => $urgency,
            'motivation'  => $motivation,
            'resistance'  => $resistance,
        ];
    }
}
