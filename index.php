<?php
require __DIR__ . '/includes/bootstrap.php';

$q = trim($_GET['q'] ?? '');
$params = [];
$sql = 'SELECT * FROM profiles WHERE visibility = "published"';

if ($q !== '') {
    $sql .= ' AND (github_username LIKE ? OR name LIKE ? OR location LIKE ? OR work LIKE ? OR bio LIKE ? OR strengths LIKE ?)';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like, $like, $like, $like];
}

$sql .= ' ORDER BY COALESCE(rank_private, 999999), name, github_username LIMIT 100';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$profiles = $stmt->fetchAll();

$externalSearch = false;
$searchResults = [];
$searchEngine = config('search.engine');
if ($q !== '') {
    require __DIR__ . '/includes/search.php';
    if (search_is_external()) {
        $searchResults = search_results($q);
        $externalSearch = true;
    }
}

$topProfiles = db()->query(
    'SELECT * FROM profiles WHERE visibility = "published" AND rank_private IS NOT NULL ORDER BY rank_private ASC LIMIT 10'
)->fetchAll();

$top50Count = (int) db()->query(
    'SELECT COUNT(*) FROM profiles WHERE visibility = "published" AND rank_private BETWEEN 1 AND 50'
)->fetchColumn();

$techStmt = db()->prepare(
    'SELECT IFNULL(NULLIF(language, ""), "Other") AS language, COUNT(DISTINCT p.profile_id) AS profiles
     FROM projects p
     JOIN profiles pr ON pr.id = p.profile_id
     WHERE pr.visibility = "published" AND p.is_private = 0
     GROUP BY language
     ORDER BY profiles DESC
     LIMIT 6'
);
$techStmt->execute();
$techData = $techStmt->fetchAll();
$techMax = 1;
foreach ($techData as $tech) {
    $techMax = max($techMax, (int) $tech['profiles']);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(config('app_name')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= e(site_url('index.php')) ?>">DEVSMW Profiles</a>
    <nav>
        <a href="<?= e(site_url('docs/project-goal.md')) ?>">Docs</a>
    </nav>
</header>

<main class="page">
    <section class="hero-panel">
        <div class="hero-copy">
            <span class="eyebrow">Developer showcase</span>
            <h1>Malawi’s top developer profiles in one place</h1>
            <p>Explore the best local talent, see the top-ranked devs, and discover the technologies shaping the community.</p>
            <form class="search-form" method="get">
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search by name, skill, project, location...">
                <button type="submit">Search</button>
            </form>
            <div class="hero-stats">
                <div>
                    <span class="stat-value"><?= $top50Count ?>+</span>
                    <p>Top 50 ranked developers</p>
                </div>
                <div>
                    <span class="stat-value"><?= count($topProfiles) ?></span>
                    <p>Featured top 10 profiles</p>
                </div>
                <div>
                    <span class="stat-value"><?= count($techData) ?></span>
                    <p>Top technologies</p>
                </div>
            </div>
            <div class="hero-actions">
                <a class="button-link secondary" href="profiles.php">Show full list</a>
            </div>
        </div>
        <div class="hero-aside">
            <div class="feature-card">
                <h2>Tech news</h2>
                <p>Latest updates, conferences, and open-source highlights for Malawi’s developer ecosystem.</p>
                <a class="button-link secondary" href="#news">Read news</a>
            </div>
            <div class="feature-card feature-card--accent">
                <h2>Jobs</h2>
                <p>Opportunities coming soon. Sign up for alerts to see local developer jobs as they launch.</p>
                <span class="pill">Coming soon</span>
            </div>
        </div>
    </section>

    <section id="all-profiles" class="section-card">
        <div class="section-head">
            <div>
                <h2>Top 10 ranked profiles</h2>
                <p>Discover the highest-ranked published profiles and what makes them stand out.</p>
            </div>
            <a class="button-link" href="index.php">Browse all profiles</a>
        </div>
        <div class="top-profiles-grid">
            <?php foreach ($topProfiles as $profile): ?>
                <article class="profile-card top-profile-card">
                    <div class="card-top">
                        <span class="rank">#<?= e((string) $profile['rank_private']) ?></span>
                        <span class="status"><?= e($profile['consent_status']) ?></span>
                    </div>
                    <h3><?= e($profile['name'] ?: $profile['github_username']) ?></h3>
                    <p class="muted">@<?= e($profile['github_username']) ?></p>
                    <p class="profile-snippet"><?= e(mb_strimwidth($profile['bio'] ?: $profile['strengths'] ?: 'Public profile summary not available.', 0, 140, '...')) ?></p>
                    <dl>
                        <div><dt>Location</dt><dd><?= e($profile['location'] ?: 'Unknown') ?></dd></div>
                        <div><dt>Work</dt><dd><?= e($profile['work'] ?: 'Unknown') ?></dd></div>
                    </dl>
                    <a class="button-link" href="profile.php?u=<?= urlencode($profile['github_username']) ?>">View profile</a>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section-card grid-2-column">
        <div class="chart-card">
            <div class="section-head">
                <div>
                    <h2>Technologies used most</h2>
                    <p>Insights from published profiles and open-source projects in the community.</p>
                </div>
            </div>
            <div class="bar-chart">
                <?php foreach ($techData as $tech): ?>
                    <?php $ratio = round(((int) $tech['profiles'] / $techMax) * 100); ?>
                    <div class="bar-row">
                        <span class="bar-label"><?= e($tech['language']) ?></span>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= $ratio ?>%"></div>
                        </div>
                        <span class="bar-count"><?= e((string) $tech['profiles']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="news-card" id="news">
            <div class="section-head">
                <div>
                    <h2>Technology news</h2>
                    <p>Community news and highlights to follow right now.</p>
                </div>
            </div>
            <ul class="news-list">
                <li><strong>New local meetup announced</strong> — A developer event for open-source collaboration and career growth.</li>
                <li><strong>GitHub contributions rising</strong> — Malawi developers are shipping more projects and profiles daily.</li>
                <li><strong>Skills spotlight:</strong> JavaScript, Python, Laravel, and DevOps continue to lead.</li>
            </ul>
            <div class="jobs-coming">
                <h3>Jobs coming soon</h3>
                <p>We’re building a jobs board for developer roles, internships, and remote opportunities.</p>
                <a class="button-link secondary" href="#">Get notified</a>
            </div>
        </div>
    </section>

    <?php if ($q !== '' && $externalSearch): ?>
        <section class="section-card">
            <div class="section-head">
                <div>
                    <h2>External search results</h2>
                    <p>Free results from <?= e(ucfirst($searchEngine === 'duckduckgo' ? 'DuckDuckGo' : $searchEngine)) ?> for "<?= e($q) ?>".</p>
                </div>
                <div class="search-engine-badge">
                    <span><?= e(strtoupper($searchEngine)) ?> (free)</span>
                </div>
            </div>
            <?php if (count($searchResults) === 0): ?>
                <p class="muted">No external results found for this query. Try another keyword or use the local search below.</p>
            <?php else: ?>
                <div class="news-list">
                    <?php foreach ($searchResults as $result): ?>
                        <li>
                            <a href="<?= e($result['url']) ?>" target="_blank" rel="noopener noreferrer"><strong><?= e($result['name']) ?></strong></a>
                            <p><?= e($result['snippet']) ?></p>
                        </li>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($q !== ''): ?>
        <section class="section-card">
            <div class="section-head">
                <div>
                    <h2>Search results</h2>
                    <p><?= count($profiles) ?> profiles found for "<?= e($q) ?>".</p>
                </div>
            </div>
            <?php if (count($profiles) === 0): ?>
                <p class="muted">No results matched your search. Try a broader keyword like a technology, location, or role.</p>
            <?php else: ?>
                <div class="profile-grid">
                    <?php foreach ($profiles as $profile): ?>
                        <article class="profile-card">
                            <div class="card-top">
                                <span class="rank"><?= $profile['rank_private'] ? '#' . e((string) $profile['rank_private']) : 'Profile' ?></span>
                                <span class="status"><?= e($profile['consent_status']) ?></span>
                            </div>
                            <h3><?= e($profile['name'] ?: $profile['github_username']) ?></h3>
                            <p class="muted">@<?= e($profile['github_username']) ?></p>
                            <p><?= e(mb_strimwidth($profile['bio'] ?: $profile['strengths'] ?: 'No summary available yet.', 0, 140, '...')) ?></p>
                            <dl>
                                <div><dt>Location</dt><dd><?= e($profile['location'] ?: 'Unknown') ?></dd></div>
                                <div><dt>Work</dt><dd><?= e($profile['work'] ?: 'Unknown') ?></dd></div>
                            </dl>
                            <a class="button-link" href="profile.php?u=<?= urlencode($profile['github_username']) ?>">View profile</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
