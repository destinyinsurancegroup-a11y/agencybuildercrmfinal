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
     */
    public function listScenariosForUi(): Collection
    {
        return GideonScenario::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Start a new sparring session based on a scenario + persona.
     *
     * @return array{session: GideonSparringSession, first_message: GideonSparringMessage|null}
     */
    public function startSession(
        ?int $agencyId,
        int $userId,
        string $scenarioCode,
        string $mode = 'prospect_simulation',
        string $personaKey = 'adaptive'
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

            // Initial emotional state based on persona + scenario type
            $state = $this->initialStateForPersonaAndScenario($personaKey, $scenario);

            $session = GideonSparringSession::create([
                'agency_id'   => $agencyId,
                'user_id'     => $userId,
                'mode'        => $mode,
                'persona_key' => $personaKey,
                'config'      => $config,
                'state'       => $state,
                'status'      => 'active',
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
                        'state'         => $state,
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
     * Handle an agent message + generate Gideon's reply with
     * dynamic objection logic and evolving emotional state.
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
            ->when($agencyId, fn ($q) => $q->where('agency_id', $agencyId))
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
            $agentMeta = [
                'analysis' => null, // will be filled below if needed
            ];

            $agentMsg = GideonSparringMessage::create([
                'session_id' => $session->id,
                'agency_id'  => $agencyId,
                'user_id'    => $userId,
                'sender'     => 'agent',
                'content'    => $agentMessage,
                'meta'       => $agentMeta,
            ]);

            // 2) Load / normalize state
            $state = $this->normalizeState($session->state);

            // 3) Analyze agent message (tags + state deltas)
            $analysis = $this->analyzeAgentMessage($agentMessage);

            // 4) Apply deltas to state (trust / urgency / motivation / resistance)
            $state = $this->applyStateDeltas($state, $analysis['deltas']);

            // Persist updated state on the session
            $session->state = $state;
            $session->save();

            // 5) Generate dynamic Gideon reply
            $replyText = $this->generateDynamicGideonReply(
                $session,
                $scenario,
                $agentMessage,
                $state,
                $analysis
            );

            $gideonMeta = [
                'source'        => 'dynamic_objection_v2',
                'scenario_code' => $scenario?->code,
                'state'         => $state,
                'analysis'      => $analysis,
            ];

            $gideonMsg = GideonSparringMessage::create([
                'session_id' => $session->id,
                'agency_id'  => $agencyId,
                'user_id'    => $userId,
                'sender'     => 'gideon',
                'content'    => $replyText,
                'meta'       => $gideonMeta,
            ]);

            return [
                'agent_message' => $agentMsg,
                'gideon_reply'  => $gideonMsg,
            ];
        });
    }

    /**
     * End a session and create an assessment based on final state.
     *
     * @return array{session: GideonSparringSession, assessment: GideonSparringAssessment}
     */
    public function endSession(
        int $sessionId,
        ?int $agencyId,
        int $userId,
    ): array {
        $session = GideonSparringSession::where('id', $sessionId)
            ->when($agencyId, fn ($q) => $q->where('agency_id', $agencyId))
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $state = $this->normalizeState($session->state);

        $session->update([
            'status'   => 'completed',
            'ended_at' => now(),
            'state'    => $state,
        ]);

        // Very simple scoring based on final state for now.
        // Later we can fold in conversation-wide analysis.
        $scores = $this->scoreFromFinalState($state);

        $strengthsText   = $this->buildStrengthsText($scores, $state);
        $improvementsText = $this->buildImprovementsText($scores, $state);

        $assessment = GideonSparringAssessment::create([
            'session_id'  => $session->id,
            'agency_id'   => $agencyId,
            'user_id'     => $userId,
            'scores'      => $scores,
            'strengths'   => $strengthsText,
            'improvements'=> $improvementsText,
            'meta'        => [
                'state' => $state,
            ],
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
            ->when($agencyId, fn ($q) => $q->where('agency_id', $agencyId))
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

    // ---------------------------------------------------------------------
    //  INTERNAL HELPERS
    // ---------------------------------------------------------------------

    /**
     * Normalize state to a consistent array shape.
     *
     * @param  mixed  $raw
     * @return array{trust:int, urgency:int, motivation:int, resistance:int}
     */
    protected function normalizeState($raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw     = is_array($decoded) ? $decoded : [];
        }

        $raw = is_array($raw) ? $raw : [];

        return [
            'trust'       => $this->clampStateValue((int) ($raw['trust'] ?? 40)),
            'urgency'     => $this->clampStateValue((int) ($raw['urgency'] ?? 30)),
            'motivation'  => $this->clampStateValue((int) ($raw['motivation'] ?? 45)),
            'resistance'  => $this->clampStateValue((int) ($raw['resistance'] ?? 55)),
        ];
    }

    protected function clampStateValue(int $value): int
    {
        return max(0, min(100, $value));
    }

    /**
     * Initial emotional state based on persona & scenario type.
     */
    protected function initialStateForPersonaAndScenario(
        string $personaKey,
        GideonScenario $scenario
    ): array {
        // Persona baseline
        switch ($personaKey) {
            case 'soft_conflict_avoidant':
                $state = [
                    'trust'      => 35,
                    'urgency'    => 30,
                    'motivation' => 45,
                    'resistance' => 65,
                ];
                break;

            case 'skeptical_guarded':
                $state = [
                    'trust'      => 25,
                    'urgency'    => 35,
                    'motivation' => 40,
                    'resistance' => 75,
                ];
                break;

            case 'neutral_realistic':
                $state = [
                    'trust'      => 40,
                    'urgency'    => 40,
                    'motivation' => 50,
                    'resistance' => 55,
                ];
                break;

            case 'adaptive':
            default:
                $state = [
                    'trust'      => 40,
                    'urgency'    => 45,
                    'motivation' => 55,
                    'resistance' => 50,
                ];
                break;
        }

        // Scenario tweak
        $type = $this->classifyScenarioType($scenario);

        if ($type === 'price') {
            $state['resistance'] += 5;
        } elseif ($type === 'no_urgency') {
            $state['urgency'] -= 10;
        } elseif ($type === 'competition') {
            $state['trust'] -= 5;
        } elseif ($type === 'spouse') {
            $state['trust'] -= 5;
            $state['motivation'] += 5;
        } elseif ($type === 'think_it_over') {
            $state['resistance'] += 5;
        }

        // Clamp
        foreach ($state as $k => $v) {
            $state[$k] = $this->clampStateValue((int) $v);
        }

        return $state;
    }

    /**
     * Attempt to classify scenario into a simple bucket.
     */
    protected function classifyScenarioType(?GideonScenario $scenario): string
    {
        if (! $scenario) {
            return 'generic';
        }

        $code = strtolower($scenario->code ?? '');
        $name = strtolower($scenario->name ?? '');

        foreach (['price', 'too_expensive'] as $needle) {
            if (str_contains($code, 'price') || str_contains($name, 'price')) {
                return 'price';
            }
        }

        if (str_contains($code, 'competition') || str_contains($name, 'competition')) {
            return 'competition';
        }

        if (str_contains($code, 'spouse') || str_contains($name, 'spouse')) {
            return 'spouse';
        }

        if (str_contains($code, 'no_urgency') || str_contains($name, 'urgency')) {
            return 'no_urgency';
        }

        if (str_contains($code, 'think_it_over') || str_contains($name, 'think it over')) {
            return 'think_it_over';
        }

        return 'generic';
    }

    /**
     * Analyze an agent message: detect empathy, questions, pressure, etc.
     *
     * @return array{
     *   tags: array<string,bool>,
     *   deltas: array{trust:int,urgency:int,motivation:int,resistance:int}
     * }
     */
    protected function analyzeAgentMessage(string $message): array
    {
        $text = mb_strtolower($message);

        $tags = [
            'empathy'        => false,
            'validation'     => false,
            'question'       => false,
            'future_focus'   => false,
            'close_attempt'  => false,
            'pressure'       => false,
            'price_focus'    => false,
            'partner_focus'  => false,
            'competition'    => false,
        ];

        // Very simple pattern checks
        if (preg_match('/(i get|i hear|i understand|makes sense|i can see)/', $text)) {
            $tags['empathy'] = true;
        }

        if (preg_match('/(sounds like|feels like|what i\'m hearing)/', $text)) {
            $tags['validation'] = true;
        }

        if (str_contains($text, '?')) {
            $tags['question'] = true;
        }

        if (preg_match('/(when you think about|down the road|long term|a year from now|in the future)/', $text)) {
            $tags['future_focus'] = true;
        }

        if (preg_match('/(let\'s do this|get you started|move forward|get this in place|go ahead and)/', $text)) {
            $tags['close_attempt'] = true;
        }

        if (preg_match('/(you need to|you have to|you should just|honestly you)/', $text)) {
            $tags['pressure'] = true;
        }

        if (preg_match('/(price|cost|expensive|cheaper|too much)/', $text)) {
            $tags['price_focus'] = true;
        }

        if (preg_match('/(spouse|husband|wife|partner|talk to.*first)/', $text)) {
            $tags['partner_focus'] = true;
        }

        if (preg_match('/(other company|already have someone|work with someone else|another agent)/', $text)) {
            $tags['competition'] = true;
        }

        // Map tags to emotional deltas
        $deltas = [
            'trust'      => 0,
            'urgency'    => 0,
            'motivation' => 0,
            'resistance' => 0,
        ];

        if ($tags['empathy'] || $tags['validation']) {
            $deltas['trust']      += 8;
            $deltas['resistance'] -= 6;
        }

        if ($tags['question']) {
            // Good discovery tends to help motivation & trust
            $deltas['motivation'] += 4;
            $deltas['trust']      += 2;
        }

        if ($tags['future_focus']) {
            $deltas['motivation'] += 5;
            $deltas['urgency']    += 3;
        }

        if ($tags['close_attempt']) {
            // If attempted too early, this may backfire; we don't know the timing,
            // so we blend: slight urgency bump but also potential resistance.
            $deltas['urgency']    += 5;
            $deltas['resistance'] += 4;
        }

        if ($tags['pressure']) {
            $deltas['trust']      -= 6;
            $deltas['resistance'] += 8;
        }

        // Focus on the real objection can actually reduce resistance a bit (naming it out loud)
        if ($tags['price_focus'] || $tags['partner_focus'] || $tags['competition']) {
            $deltas['resistance'] -= 2;
        }

        return [
            'tags'   => $tags,
            'deltas' => $deltas,
        ];
    }

    /**
     * Apply state deltas and clamp.
     */
    protected function applyStateDeltas(array $state, array $deltas): array
    {
        foreach ($deltas as $k => $delta) {
            if (! array_key_exists($k, $state)) {
                continue;
            }
            $state[$k] = $this->clampStateValue((int) ($state[$k] + $delta));
        }

        return $state;
    }

    /**
     * Generate Gideon's reply based on scenario type, updated state, and agent message tags.
     */
    protected function generateDynamicGideonReply(
        GideonSparringSession $session,
        ?GideonScenario $scenario,
        string $agentMessage,
        array $state,
        array $analysis
    ): string {
        $type = $this->classifyScenarioType($scenario);
        $tags = $analysis['tags'];

        $trust      = $state['trust'];
        $urgency    = $state['urgency'];
        $motivation = $state['motivation'];
        $resistance = $state['resistance'];

        $isHighResistance = $resistance >= 70;
        $isMediumResistance = $resistance >= 45 && $resistance < 70;
        $isLowResistance = $resistance < 45;

        $isHighTrust = $trust >= 60;
        $isLowTrust  = $trust < 35;

        // We'll use slightly different cores per scenario and resistance band.
        $core = '';

        switch ($type) {
            case 'price':
                if ($isHighResistance) {
                    $core = "Honestly, the price is still the thing that’s holding me back. "
                        . "I’m trying to be sensible and not overreact, but I’m not at a place yet where I feel comfortable with that payment.";
                } elseif ($isMediumResistance) {
                    $core = "Parts of what you’re saying make sense, but I’m still weighing the price. "
                        . "I don’t want to put myself in a spot where I’m overcommitting and regret it later.";
                } else { // low resistance
                    $core = "I’m feeling better about the price than I was at first. "
                        . "I just want to be sure I’m not missing anything and that this really is a smart move for my budget.";
                }
                break;

            case 'competition':
                if ($isHighResistance) {
                    $core = "We already have someone we work with, and I’d hate to disrupt that unless I’m absolutely convinced it’s worth it. "
                        . "Right now I’m not there yet.";
                } elseif ($isMediumResistance) {
                    $core = "I like some of what you’re saying, but we do already have someone in place. "
                        . "I’d need a really clear reason why changing from them to you would be worth the hassle.";
                } else {
                    $core = "I’m not closed off to the idea of changing, I just need to clearly see what would be better with you than what we have now.";
                }
                break;

            case 'spouse':
                if ($isHighResistance) {
                    $core = "This isn’t something I’m comfortable deciding on my own. "
                        . "If my spouse isn’t on board, it’s going to cause friction at home, and that’s a big deal to me.";
                } elseif ($isMediumResistance) {
                    $core = "I’m interested, but I really do need my spouse to feel good about this too. "
                        . "If they’re hesitant, it’s going to slow everything down.";
                } else {
                    $core = "I feel mostly good about this, I just want to make sure when I bring it to my spouse it doesn’t feel like I’m pushing them into something.";
                }
                break;

            case 'no_urgency':
                if ($isHighResistance) {
                    $core = "I just don’t feel like this has to be handled right now. "
                        . "There are other things that feel more urgent, and I’m worried about stretching myself too thin.";
                } elseif ($isMediumResistance) {
                    $core = "I can see the value, but it doesn’t quite feel like a ‘right now’ decision. "
                        . "It’s more like something I’d think about for later.";
                } else {
                    $core = "I’m starting to see why waiting might not be the best idea, I just need a bit more clarity on why now versus six months from now.";
                }
                break;

            case 'think_it_over':
            case 'generic':
            default:
                if ($isHighResistance) {
                    $core = "I’m still not ready to move forward. "
                        . "I feel like I need more time to think this over before I say yes or no.";
                } elseif ($isMediumResistance) {
                    $core = "I’m somewhere in the middle. I see some good reasons to do this, "
                        . "but I also have a few things that don’t feel fully resolved yet.";
                } else {
                    $core = "I’m leaning more toward this making sense, I just want to be sure I’ve asked the right questions before I commit.";
                }
                break;
        }

        // Add color based on trust and tags
        $color = '';

        if ($tags['empathy'] || $tags['validation']) {
            $color .= " I do appreciate that you’re actually trying to understand where I’m coming from.";
        } elseif ($tags['pressure']) {
            $color .= " When I feel pushed, my instinct is to slow down or back away, and I don’t want to feel pressured into this.";
        }

        if ($tags['question']) {
            // They asked something; answer in a way that nudges them to go deeper
            $color .= " The way you’re asking questions is helping me sort through things, "
                . "but there might be one or two deeper questions you could ask that would really get to the heart of what’s holding me back.";
        } else {
            // No question – invite them to ask more
            $color .= " If you were in my shoes, what would you ask me next to really understand what’s still in the way?";
        }

        if ($isLowTrust) {
            $color .= " A big part of this for me is feeling like I can genuinely trust who I’m working with, not just the numbers on paper.";
        } elseif ($isHighTrust && $isLowResistance) {
            $color .= " I’m not doubting you personally; I just want to be sure the timing and the details are truly right for me.";
        }

        return trim($core . ' ' . $color);
    }

    /**
     * Score the session based on final state.
     */
    protected function scoreFromFinalState(array $state): array
    {
        // Simple mapping: 0–100 -> 1–5
        $map = function (int $value): int {
            if ($value >= 80) return 5;
            if ($value >= 60) return 4;
            if ($value >= 40) return 3;
            if ($value >= 20) return 2;
            return 1;
        };

        $rapport       = $map($state['trust']);
        $discovery     = $map((int) (($state['motivation'] + $state['trust']) / 2));
        $dealKillers   = $map(100 - $state['resistance']);
        $closingClarity= $map((int) (($state['motivation'] + $state['urgency']) / 2));

        return [
            'rapport'        => $rapport,
            'discovery'      => $discovery,
            'deal_killers'   => $dealKillers,
            'closing_clarity'=> $closingClarity,
        ];
    }

    protected function buildStrengthsText(array $scores, array $state): string
    {
        $parts = [];

        if ($scores['rapport'] >= 4) {
            $parts[] = "You did a solid job building rapport and helping the prospect feel heard.";
        } elseif ($scores['rapport'] >= 3) {
            $parts[] = "There was some rapport, especially when you slowed down and validated what they were feeling.";
        } else {
            $parts[] = "You’re starting to build rapport, and moments of empathy are helping, even if it’s not fully consistent yet.";
        }

        if ($scores['discovery'] >= 4) {
            $parts[] = "Your questions opened up useful information and helped the prospect think things through.";
        } elseif ($scores['discovery'] >= 3) {
            $parts[] = "You asked some helpful questions that nudged the conversation forward.";
        } else {
            $parts[] = "You asked a few questions that moved things a bit, even if you left some room for deeper discovery.";
        }

        return implode(' ', $parts);
    }

    protected function buildImprovementsText(array $scores, array $state): string
    {
        $parts = [];

        if ($scores['rapport'] <= 3) {
            $parts[] = "Spend more time reflecting back what the prospect is feeling before you pivot back to the solution.";
        }

        if ($scores['discovery'] <= 3) {
            $parts[] = "Ask more open-ended questions about what they value, what they’re worried about, and what would have to be true for them to feel good moving forward.";
        }

        if ($scores['deal_killers'] <= 3) {
            $parts[] = "Slow down and name the real deal-killer out loud, then ask how big of a barrier it really is on a 1–10 scale.";
        }

        if ($scores['closing_clarity'] <= 3) {
            $parts[] = "End with a simple, clear next step so the prospect knows exactly what moving forward looks like.";
        }

        return implode(' ', $parts);
    }
}
