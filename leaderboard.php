<?php
require __DIR__ . '/includes/bootstrap.php';
set_page_cache_control('public');

$snapshotPath = __DIR__ . '/data/leaderboards/malawi.json';
$snapshot = null;
$loadError = false;

try {
    if (is_file($snapshotPath) && is_readable($snapshotPath)) {
        $decoded = json_decode((string) file_get_contents($snapshotPath), true, 512, JSON_THROW_ON_ERROR);
        if (is_array($decoded) && isset($decoded['ranked_users']) && is_array($decoded['ranked_users'])) {
            $snapshot = $decoded;
        }
    }
} catch (Throwable $exception) {
    $loadError = true;
}

$allUsers = $snapshot['ranked_users'] ?? [];
$query = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;

if ($query !== '') {
    $needle = mb_strtolower($query);
    $allUsers = array_values(array_filter($allUsers, static function (array $user) use ($needle): bool {
        $haystack = mb_strtolower(
            (string) ($user['login'] ?? '') . ' ' .
            (string) ($user['name'] ?? '') . ' ' .
            (string) ($user['location'] ?? '')
        );
        return mb_strpos($haystack, $needle) !== false;
    }));
}

$totalResults = count($allUsers);
$totalPages = max(1, (int) ceil($totalResults / $perPage));
$page = min($page, $totalPages);
$visibleUsers = array_slice($allUsers, ($page - 1) * $perPage, $perPage);
$generatedAt = $snapshot['generated_at'] ?? null;
$periodStart = $snapshot['period_start'] ?? null;
$periodEnd = $snapshot['period_end'] ?? null;
$candidateCount = (int) ($snapshot['candidate_count'] ?? 0);
$rankedCount = (int) ($snapshot['ranked_count'] ?? 0);
$hasSnapshot = is_string($generatedAt) && $generatedAt !== '' && $rankedCount > 0;

function leaderboard_profile_url(array $user): ?string
{
    $url = (string) ($user['profile_url'] ?? '');
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    return $host === 'github.com' ? $url : null;
}

function leaderboard_avatar_url(array $user): ?string
{
    $url = (string) ($user['avatar_url'] ?? '');
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    return $host === 'avatars.githubusercontent.com' ? $url : null;
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Malawi GitHub Contributions Leaderboard | DEVSMW</title>
    <?= meta_description('Explore a dated ranking of discoverable GitHub accounts in Malawi by public contributions over the last 365 days.') . "\n    " ?>
    <?= og_tags([
        'title' => 'Malawi GitHub Contributions Leaderboard | DEVSMW',
        'description' => 'A transparent, periodically updated ranking based on public GitHub contributions.',
        'type' => 'website',
        'url' => site_url('leaderboard.php'),
    ]) . "\n    " ?>
    <?= canonical_url(site_url('leaderboard.php')) . "\n    " ?>
    <link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/leaderboard.css')) ?>">
</head>
<body class="leaderboard-view">
<header class="topbar">
    <a class="brand" href="<?= e(site_url('index.php')) ?>">
        <span class="brand-mark">D</span>
        <span>DEVSMW Profiles</span>
    </a>
    <nav aria-label="Main navigation">
        <a href="<?= e(site_url('index.php')) ?>">Home</a>
        <a href="<?= e(site_url('profiles.php')) ?>">Profiles</a>
        <a class="active" href="<?= e(site_url('leaderboard.php')) ?>">Leaderboard</a>
        <a href="<?= e(site_url('docs/project-goal.md')) ?>">Docs</a>
    </nav>
</header>

<main class="page leaderboard-page">
    <section class="leaderboard-hero">
        <div class="leaderboard-hero-copy">
            <span class="eyebrow">Malawi developer activity</span>
            <h1>GitHub Contributions Leaderboard</h1>
            <p>Discover GitHub accounts associated with Malawi and compare their public contribution activity over the same rolling 365-day period.</p>
        </div>
        <div class="leaderboard-hero-note">
            <span class="leaderboard-note-label">What this ranks</span>
            <strong>Public GitHub contributions</strong>
            <span>Issues, pull requests, reviews, commits and other activity included in GitHub's public contribution calendar.</span>
        </div>
    </section>

    <section class="leaderboard-meta-grid" aria-label="Leaderboard snapshot details">
        <div class="leaderboard-meta-card">
            <span>Ranked accounts</span>
            <strong><?= $hasSnapshot ? number_format($rankedCount) : '—' ?></strong>
        </div>
        <div class="leaderboard-meta-card">
            <span>Discovery candidates</span>
            <strong><?= $hasSnapshot ? number_format($candidateCount) : '—' ?></strong>
        </div>
        <div class="leaderboard-meta-card">
            <span>Contribution period</span>
            <strong class="period-value"><?= $hasSnapshot && $periodStart && $periodEnd ? e(date('M j, Y', strtotime($periodStart)) . ' – ' . date('M j, Y', strtotime($periodEnd))) : 'Awaiting first run' ?></strong>
        </div>
        <div class="leaderboard-meta-card">
            <span>Last updated</span>
            <strong class="period-value"><?= $hasSnapshot ? e(gmdate('M j, Y H:i', strtotime($generatedAt)) . ' UTC') : 'Not generated' ?></strong>
        </div>
    </section>

    <section class="leaderboard-section">
        <div class="leaderboard-section-head">
            <div>
                <span class="eyebrow">National ranking</span>
                <h2>Most active GitHub accounts</h2>
                <p><?= $hasSnapshot ? number_format($totalResults) . ' accounts match this view.' : 'The first scheduled collection has not run yet.' ?></p>
            </div>
            <?php if ($hasSnapshot): ?>
                <form class="leaderboard-search" method="get" action="<?= e(site_url('leaderboard.php')) ?>">
                    <label class="visually-hidden" for="leaderboard-search">Search the leaderboard</label>
                    <input id="leaderboard-search" type="search" name="q" value="<?= e($query) ?>" placeholder="Search name, username or location">
                    <button type="submit">Search</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($loadError): ?>
            <div class="leaderboard-empty">
                <h3>Leaderboard data could not be read</h3>
                <p>The stored snapshot is invalid. The previous valid snapshot should be restored by rerunning the GitHub Actions collector.</p>
            </div>
        <?php elseif (!$hasSnapshot): ?>
            <div class="leaderboard-empty">
                <h3>First snapshot pending</h3>
                <p>The leaderboard is ready, but GitHub Actions has not published its first data snapshot. No contribution totals are being invented or estimated.</p>
            </div>
        <?php elseif (!$visibleUsers): ?>
            <div class="leaderboard-empty">
                <h3>No matching accounts</h3>
                <p>Try a different username, name or location.</p>
            </div>
        <?php else: ?>
            <div class="leaderboard-table-wrap">
                <table class="leaderboard-table">
                    <thead>
                        <tr>
                            <th scope="col">Rank</th>
                            <th scope="col">Developer</th>
                            <th scope="col">Location</th>
                            <th scope="col" class="contribution-heading">Contributions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($visibleUsers as $index => $user): ?>
                        <?php
                            $absoluteRank = ($page - 1) * $perPage + $index + 1;
                            $login = (string) ($user['login'] ?? '');
                            $displayName = trim((string) ($user['name'] ?? '')) ?: $login;
                            $profileUrl = leaderboard_profile_url($user);
                            $avatarUrl = leaderboard_avatar_url($user);
                            $initials = mb_strtoupper(mb_substr($displayName, 0, 1));
                        ?>
                        <tr>
                            <td class="rank-cell"><?= number_format($absoluteRank) ?></td>
                            <td>
                                <div class="leaderboard-person">
                                    <?php if ($avatarUrl): ?>
                                        <img src="<?= e($avatarUrl) ?>" alt="" loading="lazy" width="42" height="42">
                                    <?php else: ?>
                                        <span class="leaderboard-avatar-fallback"><?= e($initials) ?></span>
                                    <?php endif; ?>
                                    <div>
                                        <strong>
                                            <?php if ($profileUrl): ?>
                                                <a href="<?= e($profileUrl) ?>" target="_blank" rel="noopener noreferrer"><?= e($displayName) ?></a>
                                            <?php else: ?>
                                                <?= e($displayName) ?>
                                            <?php endif; ?>
                                        </strong>
                                        <span>@<?= e($login) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="location-cell"><?= e((string) (($user['location'] ?? '') ?: 'Not specified')) ?></td>
                            <td class="contribution-cell"><?= number_format((int) ($user['contributions'] ?? 0)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="leaderboard-pagination" aria-label="Leaderboard pages">
                    <?php if ($page > 1): ?>
                        <a href="<?= e(site_url('leaderboard.php?' . http_build_query(['q' => $query, 'page' => $page - 1]))) ?>">Previous</a>
                    <?php else: ?>
                        <span class="disabled">Previous</span>
                    <?php endif; ?>
                    <span>Page <?= number_format($page) ?> of <?= number_format($totalPages) ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= e(site_url('leaderboard.php?' . http_build_query(['q' => $query, 'page' => $page + 1]))) ?>">Next</a>
                    <?php else: ?>
                        <span class="disabled">Next</span>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <section class="leaderboard-methodology">
        <div>
            <span class="eyebrow">Methodology and limitations</span>
            <h2>How this ranking works</h2>
            <p><?= e((string) ($snapshot['methodology'] ?? 'The ranking uses a limited discovery sample of GitHub accounts with Malawi in their public location.')) ?></p>
            <p>GitHub's contribution calendar includes more than commits. This is an activity indicator, not a judgement of skill, code quality, employment status or overall contribution to Malawi's technology sector.</p>
            <p>Accounts with no Malawi location, a different location spelling, or low visibility in follower-sorted search may be missed. The ranking is therefore not a complete national census.</p>
            <p><a href="<?= e(site_url('docs/malawi-leaderboard.md')) ?>">Read the full methodology</a></p>
        </div>
    </section>
</main>
</body>
</html>
