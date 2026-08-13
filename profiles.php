<?php
require __DIR__ . '/includes/bootstrap.php';
set_page_cache_control('public');  // 5 min cache for search engines to see fresh metadata

$q = trim($_GET['q'] ?? '');
$params = [];
$sql = 'SELECT * FROM profiles WHERE visibility = "published"';

if ($q !== '') {
    $sql .= ' AND (github_username LIKE ? OR name LIKE ? OR location LIKE ? OR work LIKE ? OR bio LIKE ? OR strengths LIKE ?)';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like, $like, $like, $like];
}

$sql .= ' ORDER BY COALESCE(rank_private, 999999), name, github_username LIMIT 2000';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$profiles = $stmt->fetchAll();

$rankedProfiles = array_values(array_filter($profiles, static function (array $profile): bool {
    return !empty($profile['rank_private']) && (int) $profile['rank_private'] <= 50;
}));
$communityProfiles = array_values(array_filter($profiles, static function (array $profile): bool {
    return empty($profile['rank_private']) || (int) $profile['rank_private'] > 50;
}));
$locations = [];
$workTypes = [];
foreach ($profiles as $profile) {
    $location = trim((string) ($profile['location'] ?? ''));
    $work = trim((string) ($profile['work'] ?? ''));
    if ($location !== '') {
        $locations[$location] = ($locations[$location] ?? 0) + 1;
    }
    if ($work !== '') {
        $workTypes[$work] = ($workTypes[$work] ?? 0) + 1;
    }
}
arsort($locations);
arsort($workTypes);
$topLocations = array_slice($locations, 0, 6, true);
$topWorkTypes = array_slice($workTypes, 0, 6, true);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= !empty($q) ? 'Search Results: ' . e($q) . ' | ' : 'All Developer Profiles | ' ?><?= e(config('app_name')) ?></title>
    <?= meta_description(!empty($q) 
        ? 'Search results for "' . $q . '" in DEVSMW\'s developer directory. Found ' . count($profiles) . ' profiles matching your query.'
        : 'Browse all profiles in DEVSMW. Discover Malawi\'s developer talent across different specialties, technologies, and locations.') . "\n    " ?>
    <?= og_tags([
        'title' => (!empty($q) ? 'Search: ' . $q . ' | ' : '') . 'Developer Profiles | DEVSMW',
        'description' => 'Discover Malawi\'s developers and tech professionals in our growing directory.',
        'type' => 'website',
        'url' => site_url('profiles.php' . (!empty($q) ? '?q=' . urlencode($q) : '')),
    ]) . "\n    " ?>
    <?= twitter_card([
        'title' => 'DEVSMW Developer Directory',
        'description' => 'Browse profiles of Malawi\'s top developers.',
    ]) . "\n    " ?>
    <?= canonical_url(site_url('profiles.php' . (!empty($q) ? '?q=' . urlencode($q) : ''))) . "\n    " ?>
    <meta name="keywords" content="developer directory, Malawi developers, software engineers, tech professionals, developer profiles">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
</head>
<body class="directory-view">
<?= schema_organization() . "\n"?>
<header class="topbar">
    <a class="brand" href="<?= e(site_url('index.php')) ?>">
        <span class="brand-mark">D</span>
        <span>DEVSMW Profiles</span>
    </a>
    <nav>
        <a href="<?= e(site_url('index.php')) ?>">Home</a>
        <a class="active" href="<?= e(site_url('profiles.php')) ?>">Profiles</a>
        <a href="<?= e(site_url('docs/project-goal.md')) ?>">Docs</a>
    </nav>
</header>
<main class="page">
    <section class="directory-hero">
        <div>
            <span class="eyebrow">Profile directory</span>
            <h1>Find Malawi developers by rank, work, and location</h1>
            <p>Browse the complete published list, filter by name or skill, and use the category summaries to scan the community faster.</p>
        </div>
        <form class="search-form compact" method="get">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search profiles, skills, location...">
            <button type="submit">Search</button>
        </form>
    </section>

    <section class="directory-layout">
        <aside class="category-sidebar">
            <div class="stat-panel">
                <strong><?= count($profiles) ?></strong>
                <span>profiles shown<?= $q !== '' ? ' for "' . e($q) . '"' : '' ?></span>
            </div>
            <div class="filter-group">
                <h2>Popular locations</h2>
                <div class="tag-list">
                    <?php foreach ($topLocations as $location => $count): ?>
                        <a href="profiles.php?q=<?= urlencode($location) ?>"><?= e($location) ?> <span><?= (int) $count ?></span></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="filter-group">
                <h2>Work categories</h2>
                <div class="tag-list">
                    <?php foreach ($topWorkTypes as $work => $count): ?>
                        <a href="profiles.php?q=<?= urlencode($work) ?>"><?= e(mb_strimwidth($work, 0, 34, '...')) ?> <span><?= (int) $count ?></span></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if ($q !== ''): ?>
                <a class="clear-filters" href="profiles.php">Clear all filters</a>
            <?php endif; ?>
        </aside>

        <div class="directory-content">
            <?php if (count($profiles) === 0): ?>
                <section class="empty-state">
                    <h2>No profiles found</h2>
                    <p>Try a broader keyword like a technology, location, or role.</p>
                    <a class="button-link secondary" href="profiles.php">Clear search</a>
                </section>
            <?php endif; ?>

            <?php if (count($rankedProfiles) > 0): ?>
                <section class="profile-category">
                    <div class="section-head">
                        <div>
                            <h2>Top ranked developers</h2>
                            <p>Published profiles currently sitting inside the top 50.</p>
                        </div>
                        <span class="count-pill"><?= count($rankedProfiles) ?></span>
                    </div>
                    <div class="profile-grid">
                        <?php foreach ($rankedProfiles as $profile): ?>
                            <article class="profile-card">
                                <div class="card-top">
                                    <span class="rank"><?= $profile['rank_private'] ? '#' . e((string) $profile['rank_private']) : 'Profile' ?></span>
                                    <span class="status"><?= e($profile['consent_status']) ?></span>
                                </div>
                                <h2><?= e($profile['name'] ?: $profile['github_username']) ?></h2>
                                <p class="muted">@<?= e($profile['github_username']) ?></p>
                                <p><?= e(mb_strimwidth($profile['bio'] ?: $profile['strengths'] ?: 'No summary available yet.', 0, 180, '...')) ?></p>
                                <dl>
                                    <div><dt>Location</dt><dd><?= e($profile['location'] ?: 'Not available') ?></dd></div>
                                    <div><dt>Work</dt><dd><?= e($profile['work'] ?: 'Not available') ?></dd></div>
                                </dl>
                                <a class="button-link" href="profile.php?u=<?= urlencode($profile['github_username']) ?>">View profile</a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (count($communityProfiles) > 0): ?>
                <section class="profile-category">
                    <div class="section-head">
                        <div>
                            <h2>Community profiles</h2>
                            <p>More published developers to explore across roles, cities, and specialties.</p>
                        </div>
                        <span class="count-pill"><?= count($communityProfiles) ?></span>
                    </div>
                    <div class="profile-grid">
                        <?php foreach ($communityProfiles as $profile): ?>
                            <article class="profile-card">
                                <div class="card-top">
                                    <span class="rank"><?= $profile['rank_private'] ? '#' . e((string) $profile['rank_private']) : 'Profile' ?></span>
                                    <span class="status"><?= e($profile['consent_status']) ?></span>
                                </div>
                                <h2><?= e($profile['name'] ?: $profile['github_username']) ?></h2>
                                <p class="muted">@<?= e($profile['github_username']) ?></p>
                                <p><?= e(mb_strimwidth($profile['bio'] ?: $profile['strengths'] ?: 'No summary available yet.', 0, 180, '...')) ?></p>
                                <dl>
                                    <div><dt>Location</dt><dd><?= e($profile['location'] ?: 'Not available') ?></dd></div>
                                    <div><dt>Work</dt><dd><?= e($profile['work'] ?: 'Not available') ?></dd></div>
                                </dl>
                                <a class="button-link" href="profile.php?u=<?= urlencode($profile['github_username']) ?>">View profile</a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>
