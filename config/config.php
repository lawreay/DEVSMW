<?php

$dbHost = getenv('DB_HOST') ?: '';
$dbName = getenv('DB_NAME') ?: '';
$dbUser = getenv('DB_USER') ?: '';
$dbPass = getenv('DB_PASS');

if ($dbHost === '' || $dbName === '' || $dbUser === '' || $dbPass === false) {
    throw new RuntimeException(
        'Database configuration is incomplete. Copy .env.example to .env and set DB_HOST, DB_NAME, DB_USER, and DB_PASS.'
    );
}

return [
    'app_name' => getenv('APP_NAME') ?: 'DEVSMW Profiles',
    'app_url' => rtrim(getenv('APP_URL') ?: 'http://localhost/DEVSMW', '/'),
    'db' => [
        'host' => $dbHost,
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'name' => $dbName,
        'user' => $dbUser,
        'pass' => $dbPass,
        'charset' => 'utf8mb4',
    ],
    'github' => [
        // Optional: set a token to increase GitHub API rate limits.
        'token' => getenv('GITHUB_TOKEN') ?: '',
    ],
    'search' => [
        'engine' => getenv('SEARCH_ENGINE') ?: 'duckduckgo',
        'key' => getenv('BING_SEARCH_KEY') ?: '',
        'bing' => [
            'endpoint' => getenv('BING_SEARCH_ENDPOINT') ?: 'https://api.bing.microsoft.com/v7.0/search',
        ],
    ],
];
