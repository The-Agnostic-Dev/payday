<?php

return [

    'default' => env('DB_CONNECTION', 'sqlite'),

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => true,
            // `php artisan serve` runs several workers, so give writers time to queue up.
            'busy_timeout' => 5000,
            'journal_mode' => 'wal',
            'synchronous' => 'normal',
            'transaction_mode' => 'DEFERRED',
        ],

    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

];
