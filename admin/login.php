<?php
require __DIR__ . '/../includes/bootstrap.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $admin = find_admin_by_username($username);
    if ($admin && password_verify($password, $admin['password_hash'])) {
        login_admin($admin);
        audit_log('login', 'admin_user', (int) $admin['id']);
        redirect('dashboard.php');
    }

    audit_log('failed_login', 'admin_user', null, null, ['username' => $username]);
    $error = 'Invalid admin login.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
</head>
<body class="admin-bg">
<main class="auth-card">
    <h1>Admin Login</h1>
    <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post" class="stack-form">
        <?= csrf_field() ?>
        <label>Username <input name="username" required autocomplete="username"></label>
        <label>Password <input name="password" type="password" required autocomplete="current-password"></label>
        <button type="submit">Sign in</button>
    </form>
</main>
</body>
</html>
