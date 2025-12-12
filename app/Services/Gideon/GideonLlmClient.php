<?php

namespace App\Services\Gideon;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        $this->apiKey         = (string) env('GIDEON_OPENAI_API_KEY', '');
        $this->provider       = strtolower(trim((string) config('gideon.llm_provider', 'openai')));
        $this->model          = (string) config('gideon.llm_model_tier1', 'gpt-4.1');
        $this->maxTokens      = (int) config('gideon.max_tokens_default', 800);
        $this->timeoutSeconds = (int) config('gideon.timeout_seconds', 20);
        $this->fakeMode       = (bool) env('GIDEON_FAKE_MODE', false);

        // Do not throw here; we can operate in safe fake mode.
    }

    /**
     * Basic chat method used by other Gideon services.
     *
     * @param  array  $messages  [
     *   ['role' => 'system', 'content' => '...'],
     *   ['role' => 'user', 'content' => '...'],
     *   ...
     * ]
     */
    public function chat(array $messages, array $options = []): string
    {
        if ($this->fakeMode || empty($this->apiKey)) {
            return '[FAKE GIDEON REPLY] Gideon is wired up, but OpenAI is not enabled (missing key or fake mode).';
        }

        if ($this->provider !== 'openai') {
            throw new RuntimeException('GideonLlmClient: Only "openai" provider is supported in Tier 1.');
        }

        // ---- OpenAI Responses API payload ----
        // Convert chat-style messages to a single "input" string while preserving roles.
        // (This is stable, easy to debug, and works well for sparring.)
        $input = $this->messagesToInput($messages);

        $payload = [
            'model' => $this->model,
            'input' => $input,
        ];

        // Options (safe defaults)
        $payload['max_output_tokens'] = (int) ($options['max_tokens'] ?? $this->maxTokens);

        if (isset($options['temperature'])) {
            $payload['temperature'] = (float) $options['temperature'];
        }

        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeoutSeconds)
            ->acceptJson()
            ->post('https://api.openai.com/v1/responses', $payload);

        if (! $response->successful()) {
            $status = $response->status();
            $body   = (string) $response->body();
            $ref    = Str::uuid()->toString();

            // Log to stderr so DigitalOcean Runtime Logs can show it when LOG_CHANNEL=stderr
            Log::error('GideonLlmClient: OpenAI request failed', [
                'ref'    => $ref,
                'status' => $status,
                'body'   => mb_substr($body, 0, 1200),
            ]);

            // Soft fallback for auth/quota/rate-limit
            if (in_array($status, [401, 403, 429], true)) {
                return '[FAKE GIDEON REPLY] OpenAI is not available right now (auth/quota/rate limit).';
            }

            throw new RuntimeException("GideonLlmClient: OpenAI request failed (ref {$ref}). Status: {$status}");
        }

        $data = $response->json();

        // Responses API returns text in output[].content[].text in many cases.
        // We defensively extract any text segments.
        $text = $this->extractTextFromResponses($data);

        if ($text === '') {
            Log::warning('GideonLlmClient: Unexpected OpenAI response format', [
                'model' => $this->model,
                'data'  => $data,
            ]);
            throw new RuntimeException('GideonLlmClient: Unexpected OpenAI response format.');
        }

        return $text;
    }

    public function testPing(): string
    {
        $messages = [
            ['role' => 'system', 'content' => 'You are Gideon, an AI assistant helping insurance agents.'],
            ['role' => 'user', 'content' => 'Say one short sentence confirming Gideon is connected.'],
        ];

        return $this->chat($messages, ['temperature' => 0.2, 'max_tokens' => 80]);
    }

    /**
     * Turn chat messages into a single input string with roles.
     */
    protected function messagesToInput(array $messages): string
    {
        $lines = [];

        foreach ($messages as $m) {
            $role = isset($m['role']) ? strtoupper((string) $m['role']) : 'UNKNOWN';
            $content = (string) ($m['content'] ?? '');
            $content = trim($content);

            if ($content === '') {
                continue;
            }

            $lines[] = "{$role}: {$content}";
        }

        return implode("\n\n", $lines);
    }

    /**
     * Extract text from OpenAI Responses API result.
     */
    protected function extractTextFromResponses(array $data): string
    {
        // Common layout:
        // { output: [ { content: [ { type:"output_text", text:"..." } ] } ] }
        $out = '';

        if (!isset($data['output']) || !is_array($data['output'])) {
            return '';
        }

        foreach ($data['output'] as $item) {
            if (!isset($item['content']) || !is_array($item['content'])) {
                continue;
            }

            foreach ($item['content'] as $c) {
                if (is_array($c) && isset($c['text']) && is_string($c['text'])) {
                    $out .= $c['text'];
                }
            }
        }

        return trim($out);
    }
}
