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

    public function __construct()
    {
        $this->apiKey        = env('GIDEON_OPENAI_API_KEY', '');
        $this->provider      = config('gideon.llm_provider', 'openai');
        $this->model         = config('gideon.llm_model_tier1', 'gpt-4.1');
        $this->maxTokens     = (int) config('gideon.max_tokens_default', 800);
        $this->timeoutSeconds = (int) config('gideon.timeout_seconds', 20);

        if (empty($this->apiKey)) {
            // We throw here so you see it clearly in logs if misconfigured.
            throw new RuntimeException('GideonLlmClient: GIDEON_OPENAI_API_KEY is not set.');
        }
    }

    /**
     * Basic chat method used by other Gideon services.
     *
     * @param  array  $messages  [
     *   ['role' => 'system', 'content' => '...'],
     *   ['role' => 'user', 'content' => '...'],
     * ]
     */
    public function chat(array $messages, array $options = []): string
    {
        if ($this->provider !== 'openai') {
            throw new RuntimeException('GideonLlmClient: Only "openai" provider is supported in Tier 1.');
        }

        $payload = [
            'model'    => $this->model,
            'messages' => $messages,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
            'temperature' => $options['temperature'] ?? 0.2,
        ];

        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeoutSeconds)
            ->acceptJson()
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        if (! $response->successful()) {
            $id = Str::uuid()->toString();
            // You can log more here later
            throw new RuntimeException(
                "GideonLlmClient: OpenAI request failed (ref {$id}). Status: {$response->status()}"
            );
        }

        $data = $response->json();

        // Defensive checks
        if (! isset($data['choices'][0]['message']['content'])) {
            throw new RuntimeException('GideonLlmClient: Unexpected OpenAI response format.');
        }

        return $data['choices'][0]['message']['content'];
    }

    /**
     * Simple test helper you can call from a route or tinker
     * to verify the connection works in DigitalOcean.
     */
    public function testPing(): string
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are Gideon, an AI assistant helping insurance agents.',
            ],
            [
                'role' => 'user',
                'content' => 'Say one short sentence confirming Gideon is connected.',
            ],
        ];

        return $this->chat($messages);
    }
}
