<?php

return [
    'starter' => [
        'name' => 'Starter',
        'conversations_limit' => 500,
        'numbers_limit' => 1,
        'units_limit' => 10,
        'reps_limit' => 1,
        'price_id' => env('STRIPE_PRICE_STARTER', 'price_starter_test_id'),
    ],
    'growth' => [
        'name' => 'Growth',
        'conversations_limit' => 2000,
        'numbers_limit' => 1,
        'units_limit' => -1,
        'reps_limit' => 3,
        'price_id' => env('STRIPE_PRICE_GROWTH', 'price_growth_test_id'),
    ],
    'enterprise' => [
        'name' => 'Enterprise',
        'conversations_limit' => 10000,
        'numbers_limit' => 2,
        'units_limit' => -1,
        'reps_limit' => -1,
        'price_id' => env('STRIPE_PRICE_ENTERPRISE', 'price_enterprise_test_id'),
    ],
];
