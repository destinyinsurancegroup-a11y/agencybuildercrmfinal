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
        ?string $requestedPersona = null,
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
            $requestedPersona
        ) {
            $config = [
                'scenario_code' => $scenario->code,
                'mode'          => $mode,
            ];

            $prospectProfile = $scenario->prospect_profile ?? [];
            $defaultPersona  = is_array($prospectProfile)
                ? Arr::get($prospectProfile, 'persona')
                : null;

            // Normalize persona based on request + scenario default
            $personaKey = $this->normalizePersona(
                $requestedPersona,
                is_array($prospectProfile) ? $prospectProfile : null,
            );

            if (! $personaKey && $defaultPersona) {
                $personaKey = (string) $defaultPersona;
            }

            $initialState = $this->initialStateForPersona($personaKey);

            $session = GideonSparringSession::create([
                'agency_id'   => $agencyId,
                'user_id'     => $userId,
                'mode'        => $mode,
                'persona_key' => $personaKey,
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
     * Handle an agent message + generate Gideon's reply (rules + state aware).
     *
     * @return array{agent_message: GideonSparringMessage, gideon_reply: GideonSparringMessage}
     */
    public function handleAgentMessage(
        int $sessionId,
        ?int $agencyId,
        int $userId,
        string $agentMessage,
    ): array {
        /** @var GideonSparringSession $session */
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
            // Analyze the agent's message a bit so we can adjust state
            $analysis = $this->analyzeAgentMessage($agentMessage);

            // Store agent message
            $agentMsg = GideonSparringMessage::create([
                'session_id' => $session->id,
                'agency_id'  => $agencyId,
                'user_id'    => $userId,
                'sender'     => 'agent',
                'content'    => $agentMessage,
                'meta'       => [
                    'analysis' => $analysis,
                ],
            ]);

            // Update internal state & craft a persona-aware reply
            $replyText = $this->generateGideonReplyWithState(
                $session,
                $scenario,
                $agentMessage,
                $analysis
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
                    'state'         => $session->state,
                    'persona_key'   => $session->persona_key,
                ],
            ]);

            // Persist updated state on session
            $session->save();

            return [
                'agent_message' => $agentMsg,
                'gideon_reply'  => $gideonMsg,
            ];
        });
    }

    /**
     * End a session; build assessment based on emotional state.
     *
     * @return array{session: GideonSparringSession, assessment: GideonSparringAssessment}
     */
    public function endSession(
        int $sessionId,
        ?int $agencyId,
        int $userId,
    ): array {
        /** @var GideonSparringSession $session */
        $session = GideonSparringSession::where('id', $sessionId)
            ->where('agency_id', $agencyId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $session->update([
            'status'   => 'completed',
            'ended_at' => now(),
        ]);

        $state = $session->state ?? [
            'trust'       => 40,
            'urgency'     => 40,
            'motivation'  => 40,
            'resistance'  => 60,
        ];

        // Convert 0-100 scale into 1-5 score
        $score = function (int $value): int {
            return match (true) {
                $value >= 80 => 5,
                $value >= 60 => 4,
                $value >= 40 => 3,
                $value >= 20 => 2,
                default      => 1,
            };
        };

        $rapport        = $score((int) ($state['trust'] ?? 40));
        $discovery      = $score((int) ($state['motivation'] ?? 40));
        // low resistance is good, so invert
        $dealKillersRaw = 100 - (int) ($state['resistance'] ?? 60);
        $dealKillers    = $score($dealKillersRaw);
        $closingRaw     = (int) round(((int) ($state['trust'] ?? 40)
            + (int) ($state['urgency'] ?? 40)
            + (int) ($state['motivation'] ?? 40)) / 3);
        $closingClarity = $score($closingRaw);

        // Simple coaching text based on highs/lows
        $strengths    = [];
        $improvements = [];

        if ($rapport >= 4) {
            $strengths[] = 'You built decent rapport – you acknowledged their situation and stayed conversational.';
        } else {
            $improvements[] = 'Spend more time validating what the prospect is feeling before you pivot back to the solution.';
        }

        if ($discovery >= 4) {
            $strengths[] = 'You asked questions that helped the prospect think through their situation and what they actually want.';
        } else {
            $improvements[] = 'Ask more open-ended questions about what they value, what they are worried about, and what would have to be true for them to move forward.';
        }

        if ($dealKillers >= 4) {
            $strengths[] = 'You did an okay job addressing the real deal-killers instead of dancing around them.';
        } else {
            $improvements[] = 'Slow down and name the real deal-killer out loud – then ask how big of a barrier it really is on a 1–10 scale.';
        }

        if ($closingClarity >= 4) {
            $strengths[] = 'Your direction toward a decision was fairly clear.';
        } else {
            $improvements[] = 'End with a simple, clear next step so the prospect knows exactly what moving forward looks like.';
        }

        if (! $strengths) {
            $strengths[] = 'Placeholder assessment. Automated coaching logic to be improved.';
        }
        if (! $improvements) {
            $improvements[] = 'Placeholder assessment. Automated coaching logic to be improved.';
        }

        $assessment = GideonSparringAssessment::create([
            'session_id'   => $session->id,
            'agency_id'    => $agencyId,
            'user_id'      => $userId,
            'scores'       => [
                'rapport'         => $rapport,
                'discovery'       => $discovery,
                'deal_killers'    => $dealKillers,
                'closing_clarity' => $closingClarity,
            ],
            'strengths'    => implode(' ', $strengths),
            'improvements' => implode(' ', $improvements),
            'meta'         => [
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

    /*
    |--------------------------------------------------------------------------
    | Persona / State helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Normalize persona slugs coming from the UI into internal persona keys.
     *
     * UI currently sends:
     *  - soft_conflict_avoidant
     *  - neutral_balanced
     *  - direct_analytical
     */
    protected function normalizePersona(?string $requested, ?array $prospectProfile): string
    {
        if (! $requested) {
            // fall back to prospect profile if available; else default soft
            return (string) ($prospectProfile['persona'] ?? 'soft_conflict_avoidant');
        }

        $slug = strtolower(trim($requested));

        return match ($slug) {
            'soft_conflict_avoidant' => 'soft_conflict_avoidant',

            'neutral_balanced',
            'neutral_realistic',
            'balanced_realist'       => 'neutral_balanced',

            'direct_analytical'      => 'direct_analytical',

            'skeptical_guarded'      => 'skeptical_guarded',

            'adaptive'               => (string) ($prospectProfile['persona'] ?? 'neutral_balanced'),

            default                  => $slug, // allow direct keys too
        };
    }

    /**
     * Initial emotional state based on persona.
     */
    protected function initialStateForPersona(?string $personaKey): array
    {
        // Defaults
        $base = [
            'trust'       => 35,
            'urgency'     => 30,
            'motivation'  => 40,
            'resistance'  => 60,
        ];

        return match ($personaKey) {
            'soft_conflict_avoidant' => [
                'trust'       => 40,
                'urgency'     => 20,
                'motivation'  => 35,
                'resistance'  => 65,
            ],
            'neutral_balanced' => [
                'trust'       => 40,
                'urgency'     => 35,
                'motivation'  => 45,
                'resistance'  => 55,
            ],
            'direct_analytical' => [
                'trust'       => 35,
                'urgency'     => 45,
                'motivation'  => 45,
                'resistance'  => 55,
            ],
            'skeptical_guarded' => [
                'trust'       => 25,
                'urgency'     => 25,
                'motivation'  => 35,
                'resistance'  => 70,
            ],
            default => $base,
        };
    }

    /**
     * Very rough tagging so we can tweak state using the agent's language.
     */
    protected function analyzeAgentMessage(string $message): array
    {
        $lower = mb_strtolower($message);

        $isQuestion      = str_contains($lower, '?');
        $mentionsSpouse  = str_contains($lower, 'spouse')
            || str_contains($lower, 'wife')
            || str_contains($lower, 'husband')
            || str_contains($lower, 'partner');
        $reassurance     = str_contains($lower, 'i understand')
            || str_contains($lower, 'i get that')
            || str_contains($lower, 'i hear you');
        $futureFocus     = str_contains($lower, 'future')
            || str_contains($lower, 'down the road')
            || str_contains($lower, 'long term');
        $urgencyWords    = str_contains($lower, 'today')
            || str_contains($lower, 'right now')
            || str_contains($lower, 'sooner')
            || str_contains($lower, 'before something happens');

        return [
            'is_question'     => $isQuestion,
            'mentions_spouse' => $mentionsSpouse,
            'reassurance'     => $reassurance,
            'future_focus'    => $futureFocus,
            'urgency_words'   => $urgencyWords,
        ];
    }

    /**
     * Main logic to adjust emotional state + craft a persona-aware reply.
     */
    protected function generateGideonReplyWithState(
        GideonSparringSession $session,
        ?GideonScenario $scenario,
        string $agentMessage,
        array $analysis,
    ): string {
        $scenarioLabel = $scenario?->name ?? 'this situation';
        $persona       = $session->persona_key ?: 'soft_conflict_avoidant';

        $state = $session->state ?? $this->initialStateForPersona($persona);

        // --- 1) Update emotional state --------------------------------------
        // Baseline deltas
        if ($analysis['is_question']) {
            // Asking questions generally helps motivation / discovery
            $state['motivation'] = ($state['motivation'] ?? 40) + 4;
            $state['trust']      = ($state['trust'] ?? 35) + 2;
        }

        if ($analysis['mentions_spouse']) {
            // You’re acknowledging their real decision-making process
            $state['trust']      = ($state['trust'] ?? 35) + 5;
            $state['resistance'] = ($state['resistance'] ?? 60) - 4;
        }

        if ($analysis['reassurance']) {
            $state['trust']      = ($state['trust'] ?? 35) + 6;
            $state['resistance'] = ($state['resistance'] ?? 60) - 5;
        }

        if ($analysis['urgency_words']) {
            $state['urgency']    = ($state['urgency'] ?? 30) + 6;
        }

        if ($analysis['future_focus']) {
            $state['motivation'] = ($state['motivation'] ?? 40) + 3;
        }

        // Persona-flavored tweaks
        switch ($persona) {
            case 'soft_conflict_avoidant':
                // They react strongly to pressure; reward validation, penalize push
                if ($analysis['is_question']) {
                    $state['resistance'] = ($state['resistance'] ?? 60) - 2;
                }
                if ($analysis['urgency_words']) {
                    $state['resistance'] = ($state['resistance'] ?? 60) + 4; // feels pushy
                }
                break;

            case 'direct_analytical':
                // Reward logic / future / clarity more than pure comfort language
                if ($analysis['future_focus']) {
                    $state['trust']      = ($state['trust'] ?? 35) + 3;
                    $state['motivation'] = ($state['motivation'] ?? 40) + 3;
                }
                break;

            case 'neutral_balanced':
                // Slightly more forgiving all around
                $state['trust'] = ($state['trust'] ?? 35) + 1;
                break;

            case 'skeptical_guarded':
                // Change more slowly overall
                $state['trust']      = ($state['trust'] ?? 25) + 1;
                $state['resistance'] = ($state['resistance'] ?? 70) - 1;
                break;
        }

        // Clamp 0–100
        foreach ($state as $key => $value) {
            $state[$key] = max(0, min(100, (int) round($value)));
        }
        $session->state = $state;

        // --- 2) Craft reply text --------------------------------------------
        $trust       = $state['trust'];
        $urgency     = $state['urgency'];
        $motivation  = $state['motivation'];
        $resistance  = $state['resistance'];

        // Base feeling
        if ($resistance > 70) {
            $baseFeeling = "Honestly, I’m still pretty hesitant.";
        } elseif ($trust > 60 && $resistance < 40) {
            $baseFeeling = "What you’re saying is starting to really make sense.";
        } else {
            $baseFeeling = "I’m tracking with a lot of what you’re saying, but I’m not fully there yet.";
        }

        // Persona-specific flavor
        $personaLine = match ($persona) {
            'soft_conflict_avoidant' => " I don’t want to feel rushed or like I’m being pushed into something I might regret.",
            'direct_analytical'      => " I just need to feel like the numbers and logic really line up before I move forward.",
            'neutral_balanced'       => " I’m trying to be sensible here and not overreact in either direction.",
            'skeptical_guarded'      => " I’ve seen things go wrong before, so I’m naturally cautious with decisions like this.",
            default                  => " I just want to feel like this is a well-thought-out, responsible decision.",
        };

        // Prompt to coach the agent’s next move
        if ($resistance > 70) {
            $prompt = " If you were in my shoes, what would you ask or say next to help me feel truly safe moving ahead?";
        } elseif ($trust > 60 && $motivation > 55) {
            $prompt = " From here, what would you walk me through so I feel clear about taking the next step instead of drifting back into doing nothing?";
        } else {
            $prompt = " What questions would you ask me now to understand what’s still holding me back?";
        }

        return $baseFeeling
            . $personaLine
            . " From my side in {$scenarioLabel}, I’m still weighing this out."
            . $prompt;
    }
}
