<?php

return [
    'starter' => [
        'key' => 'starter',
        'name' => 'Starter',
        'price_cents' => (int) env('PLAN_STARTER_PRICE_CENTS', 9900),
        'currency' => 'EGP',
        'period' => 'monthly',
        'limits' => [
            'conversation_quota' => 500,
            'numbers' => 1,
            'units' => 10,
            'reps' => 1,
        ],
    ],
    'growth' => [
        'key' => 'growth',
        'name' => 'Growth',
        'price_cents' => (int) env('PLAN_GROWTH_PRICE_CENTS', 24900),
        'currency' => 'EGP',
        'period' => 'monthly',
        'limits' => [
            'conversation_quota' => 2000,
            'numbers' => 1,
            'units' => null,
            'reps' => 3,
        ],
    ],
    'enterprise' => [
        'key' => 'enterprise',
        'name' => 'Enterprise',
        'price_cents' => (int) env('PLAN_ENTERPRISE_PRICE_CENTS', 59900),
        'currency' => 'EGP',
        'period' => 'monthly',
        'limits' => [
            'conversation_quota' => 10000,
            'numbers' => 2,
            'units' => null,
            'reps' => null,
        ],
    ],
];
