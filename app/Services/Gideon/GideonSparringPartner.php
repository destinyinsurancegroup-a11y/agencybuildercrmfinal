<?php

namespace App\Services\Gideon;

/**
 * GideonSparringPartner
 *
 * This service handles all "conversation" logic for Gideon:
 * scenarios, personas, and building prompts for the LLM client.
 *
 * For now:
 * - It does NOT store chat history in the database.
 * - It just takes input, builds a prompt, calls GideonLlmClient, and returns a reply.
 * - GideonLlmClient may be in FAKE MODE, which is fine for wiring & testing.
 */
class GideonSparringPartner
{
    protected GideonLlmClient $llm;

    public function __construct(GideonLlmClient $llm)
    {
        $this->llm = $llm;
    }

    /**
     * Main entry point for sparring.
     *
     * @param  array  $input  [
     *   'prompt'        => string (required),
     *   'scenario_type' => string|null (single_prospect, couple_presentation, referral_partner, generic_coaching),
     *   'persona'       => string|null,
     *   'entity_type'   => string|null,
     *   'entity_id'     => int|null,
     * ]
     *
     * @return array [
     *   'reply'         => string,
     *   'scenario_type' => string|null,
     *   'persona'       => string|null,
     *   'speaker'       => string,     // e.g. client, partner_a, partner_b, coach
     *   'emotion'       => string,     // e.g. neutral, skeptical, supportive
     * ]
     */
    public function ask(array $input): array
    {
        $prompt       = $input['prompt']        ?? '';
        $scenarioType = $input['scenario_type'] ?? 'generic_coaching';
        $persona      = $input['persona']       ?? null;

        if (trim($prompt) === '') {
            return [
                'reply'         => 'Please type a question or scenario for Gideon to help with.',
                'scenario_type' => $scenarioType,
                'persona'       => $persona,
                'speaker'       => 'coach',
                'emotion'       => 'neutral',
            ];
        }

        // Build a system message based on scenario + persona
        $systemMessage = $this->buildSystemMessage($scenarioType, $persona);

        $messages = [
            [
                'role'    => 'system',
                'content' => $systemMessage,
            ],
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ];

        $replyText = $this->llm->chat($messages);

        // For now, we keep it simple:
        // - Speaker is always "client" for scenario roleplay,
        //   or "coach" for generic coaching.
        // - Emotion defaults to "neutral" (Avatar UI can improve this later).
        $speaker = $scenarioType === 'generic_coaching' ? 'coach' : 'client';

        return [
            'reply'         => $replyText,
            'scenario_type' => $scenarioType,
            'persona'       => $persona,
            'speaker'       => $speaker,
            'emotion'       => 'neutral',
        ];
    }

    /**
     * Build the system prompt that tells Gideon HOW to behave
     * based on scenario and persona.
     */
    protected function buildSystemMessage(string $scenarioType, ?string $persona): string
    {
        $base = "You are Gideon, an elite insurance sales trainer and sparring partner. "
              . "You help agents practice conversations, overcome objections, and plan their next steps. ";

        switch ($scenarioType) {
            case 'single_prospect':
                $base .= "Act as a single insurance prospect in a 1-on-1 presentation. "
                    . "Stay in character as the client unless the user explicitly asks for coaching.";
                break;

            case 'couple_presentation':
                $base .= "Act as a couple receiving an insurance presentation. "
                    . "Sometimes you speak as Partner A (more emotional) and sometimes as Partner B (more analytical). "
                    . "Indicate clearly who is speaking in your text (e.g., 'Partner A:' or 'Partner B:').";
                break;

            case 'referral_partner':
                $base .= "Act as a referral partner, such as a funeral director, pastor, or CPA. "
                    . "Your focus is on whether working with the agent makes sense for you and the people you serve.";
                break;

            case 'generic_coaching':
            default:
                $base .= "Act as a coaching voice, giving direct, practical advice and example scripts.";
                break;
        }

        if ($persona) {
            $base .= " The persona you are playing is: {$persona}. Adjust your tone and objections to match.";
        }

        // Keep instructions short and tight for Tier 1
        $base .= " Keep responses concise and practical. Do not ramble.";

        return $base;
    }
}
