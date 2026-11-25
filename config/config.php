<?php

return [
    'app_name' => 'DEVSMW Profiles',
    'app_url' => rtrim(getenv('APP_URL') ?: 'http://localhost/DEVSMW', '/'),
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'name' => getenv('DB_NAME') ?: 'devsmw_profiles',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
        'charset' => 'utf8mb4',
    ],
    'github' => [
        // Optional: set a token to increase rate limits.
        'token' => getenv('GITHUB_TOKEN') ?: '',
    ],
    'search' => [
        'engine' => getenv('SEARCH_ENGINE') ?: 'duckduckgo',
        'key' => getenv('BING_SEARCH_KEY') ?: '',
        'bing' => [
            'endpoint' => getenv('BING_SEARCH_ENDPOINT') ?: 'https://api.bing.microsoft.com/v7.0/search',
        ],
        // Use 'duckduckgo' for free external search results without a paid API key.
    ],
];
