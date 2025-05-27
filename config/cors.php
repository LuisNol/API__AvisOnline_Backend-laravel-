
<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://www.avisonline.store',
        'https://admin.avisonline.store',
        'http://localhost:4200', // si usas Angular en local
        'http://localhost:500', // si usas Angular en local
        'http://127.0.0.1:4200', // alternativa para local
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // IMPORTANTE si usas cookies/session/token

];
