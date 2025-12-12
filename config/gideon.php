<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global Enable / Disable
    |--------------------------------------------------------------------------
    */

    'enabled' => (bool) env('GIDEON_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | LLM Provider & Model
    |--------------------------------------------------------------------------
    */

    'llm_provider' => env('GIDEON_LLM_PROVIDER', 'openai'),

    'llm_model_tier1' => env('GIDEON_OPENAI_MODEL_TIER1', 'gpt-4.1'),

    /*
    |--------------------------------------------------------------------------
    | LLM Defaults
    |--------------------------------------------------------------------------
    */

    'max_tokens_default' => (int) env('GIDEON_MAX_TOKENS_DEFAULT', 800),
    'timeout_seconds'    => (int) env('GIDEON_TIMEOUT_SECONDS', 20),

    /*
    |--------------------------------------------------------------------------
    | Sparring-specific LLM Controls (C3)
    |--------------------------------------------------------------------------
    */

    'sparring' => [
        'llm_enabled'   => (bool) env('GIDEON_SPARRING_LLM_ENABLED', false),
        'history_limit' => (int) env('GIDEON_SPARRING_LLM_HISTORY_LIMIT', 8),
        'temperature'   => (float) env('GIDEON_SPARRING_LLM_TEMPERATURE', 0.7),
        'max_tokens'    => (int) env('GIDEON_SPARRING_LLM_MAX_TOKENS', 280),
    ],

    /*
    |--------------------------------------------------------------------------
    | Coaching (C4)
    |--------------------------------------------------------------------------
    |
    | Coaching is separate from sparring replies. It must never change the
    | "prospect" voice. Coaching writes Strengths/Improvements at end-of-session.
    |
    */

    'coaching' => [
        // Master switch for C4 coaching features
        'enabled' => (bool) env('GIDEON_COACHING_ENABLED', false),

        // LLM-written narrative on endSession()
        'assessment_llm_enabled' => (bool) env('GIDEON_COACHING_ASSESSMENT_LLM_ENABLED', false),

        // How many recent messages to include when writing the assessment
        'assessment_history_limit' => (int) env('GIDEON_COACHING_ASSESSMENT_HISTORY_LIMIT', 18),

        // Style controls for coaching voice
        'temperature' => (float) env('GIDEON_COACHING_TEMPERATURE', 0.4),

        // Token cap for the coaching output (strengths+improvements)
        'max_tokens'  => (int) env('GIDEON_COACHING_MAX_TOKENS', 420),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting (Soft Config)
    |--------------------------------------------------------------------------
    */

    'rate_limits' => [
        'ask_per_user_per_minute'      => (int) env('GIDEON_ASK_PER_USER_PER_MINUTE', 6),
        'ask_per_tenant_per_minute'    => (int) env('GIDEON_ASK_PER_TENANT_PER_MINUTE', 60),
        'refresh_per_tenant_per_hour'  => (int) env('GIDEON_REFRESH_PER_TENANT_PER_HOUR', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Adaptive Intelligence
    |--------------------------------------------------------------------------
    */

    'adaptive' => [
        'enabled'                      => (bool) env('GIDEON_ADAPTIVE_ENABLED', true),
        'min_opportunities_for_tuning' => (int) env('GIDEON_MIN_OPPS_FOR_TUNING', 50),
        'min_sessions_for_tuning'      => (int) env('GIDEON_MIN_SESSIONS_FOR_TUNING', 20),
    ],

];
