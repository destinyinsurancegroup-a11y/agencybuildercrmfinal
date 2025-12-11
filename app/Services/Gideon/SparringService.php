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
        int  $userId,
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

            // Simple default "emotional state"
            $defaultState = [
                'trust'       => 35,
                'urgency'     => 30,
                'motivation'  => 40,
                'resistance'  => 60,
                'turn'        => 0,  // how many agent turns we’ve had
            ];

            $session = GideonSparringSession::create([
                'agency_id'   => $agencyId,
                'user_id'     => $userId,
                'mode'        => $mode,
                'persona_key' => $personaKey,
                'config'      => $config,
                'status'      => 'active',
                'started_at'  => now(),
                'state'       => $defaultState,
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
     * Handle an agent message + generate Gideon's reply (scenario-aware v1).
     *
     * @return array{agent_message: GideonSparringMessage, gideon_reply: GideonSparringMessage}
     */
    public function handleAgentMessage(
        int   $sessionId,
        ?int  $agencyId,
        int   $userId,
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

            // 2) Update state (very simple heuristics)
            $state = $session->state ?? [];
            $state = $this->updateStateFromAgentMessage($state, $agentMessage);
            $session->state = $state;
            $session->save();

            // 3) Scenario-aware reply
            $replyText = $this->generateGideonReplyScenarioV1(
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
                    'state'         => $session->state,
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
        int   $sessionId,
        ?int  $agencyId,
        int   $userId,
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
            'strengths'   => 'Placeholder assessment. Automated coaching logic to be implemented.',
            'improvements' => 'Placeholder assessment. Automated coaching logic to be implemented.',
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
        int   $sessionId,
        ?int  $agencyId,
        int   $userId,
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
     * Very simple "emotion" adjustment + turn counter.
     * This will get replaced by smarter coaching later.
     */
    protected function updateStateFromAgentMessage(array $state, string $agentMessage): array
    {
        $state['trust']      = $state['trust']      ?? 35;
        $state['urgency']    = $state['urgency']    ?? 30;
        $state['motivation'] = $state['motivation'] ?? 40;
        $state['resistance'] = $state['resistance'] ?? 60;
        $state['turn']       = ($state['turn']      ?? 0) + 1;

        $text = mb_strtolower($agentMessage);

        $showsEmpathy = str_contains($text, 'i get') ||
                        str_contains($text, 'i understand') ||
                        str_contains($text, 'makes sense') ||
                        str_contains($text, 'totally hear');

        $asksClarifying = str_contains($text, 'help me understand') ||
                          str_contains($text, 'walk me through') ||
                          str_contains($text, 'tell me more') ||
                          str_contains($text, 'what makes you say');

        $pushesHard = str_contains($text, 'let’s just') ||
                      str_contains($text, 'let us just') ||
                      str_contains($text, 'we can get this done') ||
                      str_contains($text, 'go ahead and get this started');

        if ($showsEmpathy) {
            $state['trust']      = min(100, $state['trust'] + 8);
            $state['resistance'] = max(0,   $state['resistance'] - 5);
        }

        if ($asksClarifying) {
            $state['motivation'] = min(100, $state['motivation'] + 5);
            $state['resistance'] = max(0,   $state['resistance'] - 3);
        }

        if ($pushesHard) {
            $state['trust']      = max(0,   $state['trust'] - 5);
            $state['resistance'] = min(100, $state['resistance'] + 7);
        }

        return $state;
    }

    /**
     * Scenario-aware reply logic v1.
     * Uses:
     *  - scenario code
     *  - session.state.turn to know where we are in the dance
     */
    protected function generateGideonReplyScenarioV1(
        GideonSparringSession $session,
        ?GideonScenario        $scenario,
        string                 $agentMessage,
    ): string {
        $scenarioCode = $scenario?->code ?? null;
        $state        = $session->state ?? [];
        $turn         = (int) ($state['turn'] ?? 1);

        // Safety: keep turn in a reasonable range
        if ($turn < 1) {
            $turn = 1;
        }
        if ($turn > 6) {
            $turn = 6;
        }

        $baseLines = [];

        switch ($scenarioCode) {
            case 'scenario_competition_already_have_someone':
                $baseLines = [
                    1 => "We actually already have someone we work with for this.",
                    2 => "I’m somewhere in the middle. I’m not a hard no, but I’m not a yes either. Help me understand what you’d focus on if you were in my position, weighing this out.",
                    3 => "If I’m being honest, the main reason I’d even consider changing is if I felt like I was missing something important right now.",
                    4 => "My biggest worry is switching and then finding out later that it wasn’t really worth the hassle. That’s happened to people I know.",
                    5 => "If you could give me one or two very clear reasons why people like me switch and are glad they did, what would those be?",
                    6 => "At the end of the day, I’d only move if it’s obvious the relationship and the coverage are clearly better than what I have now.",
                ];
                break;

            case 'scenario_no_urgency_maybe_later':
                $baseLines = [
                    1 => "Yeah, it sounds important, but it just doesn’t feel urgent right now. We’ve got a lot going on.",
                    2 => "It’s more of a \"someday\" thing in my head. I know we should handle it, I just keep pushing it back.",
                    3 => "What usually makes your clients decide that now is the right time instead of waiting like I’ve been doing?",
                    4 => "I’d need to feel like there’s a real cost to waiting, not just a sales pitch. Otherwise I’ll keep kicking the can down the road.",
                    5 => "If we pushed this off another 6–12 months, what do you see as the most realistic downside for someone in my situation?",
                    6 => "If you can help me see a clear, logical reason why now actually protects us from something real, I’d be much more open.",
                ];
                break;

            case 'scenario_price_too_expensive':
                $baseLines = [
                    1 => "Honestly, my first reaction is that it just feels a bit expensive compared to what I expected.",
                    2 => "It’s not that I don’t see value, it’s that I’m trying to make the math work with everything else we’ve got going on.",
                    3 => "If you were in my shoes, where would you look to see if this is genuinely worth the extra money?",
                    4 => "What I’m afraid of is committing to a higher payment and then regretting it if things get tight down the road.",
                    5 => "Help me understand what people usually cut or adjust so this fits without feeling like a stressful bill every month.",
                    6 => "If you can show me that what I’m paying for actually protects something I truly care about, the price would feel a lot more reasonable.",
                ];
                break;

            case 'scenario_spouse_objection':
                $baseLines = [
                    1 => "I like this, but I really need to talk it through with my spouse before we do anything.",
                    2 => "We try not to make bigger decisions without both of us being on the same page. It just avoids arguments later.",
                    3 => "If you were talking to my spouse instead of me, what would you make sure they understood before saying yes or no?",
                    4 => "I don’t want to drag them into a long sales call, but I also don’t want to decide for both of us without their input.",
                    5 => "What would a simple, respectful next step look like that includes my spouse without either of us feeling pressured?",
                    6 => "If you can make it easy for both of us to hear the facts and ask a couple of questions, I’d feel much better moving forward.",
                ];
                break;

            case 'scenario_think_it_over':
                $baseLines = [
                    1 => "Yeah, this all sounds pretty good. I just want to think about it for a bit.",
                    2 => "It’s a big decision and I don’t like rushing into things like this. I want to feel settled about it.",
                    3 => "What usually happens when people say they want to think it over? Do they actually come back to you or do they just drift off?",
                    4 => "Part of me worries that if I walk away to think, life will get busy and I’ll never actually sit down to make a decision.",
                    5 => "If we were to decide today, what are the one or two key things you’d want me to be absolutely clear on?",
                    6 => "If you can help me get clear on the real risk of waiting versus the upside of acting, it would make this a lot easier to decide.",
                ];
                break;

            default:
                // fallback scenario if something is mis-wired
                return "Okay, I hear what you’re saying. From my side as the prospect, I’m still on the fence. "
                    . "Help me understand your thinking a bit more so I can feel confident about the direction you’re recommending.";
        }

        // Pick the line for the current turn (or last one if we’re past the script)
        $line = $baseLines[$turn] ?? end($baseLines);

        return $line;
    }
}
