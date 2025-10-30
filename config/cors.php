<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:5174',
        'http://crm-backend.test:5173',   // if using a .test Vite URL
        'http://crm-backend.test',        // if serving static build on this host
        'https://backend-api.samsglobal.co.uk'
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false, // keep false since you use Bearer tokens, not cookies
];
