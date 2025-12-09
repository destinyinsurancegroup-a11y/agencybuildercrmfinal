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

        // NOTE:
        // We no longer throw if apiKey is missing here.
        // If fakeMode is on OR apiKey is empty, we just return fake replies in chat().
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
        // If we're in fake mode OR we don't have an API key,
        // DO NOT call OpenAI at all. Just fake it.
        if ($this->fakeMode || empty($this->apiKey)) {
            return '[FAKE GIDEON REPLY] Gideon is wired up in the app, but OpenAI billing / API is not enabled yet.';
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

            // If we're getting rate-limited / unauthorized / forbidden by OpenAI,
            // fall back to fake mode instead of blowing up the app.
            if (in_array($status, [401, 403, 429], true)) {
                return '[FAKE GIDEON REPLY] Gideon attempted to call OpenAI, but the API is not available (billing/quota). Using safe fake response instead.';
            }

            $id = Str::uuid()->toString();
            throw new RuntimeException(
                "GideonLlmClient: OpenAI request failed (ref {$id}). Status: {$status}"
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
     * to verify the wiring works. In your current setup,
     * this will return a fake reply until billing is enabled.
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
