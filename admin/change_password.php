<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();
set_page_cache_control('admin');  // No caching for admin pages

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM admin_users WHERE id = ? LIMIT 1');
    $stmt->execute([current_admin_id()]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($currentPassword, $admin['password_hash'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($newPassword) < 12) {
        $error = 'New password must be at least 12 characters.';
    } elseif (!hash_equals($newPassword, $confirmPassword)) {
        $error = 'New passwords do not match.';
    } else {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = db()->prepare('UPDATE admin_users SET password_hash = ?, updated_at = NOW() WHERE id = ?');
        $update->execute([$hash, current_admin_id()]);
        audit_log('change_password', 'admin_user', current_admin_id());
        flash('Password changed.');
        redirect('dashboard.php');
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Change Password</title>
    <link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= e(site_url('admin/dashboard.php')) ?>">DEVSMW Admin</a>
    <nav><a href="<?= e(site_url('admin/dashboard.php')) ?>">Dashboard</a></nav>
<main class="page narrow-page">
    <section class="admin-head">
        <div>
            <h1>Change Password</h1>
            <p>Use a long password that is not reused anywhere else.</p>
        </div>
    </section>

    <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>

    <form method="post" class="edit-form">
        <?= csrf_field() ?>
        <label>Current Password <input name="current_password" type="password" required autocomplete="current-password"></label>
        <label>New Password <input name="new_password" type="password" required minlength="12" autocomplete="new-password"></label>
        <label>Confirm New Password <input name="confirm_password" type="password" required minlength="12" autocomplete="new-password"></label>
        <button type="submit">Update password</button>
    </form>
</main>
</body>
</html>
