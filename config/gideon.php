<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global Enable / Disable
    |--------------------------------------------------------------------------
    |
    | Master switch for Gideon features. You can also layer in per-tenant
    | and per-plan flags later. Nothing should run if this is false.
    |
    */

    'enabled' => env('GIDEON_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | LLM Provider & Model
    |--------------------------------------------------------------------------
    |
    | For Tier 1 we assume OpenAI-style chat models. This is abstracted
    | behind GideonLlmClient so we can swap later if needed.
    |
    */

    'llm_provider' => env('GIDEON_LLM_PROVIDER', 'openai'),

    'llm_model_tier1' => env('GIDEON_OPENAI_MODEL_TIER1', 'gpt-4.1'),

    /*
    |--------------------------------------------------------------------------
    | LLM Defaults
    |--------------------------------------------------------------------------
    */

    'max_tokens_default' => env('GIDEON_MAX_TOKENS_DEFAULT', 800),
    'timeout_seconds'    => env('GIDEON_TIMEOUT_SECONDS', 20),

    /*
    |--------------------------------------------------------------------------
    | Sparring-specific LLM Controls
    |--------------------------------------------------------------------------
    |
    | These flags control LLM behavior ONLY for the Sparring Partner.
    | Rule-based logic remains the fallback and safety net.
    |
    */

    'sparring' => [
        // Master switch for LLM replies in SparringService
        'llm_enabled'   => env('GIDEON_SPARRING_LLM_ENABLED', false),

        // Number of prior sparring messages sent to the LLM as context
        'history_limit' => env('GIDEON_SPARRING_LLM_HISTORY_LIMIT', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting (Soft Config)
    |--------------------------------------------------------------------------
    |
    | These are soft limits; you can enforce them using Laravel's rate
    | limiting features. Values are "requests per timeframe".
    |
    */

    'rate_limits' => [
        // Sparring Partner
        'ask_per_user_per_minute'   => env('GIDEON_ASK_PER_USER_PER_MINUTE', 6),
        'ask_per_tenant_per_minute' => env('GIDEON_ASK_PER_TENANT_PER_MINUTE', 60),

        // Opportunity refresh
        'refresh_per_tenant_per_hour' => env('GIDEON_REFRESH_PER_TENANT_PER_HOUR', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Adaptive Intelligence
    |--------------------------------------------------------------------------
    |
    | Controls for how aggressively Gideon adjusts its internal weights
    | based on outcomes. Tier 1: scaffolding only, simple weighting.
    |
    */

    'adaptive' => [
        'enabled'                      => env('GIDEON_ADAPTIVE_ENABLED', true),
        'min_opportunities_for_tuning' => env('GIDEON_MIN_OPPS_FOR_TUNING', 50),
        'min_sessions_for_tuning'      => env('GIDEON_MIN_SESSIONS_FOR_TUNING', 20),
    ],

];
