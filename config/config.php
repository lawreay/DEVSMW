<?php

return [
    'app_name' => 'DEVSMW Profiles',
    'app_url' => 'http://localhost/DEVSMW',
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'devsmw_profiles',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'admin' => [
        'username' => 'admin',
        // Change this immediately after first setup.
        'password' => 'change-me-now',
    ],
    'github' => [
        // Optional: set a token to increase rate limits.
        'token' => getenv('GITHUB_TOKEN') ?: '',
    ],
];
