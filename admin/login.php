<?php
require __DIR__ . '/../includes/bootstrap.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (hash_equals(config('admin.username'), $username) && hash_equals(config('admin.password'), $password)) {
        $_SESSION['admin_logged_in'] = true;
        redirect('dashboard.php');
    }

    $error = 'Invalid admin login.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../assets/app.css">
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
