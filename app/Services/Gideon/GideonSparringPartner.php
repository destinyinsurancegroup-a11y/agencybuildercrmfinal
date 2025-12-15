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
        if ($session->status !== 'active') {
            return "This sparring session has already ended. Start a new session to keep practicing.";
        }

        return DB::transaction(function () use ($session, $message) {

            // ✅ Save the agent's message (role, not sender)
            GideonSparringMessage::create([
                'session_id' => $session->id,
                'agency_id'  => $session->agency_id,
                'user_id'    => $session->user_id,
                'role'       => 'agent',
                'content'    => $message,
                'meta'       => [
                    'source' => 'gideon_sparring_partner_fake_brain',
                ],
            ]);

            $reply = $this->generateFakeReply($session, $message);

            // ✅ Save the prospect/system reply (role, not sender)
            GideonSparringMessage::create([
                'session_id' => $session->id,
                'agency_id'  => $session->agency_id,
                'user_id'    => $session->user_id,
                'role'       => 'prospect',
                'content'    => $reply,
                'meta'       => [
                    'source' => 'gideon_sparring_partner_fake_brain',
                ],
            ]);

            return $reply;
        });
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
                'meta'         => [
                    'source' => 'gideon_sparring_partner_fake_brain',
                ],
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
