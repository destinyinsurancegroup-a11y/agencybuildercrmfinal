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
            $personaKey = is_array($prospectProfile)
                ? Arr::get($prospectProfile, 'persona')
                : null;

            // Initial emotional state – v1 defaults, tuned per scenario
            $initialState = $this->initialStateForScenario($scenario->code, $personaKey);

            $session = GideonSparringSession::create([
                'agency_id'   => $agencyId,
                'user_id'     => $userId,
                'mode'        => $mode,
                'persona_key' => $personaKey,
                'config'      => $config,
                'state'       => $initialState,
                'status'      => 'active',
                'started_at'  => now(),
            ]);

            $scriptEngine = $scenario->script_engine ?? [];
            $openingLine = is_array($scriptEngine)
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

        $config = $session->config ?? [];
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

            // 2) Generate reply + updated emotional state
            [$replyText, $newState, $engineMeta] = $this->generateGideonReplyV1(
                $session,
                $scenario,
                $agentMessage
            );

            // Persist new state to the session
            $session->state = $newState;
            $session->save();

            // 3) Store Gideon's reply
            $gideonMsg = GideonSparringMessage::create([
                'session_id' => $session->id,
                'agency_id'  => $agencyId,
                'user_id'    => $userId,
                'sender'     => 'gideon',
                'content'    => $replyText,
                'meta'       => array_merge([
                    'source'        => 'scenario_v1_logic_with_state',
                    'scenario_code' => $scenario?->code,
                ], $engineMeta),
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

    /* -----------------------------------------------------------------
     |  INTERNAL: SCENARIO ENGINE V1
     |------------------------------------------------------------------*/

    /**
     * Default emotional state per scenario.
     */
    protected function initialStateForScenario(?string $scenarioCode, ?string $personaKey): array
    {
        // For now we just vary slightly by scenario; later this can read from DB/script_engine
        switch ($scenarioCode) {
            case 'scenario_spouse_objection':
            case 'scenario_spouse_objection_needs_to_talk':
                return [
                    'trust'       => 35,
                    'urgency'     => 30,
                    'motivation'  => 45,
                    'resistance'  => 65,
                    'step'        => 1,
                    'persona_key' => $personaKey,
                ];

            case 'scenario_think_it_over':
            default:
                return [
                    'trust'       => 35,
                    'urgency'     => 30,
                    'motivation'  => 40,
                    'resistance'  => 60,
                    'step'        => 1,
                    'persona_key' => $personaKey,
                ];
        }
    }

    /**
     * Main rule-based engine for v1.
     *
     * @return array{0:string,1:array,2:array} [replyText, newState, meta]
     */
    protected function generateGideonReplyV1(
        GideonSparringSession $session,
        ?GideonScenario $scenario,
        string $agentMessage,
    ): array {
        $scenarioCode = $scenario?->code ?? 'generic';
        $state        = $session->state ?? [];
        $state        = $this->ensureStateDefaults($state);

        $text = mb_strtolower($agentMessage, 'UTF-8');

        // Very simple pattern flags – v1
        $isCuriousQuestion = str_contains($text, 'what') || str_contains($text, 'help you');
        $mentionsRisk      = str_contains($text, 'risk') || str_contains($text, 'risky');
        $isPressure        = str_contains($text, 'today') ||
                             str_contains($text, 'right now') ||
                             str_contains($text, 'honestly') ||
                             str_contains($text, 'look,');
        $mentionsSpouse    = str_contains($text, 'spouse') ||
                             str_contains($text, 'wife') ||
                             str_contains($text, 'husband') ||
                             str_contains($text, 'partner');

        // Adjust emotional gauges based on patterns
        if ($isCuriousQuestion) {
            $state['trust']      += 7;
            $state['resistance'] -= 5;
        }

        if ($mentionsRisk) {
            $state['trust']      += 5;
            $state['motivation'] += 4;
        }

        if ($isPressure) {
            $state['trust']      -= 8;
            $state['resistance'] += 8;
        }

        if ($mentionsSpouse) {
            $state['motivation'] += 3;
        }

        // Clamp values 0–100
        foreach (['trust', 'urgency', 'motivation', 'resistance'] as $key) {
            $state[$key] = max(0, min(100, (int) ($state[$key] ?? 0)));
        }

        // Step = how many back-and-forths we’ve done in this session
        $step = (int) ($state['step'] ?? 1);

        // Delegate to a scenario-specific handler
        switch ($scenarioCode) {
            case 'scenario_think_it_over':
                $reply = $this->replyThinkItOver($state, $agentMessage);
                break;

            case 'scenario_spouse_objection':
            case 'scenario_spouse_objection_needs_to_talk':
                $reply = $this->replySpouseObjection($state, $agentMessage);
                break;

            default:
                $reply = $this->replyGenericObjection($state, $agentMessage);
                break;
        }

        // Advance conversation step
        $state['step'] = $step + 1;

        return [$reply, $state, [
            'engine_version' => 'v1',
        ]];
    }

    /**
     * Scenario: "I need to think it over."
     */
    protected function replyThinkItOver(array $state, string $agentMessage): string
    {
        $trust      = $state['trust'];
        $resistance = $state['resistance'];
        $step       = $state['step'];

        if ($step === 1) {
            // First pushback – acknowledge + open up risk
            return "Yeah, this all sounds pretty good. I just want to think about it for a bit. "
                . "Right now I’m more worried about making the wrong decision than missing out.";
        }

        // High resistance – still defensive
        if ($resistance >= 70) {
            return "Honestly, I still feel like I need more time. I hate the idea of being rushed into something "
                . "and then regretting it later. What makes this different from other things people try to sell me?";
        }

        // Medium resistance – open but cautious
        if ($resistance >= 45) {
            return "I hear what you’re saying, and it helps. I’m just trying to picture what happens if I say yes and "
                . "then something changes with my budget or health. Can you walk me through what goes wrong if I wait?";
        }

        // Lower resistance + decent trust – close to moving forward
        if ($trust >= 55 && $resistance < 45) {
            return "I mean, I’m pretty close. If I’m being honest, I just need to feel certain that I’m not stepping into "
                . "a bad decision. If you were in my shoes, what would you be looking for before you moved ahead?";
        }

        // Fallback
        return "I get that you’re trying to help, but I’m still on the fence. I don’t want to say no, but I’m not ready "
            . "to just jump in either. What’s the one thing you think I’m still not seeing clearly?";
    }

    /**
     * Scenario: spouse/partner objection.
     */
    protected function replySpouseObjection(array $state, string $agentMessage): string
    {
        $trust      = $state['trust'];
        $resistance = $state['resistance'];
        $step       = $state['step'];

        if ($step === 1) {
            return "I like this, but I really do need to talk it through with my spouse before I do anything. "
                . "We’ve always made these kinds of decisions together.";
        }

        if ($resistance >= 70) {
            return "I’m not trying to be difficult, it’s just how we work. If I showed up and told them I signed up "
                . "for this without even talking to them first, that would not go over well at all.";
        }

        if ($trust >= 55 && $resistance < 60) {
            return "I’m not against it, I just need to know my spouse won’t feel blindsided. "
                . "What would it look like for you to explain this with me so we’re all on the same page?";
        }

        return "I hear what you’re saying, but I still feel like I owe it to them to run it by them first. "
            . "What do you usually do when someone wants to include their spouse in the decision?";
    }

    /**
     * Fallback for any other generic objection scenario.
     */
    protected function replyGenericObjection(array $state, string $agentMessage): string
    {
        $trust      = $state['trust'];
        $resistance = $state['resistance'];

        if ($resistance >= 70) {
            return "I’m still not fully comfortable yet. It feels like there are pieces I’m missing, "
                . "and I don’t want to regret this later. What would you say to someone who’s skeptical like me?";
        }

        if ($trust >= 55 && $resistance < 55) {
            return "I do feel like you’re trying to help. I just want to be sure this really fits me and my situation. "
                . "What are the downsides here that I should be thinking about honestly?";
        }

        return "I’m somewhere in the middle. I’m not a hard no, but I’m not a yes either. "
            . "Help me understand what you’d focus on if you were in my position, weighing this out.";
    }

    /**
     * Ensure all state keys exist with sane defaults.
     */
    protected function ensureStateDefaults(?array $state): array
    {
        $state = $state ?? [];

        $defaults = [
            'trust'       => 35,
            'urgency'     => 30,
            'motivation'  => 40,
            'resistance'  => 60,
            'step'        => 1,
        ];

        foreach ($defaults as $key => $value) {
            if (! array_key_exists($key, $state)) {
                $state[$key] = $value;
            }
        }

        return $state;
    }
}
