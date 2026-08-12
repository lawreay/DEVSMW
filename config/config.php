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
    'github' => [
        // Optional: set a token to increase rate limits.
        'token' => getenv('GITHUB_TOKEN') ?: '',
    ],
    'search' => [
        'engine' => getenv('SEARCH_ENGINE') ?: 'local',
        'key' => getenv('BING_SEARCH_KEY') ?: '',
        'bing' => [
            'endpoint' => getenv('BING_SEARCH_ENDPOINT') ?: 'https://api.bing.microsoft.com/v7.0/search',
        ],
        // Use 'duckduckgo' for free external search results without a paid API key.
    ],
];
