<?php

namespace App\Services\Gideon;

use App\Models\GideonSparringSession;
use App\Models\GideonSparringMessage;
use App\Models\GideonSparringAssessment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GideonSparringPartner
{
    /**
     * Start a new sparring session for this user.
     */
    public function startSession(
        User $user,
        string $mode = 'standard',
        ?string $personaKey = null,
        array $config = []
    ): GideonSparringSession {
        return GideonSparringSession::create([
            'agency_id'   => $user->agency_id,
            'user_id'     => $user->id,
            'mode'        => $mode,
            'persona_key' => $personaKey,
            'config'      => $config,
            'status'      => 'active',
            'started_at'  => now(),
        ]);
    }

    /**
     * Record an agent message and return Gideon's reply (fake for now).
     */
    public function handleAgentMessage(GideonSparringSession $session, string $message): string
    {
        // Safety: only active sessions can be used
        if ($session->status !== 'active') {
            return "This sparring session has already ended. Start a new session to keep practicing.";
        }

        // Save the agent's message
        GideonSparringMessage::create([
            'session_id' => $session->id,
            'agency_id'  => $session->agency_id,
            'user_id'    => $session->user_id,
            'sender'     => 'agent',
            'content'    => $message,
            'meta'       => null,
        ]);

        // Tier 1 = fake reply for now (no real LLM call yet)
        $reply = $this->generateFakeReply($session, $message);

        // Save Gideon's reply
        GideonSparringMessage::create([
            'session_id' => $session->id,
            'agency_id'  => $session->agency_id,
            'user_id'    => $session->user_id,
            'sender'     => 'gideon',
            'content'    => $reply,
            'meta'       => null,
        ]);

        return $reply;
    }

    /**
     * End the session + create a basic assessment stub.
     */
    public function endSessionAndAssess(GideonSparringSession $session): GideonSparringAssessment
    {
        return DB::transaction(function () use ($session) {
            $session->update([
                'status'   => 'completed',
                'ended_at' => now(),
            ]);

            // Simple placeholder scoring for v1 (non-AI).
            $scores = [
                'rapport'            => 6,
                'discovery'          => 5,
                'objection_handling' => 7,
                'close'              => 5,
                'compliance_flags'   => [],
            ];

            return GideonSparringAssessment::create([
                'session_id'   => $session->id,
                'agency_id'    => $session->agency_id,
                'user_id'      => $session->user_id,
                'scores'       => $scores,
                'strengths'    => 'You stayed engaged in the conversation and kept the dialogue going.',
                'improvements' => 'Ask more discovery questions before presenting, and be more direct when asking for the sale.',
                'meta'         => null,
            ]);
        });
    }

    /**
     * Fake “brain” for Tier 1. Later this will call GideonLlmClient.
     */
    protected function generateFakeReply(GideonSparringSession $session, string $agentMessage): string
    {
        $mode = $session->mode ?? 'standard';

        if ($mode === 'objection_gauntlet') {
            return "Okay, as your practice client, here’s an objection: “I’m not sure I can afford that right now.” "
                . "Respond as the agent and show me how you’d handle that.";
        }

        return "Got it. As your Sparring Partner, I’ll play the client. "
            . "Go ahead and give me your next line as if this were a real call. "
            . "You said: “{$agentMessage}”. Continue from there.";
    }
}
