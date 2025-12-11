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
        int $userId,
        string $scenarioCode,
        string $mode = 'prospect_simulation',
        ?string $requestedPersona = null,
    ): array {
        $scenario = GideonScenario::where('code', $scenarioCode)->first();

        if (! $scenario) {
            throw ValidationException::withMessages([
                'scenario_code' => ['Invalid scenario code.'],
            ]);
        }

        return DB::transaction(function () use (
            $agencyId,
            $userId,
            $scenario,
            $mode,
            $requestedPersona
        ) {
            $config = [
                'scenario_code' => $scenario->code,
                'mode'          => $mode,
            ];

            $prospectProfile = $scenario->prospect_profile ?? [];
            $defaultPersona  = is_array($prospectProfile)
                ? Arr::get($prospectProfile, 'persona')
                : null;

            // Normalize persona based on request + scenario default
            $personaKey = $this->normalizePersonaKey(
                $requestedPersona,
                is_array($prospectProfile) ? $prospectProfile : null,
            );

            if (! $personaKey && $defaultPersona) {
                $personaKey = (string) $defaultPersona;
            }

            $initialState = $this->initialStateForPersona($personaKey);

            $session = GideonSparringSession::create([
                'agency_id'   => $agencyId,
                'user_id'     => $userId,
                'mode'        => $mode,
                'persona_key' => $personaKey,
                'config'      => $config,
                'status'      => 'active',
                'state'       => $initialState,
                'started_at'  => now(),
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
     * Handle an agent message + generate Gideon's reply.
     *
     * @return array{agent_message: GideonSparringMessage, gideon_reply: GideonSparringMessage}
     */
    public function handleAgentMessage(
        int $sessionId,
        ?int $agencyId,
        int $userId,
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
            // Store agent message
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

            // Persona + state-aware reply
            $replyText = $this->generateGideonReply(
                $session,
                $scenario,
                $agentMessage,
            );

            $gideonMsg = GideonSparringMessage::create([
                'session_id' => $session->id,
                'agency_id'  => $agencyId,
                'user_id'    => $userId,
                'sender'     => 'gideon',
                'content'    => $replyText,
                'meta'       => [
                    'source'        => 'scenario_v1_logic_with_state',
                    'scenario_code' => $scenario?->code,
                ],
            ]);

            // Lightly evolve internal state after each exchange
            $session->state = $this->evolveStateOnTurn(
                $session->state ?? [],
                $session->persona_key,
            );
            $session->save();

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
        int $sessionId,
        ?int $agencyId,
        int $userId,
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
            'session_id'  => $session->id,
            'agency_id'   => $agencyId,
            'user_id'     => $userId,
            'scores'      => [
                'rapport'        => 5,
                'discovery'      => 5,
                'deal_killers'   => 5,
                'closing_clarity'=> 5,
            ],
            'strengths'   => 'Placeholder assessment. Automated coaching logic to be implemented.',
            'improvements'=> 'Placeholder assessment. Automated coaching logic to be implemented.',
            'meta'        => [],
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
        int $sessionId,
        ?int $agencyId,
        int $userId,
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
    | Persona / State helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Normalize persona slugs coming from the UI into internal persona keys.
     */
    protected function normalizePersonaKey(?string $requested, ?array $prospectProfile): ?string
    {
        if (! $requested) {
            // fall back to prospect profile if available
            return $prospectProfile['persona'] ?? null;
        }

        // UI slugs -> internal keys
        return match ($requested) {
            'soft_conflict_avoidant' => 'conflict_avoidant',
            'neutral_realistic'      => 'balanced_realist',
            'skeptical_guarded'     => 'skeptical_guarded',
            'adaptive'              => $prospectProfile['persona'] ?? null,
            default                 => $requested, // allow direct persona keys too
        };
    }

    /**
     * Initial emotional state based on persona.
     */
    protected function initialStateForPersona(?string $personaKey): array
    {
        // Defaults if we don't know yet
        $base = [
            'trust'       => 35,
            'urgency'     => 30,
            'motivation'  => 40,
            'resistance'  => 60,
        ];

        return match ($personaKey) {
            'conflict_avoidant' => [
                'trust'       => 40,
                'urgency'     => 20,
                'motivation'  => 35,
                'resistance'  => 65,
            ],
            'balanced_realist' => [
                'trust'       => 30,
                'urgency'     => 30,
                'motivation'  => 45,
                'resistance'  => 55,
            ],
            'skeptical_guarded' => [
                'trust'       => 20,
                'urgency'     => 25,
                'motivation'  => 35,
                'resistance'  => 70,
            ],
            default => $base,
        };
    }

    /**
     * Very simple evolution for now. Later this can look at actual content.
     */
    protected function evolveStateOnTurn(array $state, ?string $personaKey): array
    {
        $state = array_merge($this->initialStateForPersona(null), $state);

        // Nudge toward lower resistance & higher trust/motivation over time
        $deltaTrust      =  +3;
        $deltaMotivation =  +3;
        $deltaResistance =  -4;

        // Skeptical personas change more slowly
        if ($personaKey === 'skeptical_guarded') {
            $deltaTrust      = +2;
            $deltaMotivation = +2;
            $deltaResistance = -2;
        }

        $state['trust']      = max(0, min(100, $state['trust'] + $deltaTrust));
        $state['motivation'] = max(0, min(100, $state['motivation'] + $deltaMotivation));
        $state['resistance'] = max(0, min(100, $state['resistance'] + $deltaResistance));

        // Urgency: slowly climb as trust/motivation improve
        $state['urgency'] = max(0, min(100, $state['urgency'] + 2));

        return $state;
    }

    /*
    |--------------------------------------------------------------------------
    | Reply logic
    |--------------------------------------------------------------------------
    */

    /**
     * Persona + state aware reply (still rule-based, but less robotic).
     */
    protected function generateGideonReply(
        GideonSparringSession $session,
        ?GideonScenario $scenario,
        string $agentMessage,
    ): string {
        $scenarioLabel = $scenario?->name ?? 'this situation';
        $state         = $session->state ?? $this->initialStateForPersona($session->persona_key);
        $personaKey    = $session->persona_key;

        // Simple buckets
        $trust      = (int) ($state['trust'] ?? 35);
        $resistance = (int) ($state['resistance'] ?? 60);

        // Base stance sentence that will be tweaked by persona
        if ($resistance > 65) {
            $stance = "I’m still pretty on the fence about making a change right now.";
        } elseif ($trust < 30) {
            $stance = "I hear what you’re saying, but I’m not fully convinced yet.";
        } else {
            $stance = "I’m warming up to what you’re saying, but I still have a few things I’m working through.";
        }

        // Persona overlay
        $tonePrefix = match ($personaKey) {
            'conflict_avoidant' => "I don’t want to be difficult, and I really appreciate you walking me through this.",
            'balanced_realist'  => "I’m trying to be fair and look at this objectively.",
            'skeptical_guarded' => "I’m naturally cautious with decisions like this.",
            default             => "From my side as the prospect in {$scenarioLabel},",
        };

        $followUp = "If you were in my shoes, weighing this out, what else would you walk me through so I can feel completely comfortable with the decision?";

        return "{$tonePrefix} {$stance} {$followUp}";
    }
}
