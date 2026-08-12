<?php

declare(strict_types=1);

function local_profile_search(string $q): array
{
    $like = '%' . $q . '%';
    $stmt = db()->prepare(
        'SELECT * FROM profiles WHERE visibility = "published" AND (
             github_username LIKE ? OR name LIKE ? OR location LIKE ? OR work LIKE ? OR bio LIKE ? OR strengths LIKE ?
         ) ORDER BY COALESCE(rank_private, 999999), name, github_username LIMIT 100'
    );
    $stmt->execute([$like, $like, $like, $like, $like, $like]);
    return $stmt->fetchAll();
}

function bing_web_search(string $q): array
{
    $key = config('search.key');
    $endpoint = config('search.bing.endpoint') ?: 'https://api.bing.microsoft.com/v7.0/search';
    if (!$key) {
        return [];
    }

    $url = $endpoint . '?q=' . rawurlencode($q) . '&mkt=en-US&count=8';
    $headers = [
        'Ocp-Apim-Subscription-Key: ' . $key,
        'User-Agent: DEVSMW-Profiles',
    ];

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
            'timeout' => 20,
            'ignore_errors' => true,
        ],
    ]);

    $body = @file_get_contents($url, false, $context);
    if ($body === false) {
        return [];
    }

    $data = json_decode($body, true);
    if (!is_array($data) || empty($data['webPages']['value'])) {
        return [];
    }

    return array_map(static function (array $item): array {
        return [
            'name' => $item['name'] ?? 'Search result',
            'url' => $item['url'] ?? '#',
            'snippet' => $item['snippet'] ?? '',
        ];
    }, $data['webPages']['value']);
}

function search_results(string $q): array
{
    $engine = config('search.engine');
    if ($engine === 'bing') {
        return bing_web_search($q);
    }
    return local_profile_search($q);
}

function search_is_external(): bool
{
    return config('search.engine') === 'bing' && config('search.key') !== '';
}
