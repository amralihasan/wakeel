<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Model Registry
    |--------------------------------------------------------------------------
    |
    | Each entry: key, provider (anthropic|openai), label (admin display),
    | supports_tools (must be true to be selectable as agent model),
    | input_cost_per_mtok / output_cost_per_mtok (USD per million tokens),
    | enabled (bool).
    |
    */

    'models' => [
        'claude-haiku-4-5' => [
            'provider' => 'anthropic',
            'label' => 'Claude Haiku 4.5',
            'supports_tools' => true,
            'input_cost_per_mtok' => 0.80,
            'output_cost_per_mtok' => 4.00,
        ],
        'claude-sonnet-4-6' => [
            'provider' => 'anthropic',
            'label' => 'Claude Sonnet 4.6',
            'supports_tools' => true,
            'input_cost_per_mtok' => 3.00,
            'output_cost_per_mtok' => 15.00,
        ],
        'gpt-4o' => [
            'provider' => 'openai',
            'label' => 'GPT-4o',
            'supports_tools' => true,
            'input_cost_per_mtok' => 2.50,
            'output_cost_per_mtok' => 10.00,
        ],
        'gpt-4o-mini' => [
            'provider' => 'openai',
            'label' => 'GPT-4o Mini',
            'supports_tools' => true,
            'input_cost_per_mtok' => 0.15,
            'output_cost_per_mtok' => 0.60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default model key
    |--------------------------------------------------------------------------
    |
    | Used when no platform default or company override is set.
    |
    */
    'default' => env('AI_DEFAULT_MODEL', 'claude-haiku-4-5'),

    /*
    |--------------------------------------------------------------------------
    | Fallback model key
    |--------------------------------------------------------------------------
    |
    | Used when the primary model/provider fails and fallback is enabled.
    |
    */
    'fallback' => env('AI_FALLBACK_MODEL', 'gpt-4o-mini'),
];
