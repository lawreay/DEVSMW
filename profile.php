<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/visit.php';

$username = trim($_GET['u'] ?? '');
$stmt = db()->prepare('SELECT * FROM profiles WHERE github_username = ? AND visibility = "published"');
$stmt->execute([$username]);
$profile = $stmt->fetch();

if (!$profile) {
    http_response_code(404);
    exit('Profile not found.');
}

record_profile_visit((int) $profile['id']);
$visitCount = get_profile_visit_count((int) $profile['id']);
$lastVisit = get_profile_last_visit((int) $profile['id']);

$projects = db()->prepare('SELECT * FROM projects WHERE profile_id = ? ORDER BY stars DESC, name LIMIT 20');
$projects->execute([$profile['id']]);
$projects = $projects->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($profile['name'] ?: $profile['github_username']) ?> - <?= e(config('app_name')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= e(site_url('index.php')) ?>">DEVSMW Profiles</a>
    <nav><a href="<?= e(site_url('index.php')) ?>">Search</a></nav>
</header>

<main class="page profile-page">
    <aside class="profile-aside">
        <span class="rank"><?= $profile['rank_private'] ? '#' . e((string) $profile['rank_private']) : 'Profile' ?></span>
        <h1><?= e($profile['name'] ?: $profile['github_username']) ?></h1>
        <p class="muted">@<?= e($profile['github_username']) ?></p>
        <dl>
            <div><dt>Location</dt><dd><?= e($profile['location'] ?: 'Not publicly available') ?></dd></div>
            <div><dt>Work</dt><dd><?= e($profile['work'] ?: 'Not publicly available') ?></dd></div>
            <div><dt>Email</dt><dd><?= $profile['email'] ? '<a href="mailto:' . e($profile['email']) . '">' . e($profile['email']) . '</a>' : 'Not publicly available' ?></dd></div>
            <div><dt>Phone</dt><dd><?= e($profile['phone'] ?: 'Not publicly available') ?></dd></div>
        </dl>
        <div class="profile-meta">
            <div><strong>Profile views</strong></div>
            <div><?= (int) $visitCount ?> visit<?= $visitCount === 1 ? '' : 's' ?></div>
            <?php if ($lastVisit): ?>
                <div class="muted">Last visited <?= e(date('M j, Y H:i', strtotime($lastVisit))) ?></div>
            <?php endif; ?>
        </div>
        <div class="link-stack">
            <?php if ($profile['github_url']): ?><a href="<?= e($profile['github_url']) ?>" target="_blank" rel="noopener">GitHub</a><?php endif; ?>
            <?php if ($profile['website']): ?><a href="<?= e($profile['website']) ?>" target="_blank" rel="noopener">Website</a><?php endif; ?>
            <?php if ($profile['linkedin_url']): ?><a href="<?= e($profile['linkedin_url']) ?>" target="_blank" rel="noopener">LinkedIn</a><?php endif; ?>
        </div>
    </aside>

    <section class="profile-main">
        <?php if ($profile['markdown']): ?>
            <div class="markdown"><?= markdown_to_html($profile['markdown']) ?></div>
        <?php else: ?>
            <h2>About</h2>
            <p><?= e($profile['bio'] ?: 'No public bio available yet.') ?></p>
            <h2>What they are good at</h2>
            <p><?= nl2br(e($profile['strengths'] ?: 'No skill summary available yet.')) ?></p>
        <?php endif; ?>

        <h2>Top Projects</h2>
        <div class="project-list">
            <?php foreach ($projects as $project): ?>
                <article class="project-row">
                    <h3>
                        <?php if ($project['url']): ?>
                            <a href="<?= e($project['url']) ?>" target="_blank" rel="noopener"><?= e($project['name']) ?></a>
                        <?php else: ?>
                            <?= e($project['name']) ?>
                        <?php endif; ?>
                    </h3>
                    <p><?= e($project['description'] ?: 'No description available.') ?></p>
                    <span><?= e($project['language'] ?: 'Unknown') ?></span>
                    <span><?= (int) $project['stars'] ?> stars</span>
                    <span><?= e($project['source']) ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
</body>
</html>
