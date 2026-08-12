<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$q = trim($_GET['q'] ?? '');
$params = [];
$sql = 'SELECT * FROM profiles';
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
    <link rel="stylesheet" href="../assets/app.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="../index.php">DEVSMW Admin</a>
    <nav>
        <a href="../index.php">Public site</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>
<main class="page">
    <?php if (!empty($_SESSION['flash'])): ?>
        <p class="alert"><?= e($_SESSION['flash']) ?></p>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <section class="admin-head">
        <div>
            <h1>Profiles</h1>
            <p>Edit data, review visibility, and refresh GitHub data in one click.</p>
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
                    <td><?= e($profile['visibility']) ?></td>
                    <td><?= e($profile['last_synced_at'] ?: 'Never') ?></td>
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
</main>
</body>
</html>
