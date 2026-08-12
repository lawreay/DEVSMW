<?php

declare(strict_types=1);

function github_request(string $url): array
{
    $headers = [
        'User-Agent: DEVSMW-Profiles',
        'Accept: application/vnd.github+json',
        'X-GitHub-Api-Version: 2022-11-28',
    ];

    $token = config('github.token');
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
            'timeout' => 20,
            'ignore_errors' => true,
        ],
    ]);

    $body = file_get_contents($url, false, $context);
    if ($body === false) {
        throw new RuntimeException('GitHub request failed.');
    }

    $status = $http_response_header[0] ?? '';
    if (!str_contains($status, '200')) {
        throw new RuntimeException('GitHub returned: ' . $status);
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        throw new RuntimeException('GitHub returned invalid JSON.');
    }

    return $data;
}

function refresh_profile_from_github(int $profileId): void
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM profiles WHERE id = ?');
    $stmt->execute([$profileId]);
    $profile = $stmt->fetch();
    if (!$profile) {
        throw new RuntimeException('Profile not found.');
    }

    $username = $profile['github_username'];
    $user = github_request('https://api.github.com/users/' . rawurlencode($username));
    $repos = github_request('https://api.github.com/users/' . rawurlencode($username) . '/repos?sort=updated&per_page=100');

    usort($repos, static function (array $a, array $b): int {
        return ($b['stargazers_count'] ?? 0) <=> ($a['stargazers_count'] ?? 0);
    });

    $pdo->beginTransaction();

    $update = $pdo->prepare(
        'UPDATE profiles
         SET name = COALESCE(NULLIF(?, ""), name),
             location = COALESCE(NULLIF(?, ""), location),
             work = COALESCE(NULLIF(?, ""), work),
             email = COALESCE(NULLIF(?, ""), email),
             website = COALESCE(NULLIF(?, ""), website),
             bio = COALESCE(NULLIF(?, ""), bio),
             github_url = ?,
             last_synced_at = NOW(),
             updated_at = NOW()
         WHERE id = ?'
    );
    $update->execute([
        $user['name'] ?? '',
        $user['location'] ?? '',
        $user['company'] ?? '',
        $user['email'] ?? '',
        $user['blog'] ?? '',
        $user['bio'] ?? '',
        $user['html_url'] ?? ('https://github.com/' . $username),
        $profileId,
    ]);

    $pdo->prepare('DELETE FROM projects WHERE profile_id = ? AND source = "github"')->execute([$profileId]);
    $insert = $pdo->prepare(
        'INSERT INTO projects (profile_id, name, description, url, language, stars, source, is_private, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, "github", 0, NOW(), NOW())'
    );

    foreach (array_slice($repos, 0, 12) as $repo) {
        $insert->execute([
            $profileId,
            $repo['name'] ?? '',
            $repo['description'] ?? null,
            $repo['html_url'] ?? null,
            $repo['language'] ?? null,
            (int) ($repo['stargazers_count'] ?? 0),
        ]);
    }

    $pdo->commit();
}
