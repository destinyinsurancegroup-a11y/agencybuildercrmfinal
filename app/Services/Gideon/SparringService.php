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
     * Return scenarios for the Sparring Partner UI.
     *
     * Currently just returns all active scenarios ordered by name.
     */
    public function listScenariosForUi(): Collection
    {
        return GideonScenario::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

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

        return DB::transaction(function () use (
            $agencyId,
            $userId,
            $scenario,
            $mode,
            $personaKey
        ) {
            $config = [
                'scenario_code' => $scenario->code,
                'mode'          => $mode,
            ];

            // Default state “dials” – can be tuned per persona/scenario.
            $baseState = [
                'trust'       => 35,
                'urgency'     => 30,
                'motivation'  => 40,
                'resistance'  => 60,
            ];

            // Simple persona-based tweaks.
            switch ($personaKey) {
                case 'soft_conflict_avoidant':
                    $baseState['trust']      = 40;
                    $baseState['urgency']    = 25;
                    $baseState['motivation'] = 35;
                    $baseState['resistance'] = 65;
                    break;

                case 'skeptical_guarded':
                    $baseState['trust']      = 25;
                    $baseState['urgency']    = 35;
                    $baseState['motivation'] = 35;
                    $baseState['resistance'] = 70;
                    break;

                case 'neutral_realistic':
                    $baseState['trust']      = 40;
                    $baseState['urgency']    = 35;
                    $baseState['motivation'] = 40;
                    $baseState['resistance'] = 55;
                    break;

                case 'adaptive':
                default:
                    // leave defaults, “adaptive” will move quicker as you ask good questions
                    break;
            }

            $session = GideonSparringSession::create([
                'agency_id'   => $agencyId,
                'user_id'     => $userId,
                'mode'        => $mode,
                'persona_key' => $personaKey,
                'config'      => $config,
                'status'      => 'active',
                'state'       => $baseState,
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

            // Update internal state first (very simple heuristic for now)
            $this->applyStateTransition($session, $agentMessage);
            $session->refresh();

            // Generate Gideon's reply based on scenario + state + persona
            $replyText = $this->generateGideonReplyWithState(
                $session,
                $scenario,
                $agentMessage
            );

            // NEW: make sure we don't just repeat the last Gideon line verbatim
            $replyText = $this->ensureNonRepeatingReply($session, $replyText, $agentMessage, $scenario);

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

        // Very simple “scoring” based on final state – placeholder.
        $state = $session->state ?? [
            'trust'       => 40,
            'urgency'     => 30,
            'motivation'  => 40,
            'resistance'  => 60,
        ];

        $rapport        = (int) round(($state['trust'] ?? 40) / 20);       // 1–5
        $discovery      = (int) round(($state['motivation'] ?? 40) / 20); // 1–5
        $dealKillers    = (int) round(5 - (($state['resistance'] ?? 60) / 20)); // lower resistance = higher score
        $closingClarity = (int) round(($state['urgency'] ?? 30) / 20);

        $assessment = GideonSparringAssessment::create([
            'session_id'   => $session->id,
            'agency_id'    => $agencyId,
            'user_id'      => $userId,
            'scores'       => [
                'rapport'        => max(1, min(5, $rapport)),
                'discovery'      => max(1, min(5, $discovery)),
                'deal_killers'   => max(1, min(5, $dealKillers)),
                'closing_clarity'=> max(1, min(5, $closingClarity)),
            ],
            'strengths'    => 'There was some rapport, especially when you slowed down and validated what they were feeling. You also asked some helpful questions that nudged the conversation forward.',
            'improvements' => 'Spend more time reflecting back what the prospect is feeling before you pivot back to the solution. Ask more open-ended questions about what they value, what they are worried about, and what would have to be true for them to feel good moving forward.',
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
     * Simple heuristic reply using scenario + session state + last agent message.
     *
     * This is where we’ll later swap in the full LLM-powered engine.
     */
    protected function generateGideonReplyWithState(
        GideonSparringSession $session,
        ?GideonScenario $scenario,
        string $agentMessage,
    ): string {
        $state      = $session->state ?? [];
        $personaKey = $session->persona_key ?? 'adaptive';
        $scenarioLabel = $scenario?->name ?? 'this situation';

        $trust      = (int) ($state['trust'] ?? 40);
        $urgency    = (int) ($state['urgency'] ?? 30);
        $motivation = (int) ($state['motivation'] ?? 40);
        $resistance = (int) ($state['resistance'] ?? 60);

        $msgLower = mb_strtolower($agentMessage, 'UTF-8');

        // Very rough pattern checks.
        $isValidation = str_contains($msgLower, 'i get that')
            || str_contains($msgLower, 'i hear')
            || str_contains($msgLower, 'makes sense');

        $isDiscoveryQuestion = str_contains($agentMessage, '?');

        $isFuturePace = str_contains($msgLower, 'fast forward')
            || str_contains($msgLower, 'picture this')
            || str_contains($msgLower, 'imagine');

        // High resistance branch
        if ($resistance >= 60) {
            if ($isValidation) {
                return "I appreciate you slowing down and trying to understand where I’m coming from. In {$scenarioLabel}, I’m still a little guarded though. What else would you ask to help me feel truly heard and not pushed?";
            }

            if ($isDiscoveryQuestion) {
                return "Your questions are helping me sort things out, but in {$scenarioLabel} there’s still something that feels risky. What follow-up question would you ask now to get underneath what I’m really worried about?";
            }

            return "From my side as the prospect in {$scenarioLabel}, I’m still not fully comfortable making a change yet. What would you ask me next to uncover what’s really keeping me from moving forward?";
        }

        // Medium resistance, building trust
        if ($trust < 50) {
            if ($isDiscoveryQuestion) {
                return "Your questions are helping me think this through. In {$scenarioLabel}, what you just asked nudged my trust up a bit, but I’m still not all the way there. What else would you ask so I feel like you truly get my world?";
            }

            return "I can tell you’re trying to understand my situation in {$scenarioLabel}, which helps. If you were in my shoes, what question would you ask next to show you’re really on my side and not just trying to close a sale?";
        }

        // Higher trust, lower resistance – closer to yes
        if ($resistance <= 45 && $trust >= 50) {
            if ($isFuturePace) {
                return "That way of looking ahead actually helps me. In {$scenarioLabel}, I’m starting to picture how this could work. What would you ask next to help me feel confident taking a concrete next step rather than drifting away again?";
            }

            return "I’m warming up to this. In {$scenarioLabel}, I’m not a hard no anymore, but I still want to feel totally clear. What would you ask me now so that we can either land on a solid yes or honestly agree it’s not the right fit?";
        }

        // Fallback generic coaching-style reply
        return "Okay, I’m tracking with what you’re saying. But as the prospect in {$scenarioLabel}, there’s still a piece I’m not settled on yet. What question would you ask me next to understand what I’m really evaluating this against?";
    }

    /**
     * Lightweight state update based on the agent's last message.
     *
     * For now this just nudges the dials a little bit.
     */
    protected function applyStateTransition(GideonSparringSession $session, string $agentMessage): void
    {
        $state = $session->state ?? [
            'trust'       => 40,
            'urgency'     => 30,
            'motivation'  => 40,
            'resistance'  => 60,
        ];

        $msgLower = mb_strtolower($agentMessage, 'UTF-8');

        // Basic patterns – can be refined later.
        if (str_contains($msgLower, 'i get that') || str_contains($msgLower, 'i hear')) {
            $state['trust']      = min(100, $state['trust'] + 8);
            $state['resistance'] = max(0,   $state['resistance'] - 5);
        }

        if (str_contains($agentMessage, '?')) {
            $state['motivation'] = min(100, $state['motivation'] + 5);
        }

        if (str_contains($msgLower, 'fast forward') || str_contains($msgLower, 'imagine')) {
            $state['urgency'] = min(100, $state['urgency'] + 5);
        }

        if (str_contains($msgLower, 'price') || str_contains($msgLower, 'budget')) {
            $state['resistance'] = max(0, $state['resistance'] + 3);
        }

        $session->state = $state;
        $session->save();
    }

    /**
     * Make sure Gideon doesn't just say the exact same thing twice in a row.
     *
     * If the candidate reply is essentially identical to the previous Gideon
     * message, we add a small variation or tweak the wording.
     */
    protected function ensureNonRepeatingReply(
        GideonSparringSession $session,
        string $candidate,
        string $agentMessage,
        ?GideonScenario $scenario
    ): string {
        $lastGideon = GideonSparringMessage::where('session_id', $session->id)
            ->where('sender', 'gideon')
            ->orderByDesc('id')
            ->first();

        if (! $lastGideon) {
            return $candidate;
        }

        $prev = trim($lastGideon->content);
        $curr = trim($candidate);

        // If content is identical (or extremely close), nudge the text a bit.
        if ($prev === $curr) {
            $scenarioLabel = $scenario?->name ?? 'this situation';

            // Simple alternates we can fall back to.
            $alternates = [
                "You’re moving the conversation forward. As the prospect in {$scenarioLabel}, I’m still weighing things out though. What else would you ask me now to really get at what’s keeping me cautious?",
                "I’m tracking with you more now than before, but there’s still something I’m wrestling with. What follow-up question would you ask so I can say out loud what’s really on my mind?",
                "That helps, but if I’m honest I’m still a bit stuck. What question would you ask next so I feel safe telling you what I’m actually afraid might happen if I move ahead?",
            ];

            // If we have alternates, just pick one based on the session id to keep it deterministic.
            $index    = $session->id % count($alternates);
            $candidate = $alternates[$index];
        }

        return $candidate;
    }
}
