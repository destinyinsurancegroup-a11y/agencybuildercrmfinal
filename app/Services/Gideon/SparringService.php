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
     * UI role modes (NOT the 3 training modes).
     * - prospect_simulation: user plays AGENT, system plays PROSPECT
     * - agent_simulation:    user plays PROSPECT, system plays AGENT
     */
    public const UI_MODE_PROSPECT_SIM = 'prospect_simulation';
    public const UI_MODE_AGENT_SIM    = 'agent_simulation';

    public function __construct(
        protected GideonLlmClient $llmClient,
        protected CoachingService $coachingService
    ) {
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, GideonScenario>
     */
    public function listScenariosForUi(): Collection
    {
        return GideonScenario::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array{session: GideonSparringSession, first_message: GideonSparringMessage|null}
     */
    public function startSession(
        int $agencyId,
        int $userId,
        string $scenarioCode,
        string $uiMode = self::UI_MODE_PROSPECT_SIM,
        string $personaKey = 'adaptive'
    ): array {
        $scenario = GideonScenario::where('code', $scenarioCode)->first();

        if (! $scenario) {
            throw ValidationException::withMessages([
                'scenario_code' => ['Invalid scenario code.'],
            ]);
        }

        $uiMode = $this->normalizeUiMode($uiMode);

        return DB::transaction(function () use ($agencyId, $userId, $scenario, $uiMode, $personaKey) {
            $config = [
                'scenario_code' => $scenario->code,

                // Store UI mode in config so we don’t confuse it with training_mode
                'ui_mode'       => $uiMode,
                'persona'       => $personaKey,
            ];

            $initialState = $this->buildInitialState($scenario, $personaKey);

            $prospectProfile = $scenario->prospect_profile ?? [];
            $personaFromScenario = is_array($prospectProfile)
                ? Arr::get($prospectProfile, 'persona')
                : null;

            $session = GideonSparringSession::create([
                'agency_id'   => $agencyId,
                'user_id'     => $userId,

                // legacy column: keep set to ui mode for backwards compatibility
                'mode'        => $uiMode,

                'persona_key' => $personaFromScenario ?: $personaKey,
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

            /**
             * Only inject an opening line when system is the PROSPECT (prospect_simulation).
             */
            if ($uiMode === self::UI_MODE_PROSPECT_SIM && $openingLine) {
                $firstMessage = GideonSparringMessage::create([
                    'session_id' => $session->id,
                    'agency_id'  => $agencyId,
                    'user_id'    => $userId,
                    'role'       => 'prospect', // ✅ correct column + consistent role
                    'content'    => $openingLine,
                    'meta'       => [
                        'source'        => 'scenario_opening_line',
                        'scenario_code' => $scenario->code,
                        'ui_mode'       => $uiMode,
                    ],
                ]);

                $state = $session->state ?? [];
                $state['last_system_line'] = $openingLine;
                $session->update(['state' => $state]);
            }

            return [
                'session'       => $session->fresh(),
                'first_message' => $firstMessage,
            ];
        });
    }

    /**
     * NOTE: method name kept for API compatibility.
     *
     * @return array{agent_message: GideonSparringMessage, gideon_reply: GideonSparringMessage}
     */
    public function handleAgentMessage(
        int $sessionId,
        int $agencyId,
        int $userId,
        string $agentMessage
    ): array {
        $session = GideonSparringSession::query()
            ->whereKey($sessionId)
            ->where('agency_id', $agencyId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        if ($session->status !== 'active') {
            throw ValidationException::withMessages([
                'session' => ['This session is not active.'],
            ]);
        }

        // ui mode: read from config first, fall back to legacy session->mode
        $config = $session->config ?? [];
        $uiMode = $this->normalizeUiMode(
            is_array($config) ? ($config['ui_mode'] ?? ($session->mode ?? self::UI_MODE_PROSPECT_SIM)) : ($session->mode ?? self::UI_MODE_PROSPECT_SIM)
        );

        $scenarioCode = is_array($config) ? ($config['scenario_code'] ?? null) : null;

        $scenario = $scenarioCode
            ? GideonScenario::where('code', $scenarioCode)->first()
            : null;

        $useLlm = (bool) config('gideon.enabled', false)
            && (bool) config('gideon.sparring.llm_enabled', false);

        return DB::transaction(function () use (
            $session,
            $agencyId,
            $userId,
            $agentMessage,
            $scenario,
            $useLlm,
            $uiMode
        ) {
            $state = $session->state ?? [];

            /**
             * Who is the user playing?
             * - prospect_simulation: userRole=agent, systemRole=prospect
             * - agent_simulation:    userRole=prospect, systemRole=agent
             */
            $userRole   = ($uiMode === self::UI_MODE_PROSPECT_SIM) ? 'agent' : 'prospect';
            $systemRole = ($uiMode === self::UI_MODE_PROSPECT_SIM) ? 'prospect' : 'agent';

            // 1) Store user message
            $userMsg = GideonSparringMessage::create([
                'session_id' => $session->id,
                'agency_id'  => $agencyId,
                'user_id'    => $userId,
                'role'       => $userRole,
                'content'    => $agentMessage,
                'meta'       => [
                    'analysis' => null,
                    'ui_mode'  => $uiMode,
                ],
            ]);

            // 2) Update state only when the AGENT speaks (agent role)
            if ($userRole === 'agent') {
                $state = $this->updateStateFromAgentMessage($state, $agentMessage);
            }

            // 3) Generate reply
            $replyText = null;
            $source    = 'scenario_v1_logic_with_state';

            if ($useLlm && $scenario) {
                try {
                    $context   = $this->buildLlmContextForSparring($session, $scenario, $state, $agentMessage, $uiMode);
                    $replyText = $this->llmClient->generateSparringReply($context);
                    $source    = 'llm_v1_sparring';
                } catch (\Throwable $e) {
                    report($e);
                    $replyText = null;
                }
            }

            if ($replyText === null || trim($replyText) === '') {
                if ($systemRole === 'prospect') {
                    $replyText = $this->generateGideonReply($session, $scenario, $agentMessage, $state);
                    $source    = 'scenario_v1_logic_with_state';
                } else {
                    // system is agent (agent_simulation) fallback
                    $replyText = $this->generateAgentFallbackReply($scenario, $agentMessage);
                    $source    = 'agent_sim_fallback';
                }
            }

            // 4) If system reply is the AGENT, update state from that reply
            if ($systemRole === 'agent') {
                $state = $this->updateStateFromAgentMessage($state, $replyText);
            }

            // 5) Save system reply
            $systemMsg = GideonSparringMessage::create([
                'session_id' => $session->id,
                'agency_id'  => $agencyId,
                'user_id'    => $userId,
                'role'       => $systemRole,
                'content'    => $replyText,
                'meta'       => [
                    'source'        => $source,
                    'scenario_code' => $scenario?->code,
                    'state'         => $state,
                    'ui_mode'       => $uiMode,
                ],
            ]);

            // 6) Update session state
            $state['last_system_line'] = $replyText;
            $session->update(['state' => $state]);

            return [
                'agent_message' => $userMsg,
                'gideon_reply'  => $systemMsg,
            ];
        });
    }

    /**
     * @return array{session: GideonSparringSession, assessment: GideonSparringAssessment}
     */
    public function endSession(
        int $sessionId,
        int $agencyId,
        int $userId
    ): array {
        $session = GideonSparringSession::query()
            ->whereKey($sessionId)
            ->where('agency_id', $agencyId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $session->update([
            'status'   => 'completed',
            'ended_at' => now(),
        ]);

        $config = $session->config ?? [];
        $uiMode = $this->normalizeUiMode(
            is_array($config) ? ($config['ui_mode'] ?? ($session->mode ?? self::UI_MODE_PROSPECT_SIM)) : ($session->mode ?? self::UI_MODE_PROSPECT_SIM)
        );
        $state = $session->state ?? [];

        $scores = [
            'rapport'         => $this->scoreFromState($state, 'trust'),
            'discovery'       => $this->scoreFromState($state, 'motivation'),
            'deal_killers'    => $this->scoreFromStateInverse($state, 'resistance'),
            'closing_clarity' => $this->scoreFromState($state, 'urgency'),
        ];

        $strengths = null;
        $improvements = null;

        $scenarioCode = is_array($config) ? ($config['scenario_code'] ?? null) : null;
        $scenario = $scenarioCode ? GideonScenario::where('code', $scenarioCode)->first() : null;

        // Coaching only makes sense when user was the agent (prospect_simulation => userRole=agent)
        if ($uiMode === self::UI_MODE_PROSPECT_SIM) {
            try {
                $llmResult = $this->coachingService->generateAssessmentNarrative($session, $scenario, $scores);
                if (is_array($llmResult) && count($llmResult) === 2) {
                    [$strengths, $improvements] = $llmResult;
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if (! $strengths || ! $improvements) {
            [$strengths, $improvements] = $this->buildAssessmentNarrative($scores);
        }

        $assessment = GideonSparringAssessment::create([
            'session_id'   => $session->id,
            'agency_id'    => $agencyId,
            'user_id'      => $userId,
            'scores'       => $scores,
            'strengths'    => $strengths,
            'improvements' => $improvements,
            'meta'         => [
                'state'   => $state,
                'ui_mode' => $uiMode,
            ],
        ]);

        return [
            'session'    => $session->fresh(),
            'assessment' => $assessment,
        ];
    }

    /**
     * @return array{session: GideonSparringSession, messages: Collection<int, GideonSparringMessage>}
     */
    public function getSessionTranscript(
        int $sessionId,
        int $agencyId,
        int $userId
    ): array {
        $session = GideonSparringSession::query()
            ->whereKey($sessionId)
            ->where('agency_id', $agencyId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $messages = GideonSparringMessage::query()
            ->where('session_id', $session->id)
            ->orderBy('id')
            ->get();

        return [
            'session'  => $session,
            'messages' => $messages,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | INTERNAL: MODE / STATE / LOGIC
    |--------------------------------------------------------------------------
    */

    protected function normalizeUiMode(?string $mode): string
    {
        $mode = (string) $mode;

        if ($mode === self::UI_MODE_AGENT_SIM) {
            return self::UI_MODE_AGENT_SIM;
        }

        return self::UI_MODE_PROSPECT_SIM;
    }

    protected function buildInitialState(?GideonScenario $scenario, string $personaKey): array
    {
        $state = [
            'trust'       => 35,
            'urgency'     => 30,
            'motivation'  => 40,
            'resistance'  => 60,
            'phase'       => 'opening',
            'turn'        => 0,
            'last_tactic' => null,
        ];

        if ($scenario && is_array($scenario->prospect_profile ?? null)) {
            $baseline = Arr::get($scenario->prospect_profile, "baseline_state.{$personaKey}")
                ?? Arr::get($scenario->prospect_profile, 'baseline_state.default');

            if (is_array($baseline)) {
                $state = array_merge($state, Arr::only($baseline, [
                    'trust', 'urgency', 'motivation', 'resistance',
                ]));
            }
        }

        switch ($personaKey) {
            case 'soft_conflict_avoidant':
                $state['trust']      = $state['trust'] + 5;
                $state['resistance'] = $state['resistance'] + 5;
                break;
            case 'skeptical_guarded':
                $state['trust']      = $state['trust'] - 5;
                $state['resistance'] = $state['resistance'] + 10;
                break;
            case 'adaptive':
                $state['trust']      = $state['trust'] + 5;
                $state['motivation'] = $state['motivation'] + 5;
                break;
        }

        foreach (['trust', 'urgency', 'motivation', 'resistance'] as $key) {
            $state[$key] = $this->clamp($state[$key] ?? 50, 0, 100);
        }

        return $state;
    }

    protected function updateStateFromAgentMessage(array $state, string $agentMessage): array
    {
        $text  = mb_strtolower($agentMessage);
        $turn  = (int) ($state['turn'] ?? 0);
        $turn += 1;
        $state['turn'] = $turn;

        $isQuestion = str_contains($agentMessage, '?');

        $rapportWords  = ['i hear you', 'i get that', 'i understand', 'makes sense', 'totally', 'thank you', 'appreciate'];
        $pressureWords = ['have to decide', 'now or never', 'last chance', 'only today', 'must', 'need to sign'];
        $moneyWords    = ['price', 'cost', 'expensive', 'budget', 'afford'];
        $riskWords     = ['worried', 'risk', 'afraid', 'concern', 'scared', 'nervous'];
        $clarityWords  = ['next step', 'moving forward', 'what happens', 'how it works', 'process'];

        foreach ($rapportWords as $needle) {
            if (str_contains($text, $needle)) {
                $state['trust']      = ($state['trust'] ?? 50) + 4;
                $state['resistance'] = ($state['resistance'] ?? 50) - 2;
            }
        }

        foreach ($pressureWords as $needle) {
            if (str_contains($text, $needle)) {
                $state['trust']      = ($state['trust'] ?? 50) - 5;
                $state['resistance'] = ($state['resistance'] ?? 50) + 6;
            }
        }

        foreach ($moneyWords as $needle) {
            if (str_contains($text, $needle)) {
                $state['motivation'] = ($state['motivation'] ?? 50) + 3;
            }
        }

        if ($isQuestion) {
            foreach ($riskWords as $needle) {
                if (str_contains($text, $needle)) {
                    $state['trust']      = ($state['trust'] ?? 50) + 2;
                    $state['motivation'] = ($state['motivation'] ?? 50) + 3;
                }
            }
        }

        foreach ($clarityWords as $needle) {
            if (str_contains($text, $needle)) {
                $state['urgency'] = ($state['urgency'] ?? 50) + 3;
            }
        }

        if ($isQuestion) {
            $state['motivation'] = ($state['motivation'] ?? 50) + 2;
        }

        foreach (['trust', 'urgency', 'motivation', 'resistance'] as $key) {
            $state[$key] = $this->clamp($state[$key] ?? 50, 0, 100);
        }

        if ($turn <= 2) {
            $state['phase'] = 'opening';
        } elseif ($turn <= 4) {
            $state['phase'] = 'deepen';
        } elseif ($turn <= 6) {
            $state['phase'] = 'reframe';
        } else {
            $state['phase'] = 'close_soft';
        }

        if (($state['resistance'] ?? 60) > 70 && $state['phase'] === 'close_soft') {
            $state['phase'] = 'reframe';
        }

        return $state;
    }

    protected function generateGideonReply(
        GideonSparringSession $session,
        ?GideonScenario $scenario,
        string $agentMessage,
        array $state
    ): string {
        $phase  = $state['phase'] ?? 'opening';
        $turn   = (int) ($state['turn'] ?? 0);
        $engine = $scenario?->script_engine ?? [];

        $phaseToKey = [
            'opening'    => 'validate_and_open',
            'deepen'     => 'deepen_questions',
            'reframe'    => 'reframe_and_value',
            'close_soft' => 'soft_close',
        ];

        $tacticKey = $phaseToKey[$phase] ?? null;

        $linesFromScenario = [];
        if ($tacticKey && is_array($engine)) {
            $linesFromScenario = Arr::get($engine, "tactics.{$tacticKey}", []);
        }

        $lastLine = $state['last_system_line'] ?? null;

        if (! empty($linesFromScenario) && is_array($linesFromScenario)) {
            $index = $turn % max(1, count($linesFromScenario));
            $candidate = trim((string) $linesFromScenario[$index]);

            if ($candidate && $candidate !== $lastLine) {
                return $candidate;
            }
        }

        switch ($phase) {
            case 'opening':
                return $this->avoidRepeat(
                    $lastLine,
                    "I hear you. I’m just not sure I want to change anything yet. What’s the one thing you think I’m missing?",
                    "That makes sense… I’m still a little guarded here. What would you want to know from me before we even compare options?"
                );

            case 'deepen':
                return $this->avoidRepeat(
                    $lastLine,
                    "Okay, but help me understand—what would actually be different for me if I switched?",
                    "I’m listening. I just don’t want to get talked into something. What’s the simplest way to compare this to what I already have?"
                );

            case 'reframe':
                return $this->avoidRepeat(
                    $lastLine,
                    "Part of me gets it, and part of me is still stuck. What happens if I do nothing and keep things the same?",
                    "I’m not saying no… I just need to feel like this isn’t a mistake. What would make this feel safer?"
                );

            case 'close_soft':
            default:
                return $this->avoidRepeat(
                    $lastLine,
                    "Alright. If we did take a next step, what does that look like—just the simple version?",
                    "I’m close, but I need one last thing to feel good about it. What would you ask me right now?"
                );
        }
    }

    protected function generateAgentFallbackReply(?GideonScenario $scenario, string $latestProspectMessage): string
    {
        $scenarioLabel = $scenario?->name ?? 'this situation';

        return "Got it — thanks for sharing that. To make sure I’m helping you the right way in {$scenarioLabel}, what matters most to you here: keeping price low, making sure coverage is stronger, or avoiding surprises later?";
    }

    protected function buildLlmContextForSparring(
        GideonSparringSession $session,
        GideonScenario $scenario,
        array $state,
        string $latestUserMessage,
        ?string $uiMode = null
    ): array {
        $uiMode = $this->normalizeUiMode($uiMode ?? ($session->config['ui_mode'] ?? ($session->mode ?? self::UI_MODE_PROSPECT_SIM)));

        $historyLimit = (int) config('gideon.sparring.history_limit', 8);

        $messages = GideonSparringMessage::query()
            ->where('session_id', $session->id)
            ->orderBy('id', 'desc')
            ->take($historyLimit)
            ->get()
            ->sortBy('id')
            ->values();

        $recent = [];
        foreach ($messages as $m) {
            $recent[] = [
                'role'    => $m->role, // ✅ correct column
                'content' => $m->content,
            ];
        }

        return [
            'ui_mode' => $uiMode,

            // let the LlmClient choose system prompt persona
            'requested_role' => ($uiMode === self::UI_MODE_PROSPECT_SIM) ? 'prospect' : 'agent',

            'scenario' => [
                'code'             => $scenario->code,
                'name'             => $scenario->name,
                'product_type'     => $scenario->product_type,
                'description'      => $scenario->description,
                'prospect_profile' => $scenario->prospect_profile ?? [],
            ],
            'session' => [
                'id'          => $session->id,
                'agency_id'   => $session->agency_id,
                'user_id'     => $session->user_id,
                'mode'        => $session->mode,
                'persona_key' => $session->persona_key ?? ($session->config['persona'] ?? null),
            ],
            'state'               => $state,
            'recent_messages'     => $recent,
            'latest_user_message' => $latestUserMessage,
        ];
    }

    protected function avoidRepeat(?string $lastLine, string ...$options): string
    {
        foreach ($options as $line) {
            if ($line !== $lastLine) {
                return $line;
            }
        }
        return $options[0];
    }

    protected function scoreFromState(array $state, string $key): int
    {
        $value = (int) ($state[$key] ?? 50);
        $value = $this->clamp($value, 0, 100);

        return (int) max(1, min(5, ceil(($value + 1) / 20)));
    }

    protected function scoreFromStateInverse(array $state, string $key): int
    {
        $value = (int) ($state[$key] ?? 50);
        $value = $this->clamp($value, 0, 100);
        $flipped = 100 - $value;

        return (int) max(1, min(5, ceil(($flipped + 1) / 20)));
    }

    protected function buildAssessmentNarrative(array $scores): array
    {
        $strengthsParts = [];
        $improvementParts = [];

        if ($scores['rapport'] >= 4) {
            $strengthsParts[] = 'You built good rapport by slowing down and validating what the prospect was feeling.';
        } elseif ($scores['rapport'] >= 3) {
            $strengthsParts[] = 'There was some rapport, especially when you reflected back what the prospect said.';
            $improvementParts[] = 'You could deepen rapport by naming their emotions out loud and asking how it feels from their side.';
        } else {
            $improvementParts[] = 'Focus more on rapport early on: reflect back what they say and check if you’re understanding them correctly.';
        }

        if ($scores['discovery'] >= 4) {
            $strengthsParts[] = 'You asked helpful questions that uncovered what really matters to them.';
        } elseif ($scores['discovery'] >= 3) {
            $strengthsParts[] = 'You asked some discovery questions.';
            $improvementParts[] = 'Go deeper with “what else?” and “what would have to be true for this to feel right to you?” questions.';
        } else {
            $improvementParts[] = 'Spend more time on discovery before you pivot back to the solution. Stay with their concerns longer.';
        }

        if ($scores['deal_killers'] >= 4) {
            $strengthsParts[] = 'You did a solid job getting deal-killers out into the open.';
        } elseif ($scores['deal_killers'] >= 3) {
            $strengthsParts[] = 'You touched on possible deal-killers.';
            $improvementParts[] = 'Ask more directly about what would stop them from moving forward and how big of a barrier it feels like.';
        } else {
            $improvementParts[] = 'Name potential deal-killers explicitly and ask them to rate how big each one feels on a 1–10 scale.';
        }

        if ($scores['closing_clarity'] >= 4) {
            $strengthsParts[] = 'You gave a relatively clear path for what happens next.';
        } elseif ($scores['closing_clarity'] >= 3) {
            $strengthsParts[] = 'You hinted at next steps.';
            $improvementParts[] = 'Be more explicit about the next simple step so the prospect knows exactly what moving forward looks like.';
        } else {
            $improvementParts[] = 'Before wrapping up, always offer a simple, pressure-free next step so they’re not left in limbo.';
        }

        $strengths = $strengthsParts
            ? implode(' ', $strengthsParts)
            : 'Strengths will appear here as the coaching engine becomes more detailed.';

        $improvements = $improvementParts
            ? implode(' ', $improvementParts)
            : 'Improvements will appear here as the coaching engine becomes more detailed.';

        return [$strengths, $improvements];
    }

    protected function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }
}
