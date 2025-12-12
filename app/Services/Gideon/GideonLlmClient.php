<?php

namespace App\Services\Gideon;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GideonLlmClient
{
    protected string $apiKey;
    protected string $provider;
    protected string $model;
    protected int $maxTokens;
    protected int $timeoutSeconds;
    protected bool $fakeMode;

    public function __construct()
    {
        $this->apiKey         = env('GIDEON_OPENAI_API_KEY', '');
        $this->provider       = config('gideon.llm_provider', 'openai');
        $this->model          = config('gideon.llm_model_tier1', 'gpt-4.1');
        $this->maxTokens      = (int) config('gideon.max_tokens_default', 800);
        $this->timeoutSeconds = (int) config('gideon.timeout_seconds', 20);
        $this->fakeMode       = (bool) env('GIDEON_FAKE_MODE', false);
    }

    public function chat(array $messages, array $options = []): string
    {
        if ($this->fakeMode || empty($this->apiKey)) {
            return '[FAKE GIDEON REPLY] OpenAI is not enabled yet (missing key or fake mode).';
        }

        if ($this->provider !== 'openai') {
            throw new RuntimeException('GideonLlmClient: Only "openai" provider is supported in Tier 1.');
        }

        $payload = [
            'model'       => $this->model,
            'messages'    => $messages,
            'max_tokens'  => $options['max_tokens'] ?? $this->maxTokens,
            'temperature' => $options['temperature'] ?? 0.2,
        ];

        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeoutSeconds)
            ->acceptJson()
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        if (! $response->successful()) {
            $status = $response->status();

            if (in_array($status, [401, 403, 429], true)) {
                return '[FAKE GIDEON REPLY] OpenAI unavailable (auth/quota). Using safe fallback.';
            }

            $id = Str::uuid()->toString();
            throw new RuntimeException("GideonLlmClient: OpenAI request failed (ref {$id}). Status: {$status}");
        }

        $data = $response->json();

        if (! isset($data['choices'][0]['message']['content'])) {
            throw new RuntimeException('GideonLlmClient: Unexpected OpenAI response format.');
        }

        return (string) $data['choices'][0]['message']['content'];
    }

    /**
     * A3: One entry point for Sparring replies.
     * Chooses prospect-voice vs agent-voice based on context['session']['gideon_role'].
     */
    public function generateSparringReply(array $context): string
    {
        $gideonRole = data_get($context, 'session.gideon_role', 'prospect'); // 'prospect' or 'agent'
        $mode       = data_get($context, 'session.mode', 'prospect_simulation');
        $persona    = (string) data_get($context, 'session.persona_key', 'adaptive');

        $scenarioName = (string) data_get($context, 'scenario.name', 'Unknown scenario');
        $scenarioDesc = (string) data_get($context, 'scenario.description', '');
        $profile      = data_get($context, 'scenario.prospect_profile', []);
        $state        = data_get($context, 'state', []);

        $trust      = (int) data_get($state, 'trust', 50);
        $resistance = (int) data_get($state, 'resistance', 50);
        $urgency    = (int) data_get($state, 'urgency', 50);
        $phase      = (string) data_get($state, 'phase', 'opening');

        $recentLines = [];
        foreach ((array) data_get($context, 'recent_messages', []) as $m) {
            $roleLabel = $m['role'] ?? null; // saved role meta
            if (! $roleLabel) {
                $roleLabel = ($m['sender'] ?? '') === 'gideon' ? 'gideon' : 'user';
            }

            // Normalize labels for transcript readability
            $label = match ($roleLabel) {
                'agent'    => 'Agent',
                'prospect' => 'Prospect',
                default    => (($m['sender'] ?? '') === 'gideon' ? 'Gideon' : 'You'),
            };

            $content = trim((string) ($m['content'] ?? ''));
            if ($content !== '') {
                $recentLines[] = "{$label}: {$content}";
            }
        }
        $transcript = $recentLines ? implode("\n", $recentLines) : '(no prior messages)';

        // Role-specific system instruction
        if ($gideonRole === 'agent') {
            $system = <<<SYS
You are Gideon playing the ROLE of an elite insurance AGENT in a sparring simulation.
Your job: respond to the Prospect naturally, calmly, and persuasively.
Follow these rules:
- Sound human. No corporate robot tone.
- Ask 1 smart question OR make 1 clear, helpful move (not both every time).
- Use short paragraphs. No bullet lists.
- Do NOT mention you are an AI, model, or prompt.
- Do NOT write "Agent:" or "Gideon:" prefixes. Output ONLY the agent's next spoken line.
SYS;
        } else {
            $system = <<<SYS
You are Gideon playing the ROLE of a real insurance PROSPECT in a sparring simulation.
Your job: respond like a human prospect with emotion and nuance.
Follow these rules:
- Sound human, a bit imperfect (hesitations are ok).
- Keep it 1-3 short paragraphs.
- Maintain the prospect persona and objections.
- Do NOT become the agent or give advice.
- Do NOT mention you are an AI, model, or prompt.
- Do NOT write "Prospect:" or "Gideon:" prefixes. Output ONLY the prospect's next spoken line.
SYS;
        }

        $user = <<<USR
Scenario: {$scenarioName}
Scenario description: {$scenarioDesc}

Persona key: {$persona}
Prospect profile (JSON-ish): {json_encode($profile)}

State:
- Phase: {$phase}
- Trust (0-100): {$trust}
- Resistance (0-100): {$resistance}
- Urgency (0-100): {$urgency}

Conversation so far:
{$transcript}

Now respond with the next line as the {$gideonRole}. Keep it realistic and context-aware.
USR;

        $temperature = (float) config('gideon.sparring.temperature', 0.7);
        $maxTokens   = (int) config('gideon.sparring.max_tokens', 280);

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ];

        $text = $this->chat($messages, [
            'temperature' => $temperature,
            'max_tokens'  => $maxTokens,
        ]);

        return trim((string) $text);
    }

    public function testPing(): string
    {
        $messages = [
            ['role' => 'system', 'content' => 'You are Gideon, an AI assistant helping insurance agents.'],
            ['role' => 'user', 'content' => 'Say one short sentence confirming Gideon is connected.'],
        ];

        return $this->chat($messages);
    }
}
