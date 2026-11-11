<?php

return [
    'email' => strtolower((string) env('ADMIN_EMAIL', 'admin@usahaku.com')),
    'initial_password' => env('ADMIN_INITIAL_PASSWORD'),
];
