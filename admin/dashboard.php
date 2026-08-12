<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/visit.php';
require_admin();

ensure_profile_visit_table();

$q = trim($_GET['q'] ?? '');
$params = [];
$sql = 'SELECT profiles.*, COALESCE(visits.visit_count, 0) AS visit_count, visits.last_visit
        FROM profiles
        LEFT JOIN (
            SELECT profile_id, COUNT(*) AS visit_count, MAX(created_at) AS last_visit
            FROM profile_visits
            GROUP BY profile_id
        ) visits ON visits.profile_id = profiles.id';
if ($q !== '') {
    $sql .= ' WHERE github_username LIKE ? OR name LIKE ? OR work LIKE ? OR location LIKE ?';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like, $like];
}
$sql .= ' ORDER BY COALESCE(rank_private, 999999), github_username LIMIT 200';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$profiles = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= e(site_url('index.php')) ?>">DEVSMW Admin</a>
    <nav>
        <a href="<?= e(site_url('index.php')) ?>">Public site</a>
        <a href="<?= e(site_url('admin/change_password.php')) ?>">Password</a>
        <a href="<?= e(site_url('admin/logout.php')) ?>">Logout</a>
    </nav>
</header>
<main class="page">
    <?php $flash = consume_flash(); ?>
    <?php if ($flash): ?>
        <p class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></p>
    <?php endif; ?>

    <section class="admin-head">
        <div>
            <h1>Profiles</h1>
            <p>Edit data, review visibility, and refresh GitHub data in one click.</p>
            <p class="muted">GitHub token is <?= config('github.token') ? 'configured' : 'not configured' ?>. Search engine is set to <?= e(config('search.engine')) ?>.</p>
        </div>
        <div class="admin-actions">
            <form method="post" action="refresh_all_github.php">
                <?= csrf_field() ?>
                <button type="submit">Refresh All GitHub</button>
            </form>
            <form class="search-form compact" method="get">
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search profiles">
                <button type="submit">Search</button>
            </form>
        </div>
    </section>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Rank</th>
                <th>Name</th>
                <th>GitHub</th>
                <th>Location</th>
                <th>Visits</th>
                <th>Visibility</th>
                <th>Synced</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($profiles as $profile): ?>
                <tr>
                    <td><?= e((string) ($profile['rank_private'] ?: '')) ?></td>
                    <td><?= e($profile['name'] ?: '-') ?></td>
                    <td>@<?= e($profile['github_username']) ?></td>
                    <td><?= e($profile['location'] ?: '-') ?></td>
                    <td><?= e((string) ($profile['visit_count'] ?: 0)) ?></td>
                    <td><?= e($profile['visibility']) ?></td>
                    <td><?= e($profile['last_synced_at'] ?: 'Never') ?>
                        <?php if ($profile['last_visit']): ?><br><small class="muted"><?= e($profile['last_visit']) ?></small><?php endif; ?>
                    </td>
                    <td class="actions">
                        <a href="profile_edit.php?id=<?= (int) $profile['id'] ?>">Edit</a>
                        <form method="post" action="refresh_github.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $profile['id'] ?>">
                            <button type="submit">Refresh GitHub</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <section class="recent-audit">
        <h2>Recent Admin Activity</h2>
        <?php
        $logs = db()->query(
            'SELECT audit_logs.*, admin_users.username
             FROM audit_logs
             LEFT JOIN admin_users ON admin_users.id = audit_logs.admin_user_id
             ORDER BY audit_logs.created_at DESC
             LIMIT 8'
        )->fetchAll();
        ?>
        <div class="activity-list">
            <?php foreach ($logs as $log): ?>
                <p>
                    <strong><?= e($log['action']) ?></strong>
                    <span><?= e($log['entity_type']) ?><?= $log['entity_id'] ? ' #' . e((string) $log['entity_id']) : '' ?></span>
                    <small><?= e($log['username'] ?: 'system') ?>, <?= e($log['created_at']) ?></small>
                </p>
            <?php endforeach; ?>
        </div>
    </section>
</main>
</body>
</html>
