<?php

return [
  'paths' => ['api/*', 'sanctum/csrf-cookie'],
  'allowed_methods' => ['*'],
  'allowed_origins' => [
    'https://www.avisonline.store',
    'https://www.admin.avisonline.store'
  ],
  'allowed_headers' => ['*'],
  'exposed_headers' => [],
  'max_age' => 0,
  'supports_credentials' => true,  // Cambia a true si necesitas cookies/autenticación
];
