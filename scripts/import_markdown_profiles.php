<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

$profilesDir = __DIR__ . '/../malawi_profiles';
$rankData = json_decode(file_get_contents(__DIR__ . '/../malawi_rank.json'), true);
$ranks = [];
foreach (($rankData['user_private'] ?? []) as $index => $username) {
    $ranks[$username] = $index + 1;
}

function extract_line_value(string $markdown, string $label): ?string
{
    if (preg_match('/\*\*' . preg_quote($label, '/') . ':\*\*\s*(.+)/i', $markdown, $match)) {
        $value = trim(strip_tags($match[1]));
        $value = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $value);
        return trim($value) !== 'Not publicly available' ? trim($value) : null;
    }
    return null;
}

function extract_heading_name(string $markdown, string $username): string
{
    if (preg_match('/^#\s+(.+?)(?:\s+\(' . preg_quote($username, '/') . '\))?\s*$/m', $markdown, $match)) {
        return trim($match[1]);
    }
    return $username;
}

function extract_section(string $markdown, string $heading): ?string
{
    $pattern = '/^#{1,2}\s+' . preg_quote($heading, '/') . '\s*$([\s\S]*?)(?=^#{1,2}\s+|\z)/mi';
    if (preg_match($pattern, $markdown, $match)) {
        return trim($match[1]);
    }
    return null;
}

$pdo = db();
$profileUpsert = $pdo->prepare(
    'INSERT INTO profiles
        (github_username, name, location, work, phone, email, website, github_url, bio, strengths, markdown, rank_private, visibility, created_at, updated_at)
     VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "published", NOW(), NOW())
     ON DUPLICATE KEY UPDATE
        name = VALUES(name),
        location = VALUES(location),
        work = VALUES(work),
        phone = VALUES(phone),
        email = VALUES(email),
        website = VALUES(website),
        github_url = VALUES(github_url),
        bio = VALUES(bio),
        strengths = VALUES(strengths),
        markdown = VALUES(markdown),
        rank_private = VALUES(rank_private),
        updated_at = NOW()'
);

$projectInsert = $pdo->prepare(
    'INSERT INTO projects (profile_id, name, description, url, language, stars, source, is_private, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, "markdown", ?, NOW(), NOW())'
);

$count = 0;
foreach (glob($profilesDir . '/*.md') as $file) {
    $username = pathinfo($file, PATHINFO_FILENAME);
    $markdown = file_get_contents($file);
    $name = extract_heading_name($markdown, $username);
    $bio = extract_section($markdown, 'Professional Profile') ?: extract_section($markdown, 'About / What they are good at');
    $strengths = extract_section($markdown, 'What They Are Good At') ?: extract_section($markdown, 'Core Areas');

    $profileUpsert->execute([
        $username,
        $name,
        extract_line_value($markdown, 'Location'),
        extract_line_value($markdown, 'Work'),
        extract_line_value($markdown, 'Phone'),
        extract_line_value($markdown, 'Email'),
        extract_line_value($markdown, 'Website'),
        'https://github.com/' . $username,
        $bio,
        $strengths,
        $markdown,
        $ranks[$username] ?? null,
    ]);

    $profileId = (int) $pdo->lastInsertId();
    if ($profileId === 0) {
        $lookup = $pdo->prepare('SELECT id FROM profiles WHERE github_username = ?');
        $lookup->execute([$username]);
        $profileId = (int) $lookup->fetchColumn();
    }

    $pdo->prepare('DELETE FROM projects WHERE profile_id = ? AND source = "markdown"')->execute([$profileId]);
    $projectSection = extract_section($markdown, 'Top Projects') ?: extract_section($markdown, 'Public GitHub Projects') ?: '';
    preg_match_all('/^[*-]\s+(?:\[(.*?)\]\((.*?)\)|\*\*(.*?)\*\*)\s*(?:\((.*?)\))?\s*(?:[-—]\s*(.*))?$/m', $projectSection, $matches, PREG_SET_ORDER);
    foreach (array_slice($matches, 0, 15) as $match) {
        $projectInsert->execute([
            $profileId,
            trim($match[1] ?: $match[3] ?: 'Untitled Project'),
            trim($match[5] ?? '') ?: null,
            trim($match[2] ?? '') ?: null,
            trim($match[4] ?? '') ?: null,
            0,
            str_contains(strtolower($match[0]), 'private') ? 1 : 0,
        ]);
    }

    $count++;
}

echo "Imported {$count} profiles.\n";
