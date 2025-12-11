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
     * Helper for the UI: list active scenarios with minimal fields.
     *
     * @return Collection<int, GideonScenario>
     */
    public function listScenariosForUi(): Collection
    {
        return GideonScenario::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'description', 'product_type', 'script_engine', 'prospect_profile']);
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

        return DB::transaction(function () use ($agencyId, $userId, $scenario, $mode, $personaKey) {
            $config = [
                'scenario_code' => $scenario->code,
                'mode'          => $mode,
            ];

            $prospectProfile = $scenario->prospect_profile ?? [];
            $defaultPersona  = is_array($prospectProfile)
                ? Arr::get($prospectProfile, 'persona')
                : null;

            // If UI sent an explicit persona, that wins. Otherwise fall back to scenario default.
            $personaKeyToUse = $personaKey ?: ($defaultPersona ?: 'adaptive');

            $session = GideonSparringSession::create([
                'agency_id'   => $agencyId,
                'user_id'     => $userId,
                'mode'        => $mode,
                'persona_key' => $personaKeyToUse,
                'config'      => $config,
                'status'      => 'active',
                'state'       => [
                    'trust'       => 35,
                    'urgency'     => 30,
                    'motivation'  => 40,
                    'resistance'  => 60,
                ],
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
            // How many messages existed BEFORE this new agent line?
            $messageCountBefore = GideonSparringMessage::where('session_id', $session->id)->count();

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

            // Update simple emotional "state" based on message (placeholder rules for now)
            $this->updateConversationState($session, $agentMessage);

            // Generate Gideon's reply using scenario templates, then fallback
            $replyText = $this->generateGideonReply(
                $session,
                $scenario,
                $agentMessage,
                $messageCountBefore
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

            // Refresh state on the session
            $session->refresh();

            return [
                'agent_message' => $agentMsg,
                'gideon_reply'  => $gideonMsg,
            ];
        });
    }

    /**
     * Crude state update: tweak trust / resistance based on presence of certain words.
     * This just gives us something to react to; later we can swap in ML/LLM logic.
     */
    protected function updateConversationState(GideonSparringSession $session, string $agentMessage): void
    {
        $state = $session->state ?? [
            'trust'       => 35,
            'urgency'     => 30,
            'motivation'  => 40,
            'resistance'  => 60,
        ];

        $lower = mb_strtolower($agentMessage);

        // Very rough heuristics
        if (str_contains($lower, '?')) {
            // Asking questions tends to build trust & discovery
            $state['trust']       = min(100, ($state['trust'] ?? 35) + 3);
            $state['motivation']  = min(100, ($state['motivation'] ?? 40) + 2);
            $state['resistance']  = max(0,   ($state['resistance'] ?? 60) - 2);
        }

        if (str_contains($lower, 'i hear') || str_contains($lower, 'i get that') || str_contains($lower, 'makes sense')) {
            // Validation language
            $state['trust']      = min(100, ($state['trust'] ?? 35) + 4);
            $state['resistance'] = max(0,   ($state['resistance'] ?? 60) - 3);
        }

        if (str_contains($lower, 'move forward') || str_contains($lower, 'next step')) {
            // Pushing a bit more
            $state['urgency']    = min(100, ($state['urgency'] ?? 30) + 3);
        }

        $session->state = $state;
        $session->save();
    }

    /**
     * Main reply generator.
     *
     * Uses per-scenario reply_templates if present; otherwise falls back
     * to a generic reply that references the scenario name.
     */
    protected function generateGideonReply(
        GideonSparringSession $session,
        ?GideonScenario $scenario,
        string $agentMessage,
        int $messageCountBefore,
    ): string {
        $scriptEngine = $scenario?->script_engine ?? null;
        $templates    = is_array($scriptEngine) ? Arr::get($scriptEngine, 'reply_templates') : null;

        // If no templates are defined for this scenario, use simple fallback.
        if (! is_array($templates) || empty($templates)) {
            return $this->generateFallbackReply($session, $scenario);
        }

        $bucket = $this->determineConversationBucket($messageCountBefore);
        $bucketTemplates = Arr::get($templates, $bucket);

        if (! is_array($bucketTemplates) || empty($bucketTemplates)) {
            // Try mid as a generic fallback, then fallback reply.
            $bucketTemplates = Arr::get($templates, 'mid', []);
            if (! is_array($bucketTemplates) || empty($bucketTemplates)) {
                return $this->generateFallbackReply($session, $scenario);
            }
        }

        $persona = $session->persona_key ?: 'adaptive';

        // Try persona-specific templates, then generic.
        $lines = Arr::get($bucketTemplates, $persona);
        if (! is_array($lines) || empty($lines)) {
            $lines = Arr::get($bucketTemplates, 'generic', []);
        }

        if (! is_array($lines) || empty($lines)) {
            // As a last resort, treat entire bucket as flat list.
            $flat = [];
            foreach ($bucketTemplates as $maybeList) {
                if (is_array($maybeList)) {
                    $flat = array_merge($flat, $maybeList);
                }
            }
            $lines = $flat;
        }

        if (empty($lines)) {
            return $this->generateFallbackReply($session, $scenario);
        }

        $chosen = $this->pickRandomLine($lines);

        // Very simple token replacement so your templates can reference context.
        $state          = $session->state ?? [];
        $scenarioName   = $scenario?->name ?? 'this situation';
        $resistance     = $state['resistance'] ?? null;

        $replacements = [
            '{{scenario_name}}' => $scenarioName,
            '{{agent_message}}' => $agentMessage,
            '{{resistance_level}}' => $resistance !== null ? (string)$resistance : '',
        ];

        return strtr($chosen, $replacements);
    }

    /**
     * Choose which bucket ("early", "mid", "late") we’re in based on how many
     * messages existed before this agent line.
     */
    protected function determineConversationBucket(int $messageCountBefore): string
    {
        // Count both Gideon + agent messages. Very rough cut:
        if ($messageCountBefore <= 3) {
            return 'early';
        }

        if ($messageCountBefore <= 8) {
            return 'mid';
        }

        return 'late';
    }

    protected function pickRandomLine(array $lines): string
    {
        $lines = array_values(array_filter($lines, fn ($l) => is_string($l) && trim($l) !== ''));
        if (empty($lines)) {
            return "Okay, I’m still trying to sort out where you are with this.";
        }

        return $lines[array_rand($lines)];
    }

    /**
     * Fallback reply if no templates exist for a scenario.
     */
    protected function generateFallbackReply(
        GideonSparringSession $session,
        ?GideonScenario $scenario,
    ): string {
        $scenarioLabel = $scenario?->name ?? 'this situation';
        $state         = $session->state ?? [];
        $resistance    = $state['resistance'] ?? 60;

        if ($resistance >= 65) {
            return "I hear what you’re saying, but from my side as the prospect in {$scenarioLabel}, I’m still not fully there yet. What would you ask me next to really understand what’s still holding me back?";
        }

        if ($resistance <= 40) {
            return "Honestly I’m warming up to this in {$scenarioLabel}. If you were going to help me feel totally confident about moving forward, what would you ask me now?";
        }

        return "Okay, I’m tracking with what you’re saying. In {$scenarioLabel}, what questions would you ask me next to better understand what’s still in the way?";
    }

    /**
     * End a session; create an assessment with simple coaching.
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

        // Basic analysis of conversation
        $analysis = $this->analyzeConversationForAssessment($session);

        $assessment = GideonSparringAssessment::create([
            'session_id'  => $session->id,
            'agency_id'   => $agencyId,
            'user_id'     => $userId,
            'scores'      => $analysis['scores'],
            'strengths'   => $analysis['strengths'],
            'improvements'=> $analysis['improvements'],
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

    /**
     * Very lightweight analysis to create a 1–5 score in four areas
     * plus a strengths / improvements summary.
     */
    protected function analyzeConversationForAssessment(GideonSparringSession $session): array
    {
        $messages = GideonSparringMessage::where('session_id', $session->id)
            ->orderBy('created_at')
            ->get();

        $agentLines = $messages->where('sender', 'agent')->pluck('content')->all();

        $totalAgentLines = max(1, count($agentLines));

        $questionCount = 0;
        $validationCount = 0;
        $rushedToSolutionCount = 0;

        foreach ($agentLines as $line) {
            $low = mb_strtolower($line);

            if (str_contains($low, '?')) {
                $questionCount++;
            }

            if (str_contains($low, 'i hear') ||
                str_contains($low, 'i get that') ||
                str_contains($low, 'makes sense') ||
                str_contains($low, 'i understand')) {
                $validationCount++;
            }

            if (str_contains($low, 'sign up') ||
                str_contains($low, 'move forward') ||
                str_contains($low, 'get you started') ||
                str_contains($low, 'application')) {
                $rushedToSolutionCount++;
            }
        }

        $questionRatio   = $questionCount / $totalAgentLines;
        $validationRatio = $validationCount / $totalAgentLines;

        // Very rough scoring from 1–5
        $rapport = 2;
        if ($validationRatio > 0.15) $rapport = 3;
        if ($validationRatio > 0.30) $rapport = 4;
        if ($validationRatio > 0.45) $rapport = 5;

        $discovery = 2;
        if ($questionRatio > 0.20) $discovery = 3;
        if ($questionRatio > 0.35) $discovery = 4;
        if ($questionRatio > 0.50) $discovery = 5;

        // Deal killers & closing clarity very simple for now
        $dealKillers = 3;
        $closingClarity = 3;

        $scores = [
            'rapport'        => $rapport,
            'discovery'      => $discovery,
            'deal_killers'   => $dealKillers,
            'closing_clarity'=> $closingClarity,
        ];

        // Strengths summary
        $strengths = 'Placeholder assessment. Automated coaching logic to be improved.';

        if ($rapport >= 3 && $discovery >= 3) {
            $strengths = 'There was some rapport, especially when you slowed down and validated what they were feeling. You also asked some helpful questions that nudged the conversation forward.';
        } elseif ($rapport >= 4) {
            $strengths = 'You did a nice job building rapport and making the prospect feel heard. Your tone and validation language were strong.';
        }

        // Improvements summary
        $improvements = 'Placeholder assessment. Automated coaching logic to be improved.';

        if ($questionRatio < 0.30) {
            $improvements = 'Spend more time asking open-ended questions before you pivot back to the solution. Slow down and pull out what would have to be true for them to feel good about moving forward.';
        } elseif ($rushedToSolutionCount > 0) {
            $improvements = 'At times you jumped to the solution quickly. Try reflecting back what the prospect is feeling, then explore the size of the problem before you talk about next steps.';
        } elseif ($validationRatio < 0.20) {
            $improvements = 'Look for chances to reflect their words back to them. Simple phrases like “I get that” and “that makes sense” can help them feel understood and safe to go deeper.';
        }

        return [
            'scores'       => $scores,
            'strengths'    => $strengths,
            'improvements' => $improvements,
        ];
    }
}
