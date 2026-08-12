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

function duckduckgo_web_search(string $q): array
{
    $url = 'https://html.duckduckgo.com/html?q=' . rawurlencode($q);
    $headers = [
        'User-Agent: DEVSMW-Profiles',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    ];

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => false,
            CURLOPT_USERAGENT => 'DEVSMW-Profiles',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FAILONERROR => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body = curl_exec($curl);
        curl_close($curl);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
    }

    if ($body === false || $body === null) {
        return [];
    }

    $results = [];
    if (preg_match_all('/<a[^>]+class="result__a"[^>]+href="([^"]+)"[^>]*>(.*?)<\/a>.*?<a[^>]+class="result__snippet"[^>]*>(.*?)<\/a>/si', $body, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            if (count($results) >= 8) {
                break;
            }
            $url = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $title = trim(strip_tags(html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            $snippet = trim(strip_tags(html_entity_decode($match[3], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            if ($url === '' || $title === '') {
                continue;
            }
            $results[] = ['name' => $title, 'url' => $url, 'snippet' => $snippet];
        }
    }

    if (empty($results) && preg_match_all('/<a[^>]+class="result__a"[^>]+href="([^"]+)"[^>]*>(.*?)<\/a>/si', $body, $linkMatches, PREG_SET_ORDER)) {
        foreach ($linkMatches as $match) {
            if (count($results) >= 8) {
                break;
            }
            $url = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $title = trim(strip_tags(html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            if ($url === '' || $title === '') {
                continue;
            }
            $results[] = ['name' => $title, 'url' => $url, 'snippet' => ''];
        }
    }

    return $results;
}

function search_results(string $q): array
{
    $engine = config('search.engine');
    if ($engine === 'bing') {
        return bing_web_search($q);
    }
    if ($engine === 'duckduckgo') {
        return duckduckgo_web_search($q);
    }
    return local_profile_search($q);
}

function search_is_external(): bool
{
    $engine = config('search.engine');
    if ($engine === 'bing') {
        return config('search.key') !== '';
    }
    return $engine === 'duckduckgo';
}
