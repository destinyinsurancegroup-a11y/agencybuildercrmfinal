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

            // Simple emotional "state machine"
            $initialState = [
                'trust'       => 35,
                'urgency'     => 30,
                'motivation'  => 40,
                'resistance'  => 60,
                'turn'        => 0,   // how many *Gideon replies* have been sent
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

            // We previously injected an "opening line" message here.
            // That created duplicate lines once Gideon replied.
            // For v1.1 we let the AGENT speak first, then Gideon responds.
            $firstMessage = null;

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
            // 1. Store agent message
            $agentMsg = GideonSparringMessage::create([
                'session_id' => $session->id,
                'agency_id'  => $agencyId,
                'user_id'    => $userId,
                'sender'     => 'agent',
                'content'    => $agentMessage,
                'meta'       => [
                    'analysis' => null, // later: NLP / pattern analysis goes here
                ],
            ]);

            // 2. Update emotional state based on what agent just said
            $state = $session->state ?? [];
            $state = $this->updateStateFromAgentMessage($state, $agentMessage);

            // Increment "turn" – how many Gideon replies have been given
            $state['turn'] = isset($state['turn']) ? (int) $state['turn'] + 1 : 1;

            $session->state = $state;
            $session->save();

            // 3. Generate reply from scenario-specific playbook
            $replyText = $this->generateGideonReply(
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
                    'state'         => $state,
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
     * Very simple reply for now. Uses:
     * - scenario-specific playbooks (turn 1, 2, 3…)
     * - the evolving emotional state
     */
    protected function generateGideonReply(
        GideonSparringSession $session,
        ?GideonScenario $scenario,
        string $agentMessage,
    ): string {
        $scenarioCode  = $scenario?->code ?? 'default';
        $state         = $session->state ?? [];
        $turn          = (int) ($state['turn'] ?? 1);

        // 1) Try scenario-specific playbook first
        $reply = $this->replyFromPlaybook($scenarioCode, $turn, $agentMessage, $state);

        if ($reply !== null) {
            return $reply;
        }

        // 2) Fallback generic behaviour
        $scenarioLabel = $scenario?->name ?? 'this situation';

        return "Okay, I hear you. But from my side as the prospect in {$scenarioLabel}, I’m still not fully convinced. "
            . "What else would you say to help me feel confident about this?";
    }

    /**
     * Scenario-specific playbooks (very small v1).
     */
    protected function replyFromPlaybook(
        string $scenarioCode,
        int $turn,
        string $agentMessage,
        array $state
    ): ?string {
        switch ($scenarioCode) {
            case 'scenario_competition_already_have_someone':
                return $this->competitionAlreadyHaveSomeoneReply($turn, $agentMessage, $state);

            case 'scenario_spouse_objection':
                return $this->spouseObjectionReply($turn, $agentMessage, $state);

            case 'scenario_think_it_over':
                return $this->thinkItOverReply($turn, $agentMessage, $state);

            case 'scenario_no_urgency_maybe_later':
                return $this->noUrgencyReply($turn, $agentMessage, $state);

            case 'scenario_price_too_expensive':
                return $this->priceTooExpensiveReply($turn, $agentMessage, $state);

            default:
                return null;
        }
    }

    protected function competitionAlreadyHaveSomeoneReply(
        int $turn,
        string $agentMessage,
        array $state
    ): ?string {
        switch ($turn) {
            case 1:
                return "We actually already have someone we work with for this. "
                    . "I’m not really looking to rock the boat unless there’s a really clear reason.";

            case 2:
                return "I’m somewhere in the middle. I’m not a hard no, but I’m not a yes either. "
                    . "If you were in my position, what would you focus on while weighing this out?";

            case 3:
                return "My biggest worry is that we switch, invest the time, and it’s not actually better. "
                    . "How would you help me avoid feeling like we made a sideways move?";

            default:
                return null;
        }
    }

    protected function spouseObjectionReply(
        int $turn,
        string $agentMessage,
        array $state
    ): ?string {
        switch ($turn) {
            case 1:
                return "I like this, but I really need to talk to my spouse before we do anything. "
                    . "We usually decide on this kind of thing together.";

            case 2:
                return "Honestly, my spouse is a bit more cautious than I am. "
                    . "If they were here, what do you think they’d be most concerned about?";

            case 3:
                return "If I go back and it sounds like I’ve already made up my mind, that won’t go over well. "
                    . "How can I bring this to them so it feels like a real conversation, not pressure?";

            default:
                return null;
        }
    }

    protected function thinkItOverReply(
        int $turn,
        string $agentMessage,
        array $state
    ): ?string {
        switch ($turn) {
            case 1:
                return "Yeah, this all sounds pretty good. I just want to think about it for a bit. "
                    . "I really hate feeling pressured into anything.";

            case 2:
                return "I guess part of me is worried about making the wrong call. "
                    . "What do people usually think about during that “I need to think about it” phase?";

            case 3:
                return "If I decided to move forward sooner rather than later, what would make that feel like a smart move "
                    . "instead of a rushed decision?";

            default:
                return null;
        }
    }

    protected function noUrgencyReply(
        int $turn,
        string $agentMessage,
        array $state
    ): ?string {
        switch ($turn) {
            case 1:
                return "It’s not that I’m against it – it just doesn’t feel urgent right now. "
                    . "We’ve got a lot of other things going on.";

            case 2:
                return "To be honest, it’s easier to keep this on the “later” pile. "
                    . "What usually happens to people who keep putting this off?";

            case 3:
                return "If we did move it up the priority list, what would need to be true so it doesn’t feel like just another thing "
                    . "on my plate?";

            default:
                return null;
        }
    }

    protected function priceTooExpensiveReply(
        int $turn,
        string $agentMessage,
        array $state
    ): ?string {
        switch ($turn) {
            case 1:
                return "I like what you’re saying, but the price just feels a bit high compared to what I expected.";

            case 2:
                return "I’m trying to figure out if this is actually more expensive, or if I’m just reacting to the number. "
                    . "How do your clients usually think about the cost?";

            case 3:
                return "If I’m going to pay more than I am now, I need to be really clear on what I’m getting for it. "
                    . "What would you want me to see or understand before I say yes?";

            default:
                return null;
        }
    }

    /**
     * Tiny heuristic: adjust emotional state based on agent's language.
     * (This is V1 and intentionally simple; we can tune/expand this later.)
     */
    protected function updateStateFromAgentMessage(array $state, string $agentMessage): array
    {
        // Ensure defaults
        $state = array_merge([
            'trust'      => 35,
            'urgency'    => 30,
            'motivation' => 40,
            'resistance' => 60,
            'turn'       => 0,
        ], $state);

        $text = mb_strtolower($agentMessage);

        // Empathy / validation -> trust up, resistance down
        if (str_contains($text, 'i get that')
            || str_contains($text, 'i hear')
            || str_contains($text, 'makes sense')
            || str_contains($text, 'sounds like')
        ) {
            $state['trust']      += 5;
            $state['resistance'] -= 5;
        }

        // Good discovery questions -> motivation + urgency up a bit
        if (str_contains($text, 'help me understand')
            || str_contains($text, 'tell me more')
            || str_contains($text, '?')
        ) {
            $state['motivation'] += 5;
            $state['urgency']    += 5;
        }

        // Gentle challenge / future pacing
        if (str_contains($text, 'what happens if')
            || str_contains($text, 'down the road')
            || str_contains($text, 'fast forward')
        ) {
            $state['urgency']    += 5;
            $state['resistance'] -= 2;
        }

        // Clamp values 0–100
        foreach (['trust', 'urgency', 'motivation', 'resistance'] as $key) {
            $state[$key] = max(0, min(100, (int) $state[$key]));
        }

        return $state;
    }
}
