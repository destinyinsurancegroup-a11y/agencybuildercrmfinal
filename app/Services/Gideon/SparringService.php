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

    /**
     * Training Modes (3 modes)
     */
    public const TRAINING_STAGES          = 'stages';
    public const TRAINING_DISCOVERY_START = 'discovery_start';
    public const TRAINING_FULL            = 'full_presentation';

    /**
     * Selling stages
     */
    public const STAGE_INTRO     = 'intro';
    public const STAGE_DISCOVERY = 'discovery';
    public const STAGE_EDUCATION = 'education';
    public const STAGE_QUALIFY   = 'qualify';
    public const STAGE_QUOTE     = 'quote';
    public const STAGE_CLOSE     = 'close';

    /**
     * Difficulty
     */
    public const DIFF_EASY   = 'easy';
    public const DIFF_NORMAL = 'normal';
    public const DIFF_HARD   = 'hard';

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
     * ✅ EDITS:
     * - Accept training_mode / selected_stage / difficulty at session creation time (optional).
     * - Store them in BOTH: columns (if exist/fillable) + config JSON.
     * - Seed training state immediately so the first agent message behaves correctly.
     *
     * @return array{session: GideonSparringSession, first_message: GideonSparringMessage|null}
     */
    public function startSession(
        int $agencyId,
        int $userId,
        string $scenarioCode,
        string $uiMode = self::UI_MODE_PROSPECT_SIM,
        string $personaKey = 'adaptive',
        ?string $trainingMode = null,
        ?string $selectedStage = null,
        ?string $difficulty = null
    ): array {
        $scenario = GideonScenario::where('code', $scenarioCode)->first();

        if (! $scenario) {
            throw ValidationException::withMessages([
                'scenario_code' => ['Invalid scenario code.'],
            ]);
        }

        $uiMode = $this->normalizeUiMode($uiMode);

        // normalize training inputs (if provided)
        $trainingMode  = $trainingMode !== null ? $this->normalizeTrainingMode($trainingMode) : null;
        $difficulty    = $difficulty !== null ? $this->normalizeDifficulty($difficulty) : null;
        $selectedStage = $selectedStage !== null ? $this->normalizeStage($selectedStage) : null;

        return DB::transaction(function () use (
            $agencyId,
            $userId,
            $scenario,
            $uiMode,
            $personaKey,
            $trainingMode,
            $selectedStage,
            $difficulty
        ) {
            $config = [
                'scenario_code' => $scenario->code,

                // Store UI mode in config so we don’t confuse it with training_mode
                'ui_mode'       => $uiMode,
                'persona'       => $personaKey,
            ];

            // Persist training selections into config for traceability
            if ($trainingMode !== null)  $config['training_mode']  = $trainingMode;
            if ($selectedStage !== null) $config['selected_stage'] = $selectedStage;
            if ($difficulty !== null)    $config['difficulty']     = $difficulty;

            $initialState = $this->buildInitialState($scenario, $personaKey);

            $prospectProfile = $scenario->prospect_profile ?? [];
            $personaFromScenario = is_array($prospectProfile)
                ? Arr::get($prospectProfile, 'persona')
                : null;

            // If your sessions table has these columns, we set them.
            // If not, Eloquent will ignore them if not fillable (or you can remove).
            $create = [
                'agency_id'   => $agencyId,
                'user_id'     => $userId,

                // legacy column: keep set to ui mode for backwards compatibility
                'mode'        => $uiMode,

                'persona_key' => $personaFromScenario ?: $personaKey,
                'config'      => $config,
                'status'      => 'active',
                'state'       => $initialState,
                'started_at'  => now(),
            ];

            if ($trainingMode !== null)  $create['training_mode']  = $trainingMode;
            if ($selectedStage !== null) $create['selected_stage'] = $selectedStage;
            if ($difficulty !== null)    $create['difficulty']     = $difficulty;

            $session = GideonSparringSession::create($create);

            // ✅ Seed training state immediately, so first agent message uses correct stage/mode/difficulty
            $state = $session->state ?? [];
            $state = $this->ensureTrainingState($session, is_array($state) ? $state : []);
            $session->update(['state' => $state]);

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
                    'role'       => 'prospect',
                    'content'    => $openingLine,
                    'meta'       => [
                        'source'        => 'scenario_opening_line',
                        'scenario_code' => $scenario->code,
                        'ui_mode'       => $uiMode,
                        'training_mode' => $state['training']['mode'] ?? null,
                        'stage'         => $state['training']['current_stage'] ?? null,
                        'difficulty'    => $state['training']['difficulty'] ?? null,
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
            is_array($config)
                ? ($config['ui_mode'] ?? ($session->mode ?? self::UI_MODE_PROSPECT_SIM))
                : ($session->mode ?? self::UI_MODE_PROSPECT_SIM)
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
            $state = is_array($state) ? $state : [];

            /**
             * Bootstraps training state if missing.
             */
            $state = $this->ensureTrainingState($session, $state);

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
                    'analysis'       => null,
                    'ui_mode'        => $uiMode,
                    'stage'          => $state['training']['current_stage'] ?? null,
                    'training_mode'  => $state['training']['mode'] ?? null,
                    'difficulty'     => $state['training']['difficulty'] ?? null,
                ],
            ]);

            // 2) Update state only when the AGENT speaks (agent role)
            if ($userRole === 'agent') {
                $state = $this->updateStateFromAgentMessage($state, $agentMessage);

                // ✅ training progression / termination logic (only when agent speaks)
                $state = $this->applyTrainingProgression($state, $agentMessage);
            }

            $replyText = null;
            $source    = 'scenario_v1_logic_with_state';

            if (! empty($state['training']['ended'])) {
                $replyText = $state['training']['terminal_message'] ?? 'Session ended.';
                $source    = 'training_terminal';
            } else {
                // 3) Generate reply
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
                        $replyText = $this->generateAgentFallbackReply($scenario, $agentMessage);
                        $source    = 'agent_sim_fallback';
                    }
                }

                // 4) If system reply is the AGENT, update state from that reply
                if ($systemRole === 'agent') {
                    $state = $this->updateStateFromAgentMessage($state, $replyText);
                }
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
                    'stage'         => $state['training']['current_stage'] ?? null,
                    'training_mode' => $state['training']['mode'] ?? null,
                    'difficulty'    => $state['training']['difficulty'] ?? null,
                ],
            ]);

            // 6) Update session state
            $state['last_system_line'] = $replyText;

            $updates = ['state' => $state];

            if (! empty($state['training']['ended'])) {
                $updates['status']   = 'completed';
                $updates['ended_at'] = now();
            }

            $session->update($updates);

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
            is_array($config)
                ? ($config['ui_mode'] ?? ($session->mode ?? self::UI_MODE_PROSPECT_SIM))
                : ($session->mode ?? self::UI_MODE_PROSPECT_SIM)
        );
        $state = $session->state ?? [];
        $state = is_array($state) ? $state : [];

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

        // Coaching only makes sense when user was the agent
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

        // Quick diagnostic add-on
        $training = $state['training'] ?? null;
        $firstFailStage  = is_array($training) ? ($training['first_failed_stage'] ?? null) : null;
        $firstFailReason = is_array($training) ? ($training['first_failed_reason'] ?? null) : null;

        if ($firstFailStage && $firstFailReason) {
            $improvements .= " Earliest missed mini-close was at '{$firstFailStage}': {$firstFailReason}";
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
    |----------------------------------------------------------------------
    | INTERNAL: MODE / TRAINING / STATE / LOGIC
    |----------------------------------------------------------------------
    */

    protected function normalizeUiMode(?string $mode): string
    {
        $mode = (string) $mode;
        return ($mode === self::UI_MODE_AGENT_SIM) ? self::UI_MODE_AGENT_SIM : self::UI_MODE_PROSPECT_SIM;
    }

    protected function ensureTrainingState(GideonSparringSession $session, array $state): array
    {
        if (isset($state['training']) && is_array($state['training'])) {
            return $state;
        }

        $config = is_array($session->config) ? $session->config : [];

        $mode        = $this->normalizeTrainingMode($session->training_mode ?? ($config['training_mode'] ?? self::TRAINING_DISCOVERY_START));
        $difficulty  = $this->normalizeDifficulty($session->difficulty ?? ($config['difficulty'] ?? self::DIFF_NORMAL));
        $selStageRaw = $session->selected_stage ?? ($config['selected_stage'] ?? null);
        $selectedStage = $this->normalizeStage(is_string($selStageRaw) ? $selStageRaw : null);

        $startStage = match ($mode) {
            self::TRAINING_STAGES          => $selectedStage ?? self::STAGE_INTRO,
            self::TRAINING_DISCOVERY_START => self::STAGE_DISCOVERY,
            self::TRAINING_FULL            => self::STAGE_INTRO,
            default                        => self::STAGE_DISCOVERY,
        };

        $state['training'] = [
            'mode'               => $mode,
            'difficulty'         => $difficulty,
            'selected_stage'     => $selectedStage,
            'current_stage'      => $startStage,
            'turns_in_stage'     => 0,
            'ended'              => false,
            'terminal_reason'    => null,
            'terminal_message'   => null,
            'first_failed_stage' => null,
            'first_failed_reason'=> null,
        ];

        return $state;
    }

    protected function normalizeTrainingMode(?string $mode): string
    {
        $mode = (string) $mode;
        return in_array($mode, [self::TRAINING_STAGES, self::TRAINING_DISCOVERY_START, self::TRAINING_FULL], true)
            ? $mode
            : self::TRAINING_DISCOVERY_START;
    }

    protected function normalizeDifficulty(?string $difficulty): string
    {
        $difficulty = (string) $difficulty;
        return in_array($difficulty, [self::DIFF_EASY, self::DIFF_NORMAL, self::DIFF_HARD], true)
            ? $difficulty
            : self::DIFF_NORMAL;
    }

    protected function normalizeStage(?string $stage): ?string
    {
        if ($stage === null) return null;
        $stage = (string) $stage;

        return in_array($stage, [
            self::STAGE_INTRO,
            self::STAGE_DISCOVERY,
            self::STAGE_EDUCATION,
            self::STAGE_QUALIFY,
            self::STAGE_QUOTE,
            self::STAGE_CLOSE,
        ], true) ? $stage : null;
    }

    protected function stageOrder(): array
    {
        return [
            self::STAGE_INTRO,
            self::STAGE_DISCOVERY,
            self::STAGE_EDUCATION,
            self::STAGE_QUALIFY,
            self::STAGE_QUOTE,
            self::STAGE_CLOSE,
        ];
    }

    protected function nextStage(string $stage): ?string
    {
        $order = $this->stageOrder();
        $i = array_search($stage, $order, true);
        if ($i === false) return null;
        return $order[$i + 1] ?? null;
    }

    /**
     * Fast “mini-close” checks.
     */
    protected function miniClosePassed(string $stage, string $agentTextLower, string $difficulty): bool
    {
        $strict = ($difficulty === self::DIFF_HARD);

        return match ($stage) {
            self::STAGE_INTRO =>
                $strict
                    ? (str_contains($agentTextLower, 'mind if') || str_contains($agentTextLower, 'agenda'))
                    : (str_contains($agentTextLower, 'mind if') || str_contains($agentTextLower, 'quick') || str_contains($agentTextLower, 'few questions') || str_contains($agentTextLower, 'agenda')),

            self::STAGE_DISCOVERY =>
                $strict
                    ? (str_contains($agentTextLower, '?') && (str_contains($agentTextLower, 'tell me') || str_contains($agentTextLower, 'help me understand')))
                    : (str_contains($agentTextLower, '?') || str_contains($agentTextLower, 'tell me') || str_contains($agentTextLower, 'help me understand')),

            self::STAGE_EDUCATION =>
                $strict
                    ? (str_contains($agentTextLower, 'does that make sense') || str_contains($agentTextLower, 'fair'))
                    : (str_contains($agentTextLower, 'does that make sense') || str_contains($agentTextLower, 'fair') || str_contains($agentTextLower, 'the reason')),

            self::STAGE_QUALIFY =>
                (str_contains($agentTextLower, 'budget') || str_contains($agentTextLower, 'decision') || str_contains($agentTextLower, 'ready') || str_contains($agentTextLower, 'if we could')),

            self::STAGE_QUOTE =>
                (str_contains($agentTextLower, 'how does that sound') || str_contains($agentTextLower, 'compared') || str_contains($agentTextLower, 'what would stop')),

            self::STAGE_CLOSE =>
                (str_contains($agentTextLower, 'submit') || str_contains($agentTextLower, 'start') || str_contains($agentTextLower, 'move forward') || str_contains($agentTextLower, 'enroll')),

            default => false,
        };
    }

    protected function applyTrainingProgression(array $state, string $agentMessage): array
    {
        if (!isset($state['training']) || !is_array($state['training'])) return $state;
        if (!empty($state['training']['ended'])) return $state;

        $mode        = $this->normalizeTrainingMode($state['training']['mode'] ?? self::TRAINING_DISCOVERY_START);
        $difficulty  = $this->normalizeDifficulty($state['training']['difficulty'] ?? self::DIFF_NORMAL);
        $currentStage= $this->normalizeStage($state['training']['current_stage'] ?? self::STAGE_DISCOVERY) ?? self::STAGE_DISCOVERY;

        $text   = mb_strtolower($agentMessage);
        $passed = $this->miniClosePassed($currentStage, $text, $difficulty);

        $state['training']['turns_in_stage'] = (int)($state['training']['turns_in_stage'] ?? 0) + 1;

        if ($mode === self::TRAINING_FULL && $currentStage === self::STAGE_INTRO && !$passed) {
            $state = $this->setFirstFailureOnce($state, $currentStage, 'Intro mini-close not achieved (permission/agenda).');
            return $this->endTraining($state, "Failed at Intro (hard gate).");
        }

        if ($mode === self::TRAINING_STAGES) {
            if ($passed) {
                return $this->endTraining($state, "Stage '{$currentStage}' resolved. Great job.");
            }
            if (($state['training']['turns_in_stage'] ?? 0) >= 6) {
                $state = $this->setFirstFailureOnce($state, $currentStage, 'Stalled too long without resolving the mini-close.');
                return $this->endTraining($state, "Session ended: stalled too long at '{$currentStage}'.");
            }
            return $state;
        }

        if ($passed) {
            $next = $this->nextStage($currentStage);
            if ($next === null) {
                return $this->endTraining($state, "Cycle complete. Nice work.");
            }
            $state['training']['current_stage']  = $next;
            $state['training']['turns_in_stage'] = 0;
            return $state;
        }

        $state = $this->setFirstFailureOnce($state, $currentStage, 'Mini-close not achieved.');
        if (($state['training']['turns_in_stage'] ?? 0) >= 6) {
            return $this->endTraining($state, "Session ended: stalled too long at '{$currentStage}'.");
        }

        return $state;
    }

    protected function setFirstFailureOnce(array $state, string $stage, string $reason): array
    {
        if (!isset($state['training']) || !is_array($state['training'])) return $state;
        if (!empty($state['training']['first_failed_stage'])) return $state;

        $state['training']['first_failed_stage']  = $stage;
        $state['training']['first_failed_reason'] = $reason;
        return $state;
    }

    protected function endTraining(array $state, string $message): array
    {
        if (!isset($state['training']) || !is_array($state['training'])) return $state;

        $state['training']['ended']           = true;
        $state['training']['terminal_reason'] = $message;
        $state['training']['terminal_message']= $message;
        return $state;
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
            $state[$key] = $this->clamp((int)($state[$key] ?? 50), 0, 100);
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
            $state[$key] = $this->clamp((int)($state[$key] ?? 50), 0, 100);
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
        $config = is_array($session->config) ? $session->config : [];
        $uiMode = $this->normalizeUiMode($uiMode ?? ($config['ui_mode'] ?? ($session->mode ?? self::UI_MODE_PROSPECT_SIM)));

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
                'role'    => $m->role,
                'content' => $m->content,
            ];
        }

        return [
            'ui_mode' => $uiMode,

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
                'persona_key' => $session->persona_key ?? ($config['persona'] ?? null),
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

        return (int) max(1, min(5, (int)ceil(($value + 1) / 20)));
    }

    protected function scoreFromStateInverse(array $state, string $key): int
    {
        $value = (int) ($state[$key] ?? 50);
        $value = $this->clamp($value, 0, 100);
        $flipped = 100 - $value;

        return (int) max(1, min(5, (int)ceil(($flipped + 1) / 20)));
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
