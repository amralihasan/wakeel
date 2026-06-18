<?php

return [
    'starter' => [
        'name' => 'Starter',
        'conversations_limit' => 500,
        'numbers_limit' => 1,
        'units_limit' => 10,
        'reps_limit' => 1,
        'amount' => env('PLAN_STARTER_PRICE', 500.00), // in EGP
    ],
    'growth' => [
        'name' => 'Growth',
        'conversations_limit' => 2000,
        'numbers_limit' => 1,
        'units_limit' => -1,
        'reps_limit' => 3,
        'amount' => env('PLAN_GROWTH_PRICE', 1500.00), // in EGP
    ],
    'enterprise' => [
        'name' => 'Enterprise',
        'conversations_limit' => 10000,
        'numbers_limit' => 2,
        'units_limit' => -1,
        'reps_limit' => -1,
        'amount' => env('PLAN_ENTERPRISE_PRICE', 5000.00), // in EGP
    ],
];
