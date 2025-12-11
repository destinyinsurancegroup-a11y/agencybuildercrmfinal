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

            $session = GideonSparringSession::create([
                'agency_id'   => $agencyId,
                'user_id'     => $userId,
                'mode'        => $mode,
                'persona_key' => $personaKey,
                'config'      => $config,
                'status'      => 'active',
                'started_at'  => now(),
            ]);

            /**
             * OPTION B: initialize emotional / scenario state.
             * These defaults will be adjusted by persona and agent behavior.
             */
            $state = [
                'motivation' => 40,
                'urgency'    => 30,
                'trust'      => 40,
                'resistance' => 50,
            ];

            if (is_array($prospectProfile)) {
                $persona = $prospectProfile['persona'] ?? null;

                if ($persona === 'easygoing_delayer') {
                    $state['urgency']    = 20;
                    $state['resistance'] = 55;
                } elseif ($persona === 'loyal_but_open') {
                    $state['trust']      = 50;
                    $state['resistance'] = 45;
                } elseif ($persona === 'conflict_avoidant') {
                    $state['trust']      = 35;
                    $state['resistance'] = 60;
                }
            }

            $session->state = $state;
            $session->save();

            // Scenario opening line (Gideon speaks first if defined)
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
            // Update emotional state based on this message (Option B foundation)
            $state = $session->state ?? [];
            $state = $this->updateStateFromAgentMessage($state, $agentMessage);
            $session->state = $state;
            $session->save();

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

            // Scenario + state-aware reply
            $replyText = $this->generateGideonReplyStub(
                $session,
                $scenario,
                $agentMessage
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
     * Option B helper: update emotional state from the agent's message.
     */
    protected function updateStateFromAgentMessage(array $state, string $message): array
    {
        $lower = mb_strtolower($message);

        // Ensure baseline values
        $state['motivation'] = $state['motivation'] ?? 40;
        $state['urgency']    = $state['urgency'] ?? 30;
        $state['trust']      = $state['trust'] ?? 40;
        $state['resistance'] = $state['resistance'] ?? 50;

        // Empathy phrases → trust up, resistance down
        if (
            str_contains($lower, 'i get that') ||
            str_contains($lower, 'i understand') ||
            str_contains($lower, 'that makes sense') ||
            str_contains($lower, 'i hear you') ||
            str_contains($lower, 'fair enough')
        ) {
            $state['trust']      += 5;
            $state['resistance'] -= 5;
        }

        // Good discovery / clarifiers → motivation up
        if (
            str_contains($lower, 'help me understand') ||
            str_contains($lower, 'tell me more') ||
            str_contains($lower, 'walk me through') ||
            str_contains($lower, 'what makes you say that') ||
            str_contains($lower, 'what part feels unclear')
        ) {
            $state['motivation'] += 5;
        }

        // Early close attempts
        if (
            str_contains($lower, 'ready to move forward') ||
            str_contains($lower, 'get this started') ||
            str_contains($lower, 'go ahead and') ||
            str_contains($lower, 'sign up today') ||
            str_contains($lower, 'move forward')
        ) {
            if ($state['trust'] < 50 || $state['resistance'] > 50) {
                // Prospect feels pushed → resistance spikes
                $state['resistance'] += 10;
            } else {
                // If relationship is good, closing builds urgency & motivation
                $state['motivation'] += 10;
                $state['urgency']    += 10;
            }
        }

        // Clamp 0–100
        foreach (['motivation', 'urgency', 'trust', 'resistance'] as $key) {
            $state[$key] = max(0, min(100, (int) $state[$key]));
        }

        return $state;
    }

    /**
     * Scenario-aware reply (Option A) + light emotional flavor (Option B).
     */
    protected function generateGideonReplyStub(
        GideonSparringSession $session,
        ?GideonScenario $scenario,
        string $agentMessage,
    ): string {
        // Fallback if scenario is missing
        if (! $scenario) {
            return "I hear what you’re saying. From my side as the prospect, I’m still not completely sure. "
                . "What else would you ask me or explain so I can feel confident either way?";
        }

        $scriptEngine    = $scenario->script_engine ?? [];
        $prospectProfile = $scenario->prospect_profile ?? [];

        $scenarioName      = $scenario->name;
        $objectionTypeCode = is_array($scriptEngine)
            ? ($scriptEngine['objection_type_code'] ?? null)
            : null;

        // Light intent classification from the agent message
        $lower = mb_strtolower($agentMessage);

        $agentAskedForClarity =
            str_contains($lower, 'what do you mean') ||
            str_contains($lower, 'help me understand') ||
            str_contains($lower, 'clarify') ||
            str_contains($lower, 'tell me more') ||
            str_contains($lower, 'walk me through');

        $agentWentForClose =
            str_contains($lower, 'ready to move forward') ||
            str_contains($lower, 'get this started') ||
            str_contains($lower, 'go ahead and') ||
            str_contains($lower, 'sign up') ||
            str_contains($lower, 'move ahead') ||
            str_contains($lower, 'move forward');

        $agentUsedEmpathy =
            str_contains($lower, 'i get that') ||
            str_contains($lower, 'i understand') ||
            str_contains($lower, 'that makes sense') ||
            str_contains($lower, 'fair enough') ||
            str_contains($lower, 'i hear you');

        // Reply templates keyed by objection type
        $repliesByObjection = [
            'obj_money' => [
                'default' => "Honestly, the biggest thing in my head is still the price. "
                    . "I’m wondering if this is really worth that much for me right now.",
                'after_clarifier' => "I get that you’re trying to understand where I’m coming from. "
                    . "My worry is just paying that amount and then not really feeling the difference day to day.",
                'after_empathy' => "I appreciate you seeing where I’m coming from. "
                    . "I just don’t want to commit to something that squeezes the budget too much.",
            ],
            'obj_think_it_over' => [
                'default' => "It all sounds good, I just feel like I need a bit more time to think it over before I say yes or no.",
                'after_clarifier' => "To be honest, it’s less about details and more that I’m nervous about making the wrong call today.",
                'after_empathy' => "Yeah, it’s a big decision. I just don’t want to rush into it and regret it.",
            ],
            'obj_spouse' => [
                'default' => "I like what you’re saying, but I really don’t make these kinds of decisions without my spouse.",
                'after_clarifier' => "My spouse will definitely have questions about the cost and whether it really changes anything for us.",
                'after_empathy' => "I appreciate you understanding that. I just know my spouse will want to have a say before we commit.",
            ],
            'obj_competition' => [
                'default' => "The main thing is we already have someone we work with for this, and switching feels like a bit of a risk.",
                'after_clarifier' => "It’s not that they’re perfect, but at least we know what we’re getting. Changing providers always feels risky.",
                'after_empathy' => "Exactly, we’ve had this in place for a while. I’d have to feel really confident that changing is worth the hassle.",
            ],
            'obj_no_urgency' => [
                'default' => "I just don’t feel like this is an urgent thing right now. It’s more of a ‘someday’ decision in my head.",
                'after_clarifier' => "It’s not that it’s unimportant, it’s just that nothing is really forcing us to act right away.",
                'after_empathy' => "Yeah, I get that it matters, it just feels like one of those things we could look at later.",
            ],
        ];

        $templates = $repliesByObjection[$objectionTypeCode] ?? null;

        // If we don’t have a specific objection profile, fall back to generic
        if (! $templates) {
            return "From my side as the prospect in {$scenarioName}, I’m still on the fence. "
                . "Part of me sees the upside, but part of me is nervous about making a mistake. "
                . "What would you ask me next to really understand what’s holding me back?";
        }

        // Choose template based on what the agent just did
        if ($agentAskedForClarity) {
            $reply = $templates['after_clarifier'];
        } elseif ($agentUsedEmpathy) {
            $reply = $templates['after_empathy'];
        } elseif ($agentWentForClose) {
            $reply = $templates['default'] . " I’m not at a solid yes yet.";
        } else {
            $reply = $templates['default'];
        }

        // Emotional flavor from state (Option B)
        $state      = $session->state ?? [];
        $trust      = $state['trust'] ?? 40;
        $resistance = $state['resistance'] ?? 50;

        if ($trust > 60 && $resistance < 40) {
            $reply = "I actually do like a lot of what you’re saying. " . $reply;
        } elseif ($resistance > 70) {
            $reply .= " Honestly, I’m feeling a bit pushed right now.";
        }

        // Persona flavor
        $persona = is_array($prospectProfile)
            ? ($prospectProfile['persona'] ?? null)
            : null;

        if ($persona === 'easygoing_delayer') {
            $reply .= " I’m just the type that likes to drag my feet on this kind of thing.";
        } elseif ($persona === 'loyal_but_open') {
            $reply .= " I’m loyal by nature, so changing what we do now takes a lot for me.";
        } elseif ($persona === 'conflict_avoidant') {
            $reply .= " I really hate feeling pressured into anything.";
        }

        return $reply;
    }
}
