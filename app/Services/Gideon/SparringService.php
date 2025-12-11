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
        string $personaKey = 'adaptive',
    ): array {
        $scenario = GideonScenario::where('code', $scenarioCode)->first();

        if (! $scenario) {
            throw ValidationException::withMessages([
                'scenario_code' => ['Invalid scenario code.'],
            ]);
        }

        return DB::transaction(function () use ($agencyId, $userId, $scenario, $mode, $personaKey) {
            $config = [
                'scenario_code' => $scenario->code,
                'mode'          => $mode,
                'persona_key'   => $personaKey,
            ];

            // Prospect profile from scenario (if any)
            $prospectProfile = $scenario->prospect_profile ?? [];
            $scenarioPersona = is_array($prospectProfile)
                ? Arr::get($prospectProfile, 'persona')
                : null;

            // If scenario defines a persona, let it override the generic one
            $effectivePersonaKey = $scenarioPersona ?: $personaKey;

            // Initial emotional state (rough defaults)
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
                'persona_key' => $effectivePersonaKey,
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
     * Handle an agent message + generate Gideon's reply (stub logic v1.5).
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

            // Very simple emotional state tweak (placeholder)
            $state = $session->state ?? [
                'trust'       => 35,
                'urgency'     => 30,
                'motivation'  => 40,
                'resistance'  => 60,
            ];

            $lower = mb_strtolower($agentMessage);

            // If agent sounds understanding, bump trust, reduce resistance
            if (str_contains($lower, 'i understand') || str_contains($lower, 'i get that')) {
                $state['trust']       = min(100, $state['trust'] + 5);
                $state['resistance']  = max(0, $state['resistance'] - 5);
            }

            // If agent asks good questions, bump motivation
            if (str_contains($lower, 'what would') || str_contains($lower, 'help you feel')) {
                $state['motivation'] = min(100, $state['motivation'] + 5);
            }

            // If agent pushes “today / now”, tweak urgency vs resistance
            if (str_contains($lower, 'today') || str_contains($lower, 'right now')) {
                $state['urgency']    = min(100, $state['urgency'] + 5);
                $state['resistance'] = min(100, $state['resistance'] + 3);
            }

            // Save updated state
            $session->state = $state;
            $session->save();

            // Persona-aware reply
            $replyText = $this->generateGideonReplyStub(
                $session,
                $scenario,
                $agentMessage,
                $state
            );

            $gideonMsg = GideonSparringMessage::create([
                'session_id' => $session->id,
                'agency_id'  => $agencyId,
                'user_id'    => $userId,
                'sender'     => 'gideon',
                'content'    => $replyText,
                'meta'       => [
                    'source'        => 'scenario_v1_logic_with_state_and_persona',
                    'scenario_code' => $scenario?->code,
                    'state'         => $state,
                    'persona_key'   => $session->persona_key,
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
            'session_id'   => $session->id,
            'agency_id'    => $agencyId,
            'user_id'      => $userId,
            'scores'       => [
                'rapport'         => 5,
                'discovery'       => 5,
                'deal_killers'    => 5,
                'closing_clarity' => 5,
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

    /**
     * Persona-aware, state-aware reply stub.
     *
     * Later this will use:
     * - tactics
     * - objection types
     * - stages
     * - if/then rules
     * - LLM + uploaded sales science
     */
    protected function generateGideonReplyStub(
        GideonSparringSession $session,
        ?GideonScenario $scenario,
        string $agentMessage,
        array $state = [],
    ): string {
        $scenarioLabel = $scenario?->name ?? 'this situation';
        $personaKey    = $session->persona_key ?? 'adaptive';

        $trust      = $state['trust']       ?? 35;
        $urgency    = $state['urgency']     ?? 30;
        $motivation = $state['motivation']  ?? 40;
        $resistance = $state['resistance']  ?? 60;

        // Base reply chunks we’ll reuse
        $hesitation   = "I’m still a little on the fence and don’t want to rush into anything I might regret.";
        $pressureFeel = "I really hate feeling pressured into anything.";
        $invite       = "From your side, what would you focus on if you were in my position, weighing this out?";
        $clarifyAsk   = "What else would you say to help me feel confident about this?";

        switch ($personaKey) {
            case 'soft_conflict_avoidant':
                return "Okay, I hear what you’re saying. In {$scenarioLabel}, {$hesitation} "
                    . "I’m not trying to be difficult, I just like to take my time and feel comfortable. "
                    . "{$invite}";

            case 'neutral_realistic':
                return "That helps, thank you. In {$scenarioLabel}, I’m open to this, but I still want to make sure it truly fits. "
                    . "I’m weighing the pros and cons and trying to see if this is actually better than what I have now. "
                    . "{$clarifyAsk}";

            case 'skeptical_guarded':
                return "I’m going to be straight with you: I’ve heard a lot of pitches before. "
                    . "In {$scenarioLabel}, I’m wondering what really makes this different and why I should trust it. "
                    . "Right now my guard is still up and I’m not convinced it’s worth changing what I’m doing. "
                    . "{$clarifyAsk}";

            case 'adaptive':
            default:
                // Adaptive = blend tone based on state
                if ($trust > 60 && $resistance < 40) {
                    return "You’re actually helping this make more sense. In {$scenarioLabel}, I’m starting to see how this could be a good move. "
                        . "I still want to make sure we’re not missing anything important though. {$clarifyAsk}";
                }

                if ($resistance > 70) {
                    return "Honestly, I’m still pretty hesitant. {$hesitation} {$pressureFeel} "
                        . "If you were in my shoes in {$scenarioLabel}, what would you need to hear or see to feel good about moving forward?";
                }

                // Middle ground
                return "I get what you’re saying, and parts of it do make sense. "
                    . "But in {$scenarioLabel}, I’m still not fully there yet. "
                    . "Help me connect the dots a bit more so I can feel confident this is the right move.";
        }
    }
}
