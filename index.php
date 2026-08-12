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
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(config('app_name')) ?></title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="index.php">DEVSMW Profiles</a>
    <nav>
        <a href="docs/project-goal.md">Docs</a>
        <a href="admin/login.php">Admin</a>
    </nav>
</header>

<main class="page">
    <section class="search-panel">
        <h1>Malawi Developer Profiles</h1>
        <p>Search public developer profiles, projects, skills, locations, and work details.</p>
        <form class="search-form" method="get">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search by name, skill, project, location...">
            <button type="submit">Search</button>
        </form>
    </section>

    <section class="result-meta">
        <strong><?= count($profiles) ?></strong> profiles shown<?= $q !== '' ? ' for "' . e($q) . '"' : '' ?>.
    </section>

    <section class="profile-grid">
        <?php foreach ($profiles as $profile): ?>
            <article class="profile-card">
                <div class="card-top">
                    <span class="rank"><?= $profile['rank_private'] ? '#' . e((string) $profile['rank_private']) : 'Profile' ?></span>
                    <span class="status"><?= e($profile['consent_status']) ?></span>
                </div>
                <h2><?= e($profile['name'] ?: $profile['github_username']) ?></h2>
                <p class="muted">@<?= e($profile['github_username']) ?></p>
                <p><?= e(mb_strimwidth($profile['bio'] ?: $profile['strengths'] ?: 'No summary available yet.', 0, 170, '...')) ?></p>
                <dl>
                    <div><dt>Location</dt><dd><?= e($profile['location'] ?: 'Not available') ?></dd></div>
                    <div><dt>Work</dt><dd><?= e($profile['work'] ?: 'Not available') ?></dd></div>
                </dl>
                <a class="button-link" href="profile.php?u=<?= urlencode($profile['github_username']) ?>">View profile</a>
            </article>
        <?php endforeach; ?>
    </section>
</main>
</body>
</html>
