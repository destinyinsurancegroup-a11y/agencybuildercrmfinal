<?php

namespace App\Services\Gideon;

use App\Models\GideonScenario;
use App\Models\GideonSparringMessage;
use App\Models\GideonSparringSession;
use Illuminate\Support\Arr;

class CoachingService
{
    public function __construct(
        protected GideonLlmClient $llmClient
    ) {
    }

    /**
     * Returns [strengths, improvements] using LLM, or null on failure.
     */
    public function generateAssessmentNarrative(
        GideonSparringSession $session,
        ?GideonScenario $scenario,
        array $scores
    ): ?array {
        $enabled = (bool) config('gideon.enabled', false)
            && (bool) config('gideon.coaching.enabled', false)
            && (bool) config('gideon.coaching.assessment_llm_enabled', false);

        if (! $enabled) {
            return null;
        }

        $historyLimit = (int) config('gideon.coaching.assessment_history_limit', 18);

        $messages = GideonSparringMessage::query()
            ->where('session_id', $session->id)
            ->orderBy('id', 'desc')
            ->take($historyLimit)
            ->get()
            ->sortBy('id')
            ->values();

        $transcript = $messages->map(function ($m) {
            $who = $m->sender === 'agent' ? 'Agent' : 'Prospect';
            return "{$who}: {$m->content}";
        })->implode("\n");

        $scenarioName = $scenario?->name ?? 'Unknown scenario';
        $persona      = $session->persona_key ?? Arr::get($session->config ?? [], 'persona', 'unknown');

        $system = <<<SYS
You are Gideon Coach, a sales coaching assistant for insurance agents.
You are NOT the prospect. You write coaching feedback.
Be specific, kind, and practical. No fluff. No lecturing.
Output must be exactly two sections: "Strengths:" and "Improvements:".
SYS;

        $user = <<<USR
Scenario: {$scenarioName}
Persona: {$persona}

Scores (1-5):
- Rapport: {$scores['rapport']}
- Discovery: {$scores['discovery']}
- Deal killers: {$scores['deal_killers']}
- Closing clarity: {$scores['closing_clarity']}

Transcript (most recent first-to-last):
{$transcript}

Write coaching feedback. Keep it concise (6-10 sentences total).
USR;

        $messagesForLlm = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ];

        $temperature = (float) config('gideon.coaching.temperature', 0.4);
        $maxTokens   = (int) config('gideon.coaching.max_tokens', 420);

        $text = $this->llmClient->chat($messagesForLlm, [
            'temperature' => $temperature,
            'max_tokens'  => $maxTokens,
        ]);

        $text = trim((string) $text);
        if ($text === '') {
            return null;
        }

        // Very simple parse: split on "Improvements:"
        $strengths = null;
        $improvements = null;

        $parts = preg_split('/\bImprovements:\b/i', $text);
        if ($parts && count($parts) >= 2) {
            $strengthsPart = preg_replace('/\bStrengths:\b/i', '', $parts[0]);
            $strengths = trim((string) $strengthsPart);
            $improvements = trim((string) $parts[1]);
        }

        if (! $strengths || ! $improvements) {
            return null;
        }

        return [$strengths, $improvements];
    }
}
