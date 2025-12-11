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
                'mode' => $mode,
            ];

            $prospectProfile = $scenario->prospect_profile ?? [];
            $personaKey = is_array($prospectProfile)
                ? Arr::get($prospectProfile, 'persona')
                : null;

            $session = GideonSparringSession::create([
                'agency_id' => $agencyId,
                'user_id' => $userId,
                'mode' => $mode,
                'persona_key' => $personaKey,
                'config' => $config,
                'status' => 'active',
                'started_at' => now(),
            ]);

            $scriptEngine = $scenario->script_engine ?? [];
            $openingLine = is_array($scriptEngine)
                ? Arr::get($scriptEngine, 'opening_line')
                : null;

            $firstMessage = null;

            if ($openingLine) {
                $firstMessage = GideonSparringMessage::create([
                    'session_id' => $session->id,
                    'agency_id' => $agencyId,
                    'user_id' => $userId,
                    'sender' => 'gideon',
                    'content' => $openingLine,
                    'meta' => [
                        'source' => 'scenario_opening_line',
                        'scenario_code' => $scenario->code,
                    ],
                ]);
            }

            return [
                'session' => $session->fresh(),
                'first_message' => $firstMessage,
            ];
        });
    }

    /**
     * Handle an agent message + generate Gideon's reply (stub logic v1).
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
            // Store agent message
            $agentMsg = GideonSparringMessage::create([
                'session_id' => $session->id,
                'agency_id' => $agencyId,
                'user_id' => $userId,
                'sender' => 'agent',
                'content' => $agentMessage,
                'meta' => [
                    'analysis' => null, // later: pattern analysis goes here
                ],
            ]);

            // Stub reply logic (later we replace with real rules/tactics)
            $replyText = $this->generateGideonReplyStub(
                $session,
                $scenario,
                $agentMessage
            );

            $gideonMsg = GideonSparringMessage::create([
                'session_id' => $session->id,
                'agency_id' => $agencyId,
                'user_id' => $userId,
                'sender' => 'gideon',
                'content' => $replyText,
                'meta' => [
                    'source' => 'stub_logic_v1',
                    'scenario_code' => $scenario?->code,
                ],
            ]);

            return [
                'agent_message' => $agentMsg,
                'gideon_reply' => $gideonMsg,
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
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        $assessment = GideonSparringAssessment::create([
            'session_id' => $session->id,
            'agency_id' => $agencyId,
            'user_id' => $userId,
            'scores' => [
                'rapport' => 5,
                'discovery' => 5,
                'deal_killers' => 5,
                'closing_clarity' => 5,
            ],
            'strengths' => 'Placeholder assessment. Automated coaching logic to be implemented.',
            'improvements' => 'Placeholder assessment. Automated coaching logic to be implemented.',
            'meta' => [],
        ]);

        return [
            'session' => $session->fresh(),
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
            'session' => $session,
            'messages' => $messages,
        ];
    }

    /**
     * Very simple reply for now. Later this will use:
     * - tactics
     * - objection types
     * - stages
     * - if/then rules
     */
    protected function generateGideonReplyStub(
        GideonSparringSession $session,
        ?GideonScenario $scenario,
        string $agentMessage,
    ): string {
        $scenarioLabel = $scenario?->name ?? 'this situation';

        return "Okay, I hear you. But from my side as the prospect in {$scenarioLabel}, I’m still not fully convinced. "
            . "What else would you say to help me feel confident about this?";
    }
}
