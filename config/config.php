<?php

return [
    'app_name' => 'DEVSMW Profiles',
    'app_url' => getenv('APP_URL') ?: 'https://developersmw.lovestoblog.com',
    'db' => [
        'host' => getenv('DB_HOST') ?: 'sql108.infinityfree.com',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'name' => getenv('DB_NAME') ?: 'if0_42702166_devsmw',
        'user' => getenv('DB_USER') ?: 'if0_42702166',
        'pass' => getenv('DB_PASS') ?: 'rw7vsdZIUJ',
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
