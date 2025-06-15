<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:4200',
        'http://localhost:5000',
        'http://127.0.0.1:4200',
        'http://127.0.0.1:5000',
        'https://www.admin.avisonline.store',
        'https://www.avisonline.store/',

    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
