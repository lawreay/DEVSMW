<?php
require __DIR__ . '/../includes/bootstrap.php';
set_page_cache_control('admin');  // No caching for admin pages

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
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login</title>
    <link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
    <style>
        .auth-card {
            max-width: 400px;
        }
        .auth-links {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        .auth-links a {
            color: #007bff;
            text-decoration: none;
        }
        .auth-links a:hover {
            text-decoration: underline;
        }
        .auth-links span {
            color: #666;
            margin: 0 5px;
        }
    </style>
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
    <div class="auth-links">
        <a href="<?= e(site_url('admin/forgot_password.php')) ?>">Forgot password?</a>
    </div>
</main>
</body>
</html>
