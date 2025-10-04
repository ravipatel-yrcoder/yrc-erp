<?php
return [
    'default' => 'main_db',
    'connections' => [
        'main_db' => [
            'driver' => env('DB_CONNECTION', 'mysql'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'app_db'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',            
        ],
        'mysql_reporting' => [
            'driver' => 'mysql',
            'host' => env('DB2_HOST', '127.0.0.1'),
            'database' => env('DB2_DATABASE', 'reporting_db'),
            'username' => env('DB2_USERNAME', 'root'),
            'password' => env('DB2_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
    ],
];