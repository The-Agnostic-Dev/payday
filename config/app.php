<?php

return [

    'name' => env('APP_NAME', 'Payday'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost:5119'),

    // Adunni Foods runs payroll in Lagos time; payroll periods and leave dates follow it.
    'timezone' => 'Africa/Lagos',

    'locale' => 'en',

    'fallback_locale' => 'en',

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    // Shown on payslips and in the CSV export.
    'company' => [
        'name' => env('PAYDAY_COMPANY', 'Adunni Foods Ltd'),
        'address' => env('PAYDAY_COMPANY_ADDRESS', '14 Oba Akran Avenue, Ikeja, Lagos'),
    ],

];
