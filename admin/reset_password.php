<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/email.php';
set_page_cache_control('public');

$token = trim($_GET['token'] ?? '');
$error = '';
$success = '';

$adminData = null;
if (!empty($token)) {
    $adminData = verify_password_reset_token($token);
    if (!$adminData) {
        $error = 'Password reset link is invalid or has expired.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($token)) {
    verify_csrf();

    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newPassword)) {
        $error = 'Password cannot be empty.';
    } elseif (strlen($newPassword) < 12) {
        $error = 'Password must be at least 12 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (!$adminData) {
        $error = 'Invalid or expired reset token.';
    } else {
        // Update password
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = db()->prepare('UPDATE admin_users SET password_hash = ?, updated_at = NOW() WHERE id = ?');
        $updateSuccess = $stmt->execute([$passwordHash, (int) $adminData['admin_user_id']]);

        if ($updateSuccess) {
            // Mark token as used
            mark_reset_token_used($token);
            audit_log('password_reset', 'admin_user', (int) $adminData['admin_user_id']);
            $success = 'Password has been reset successfully. You can now log in with your new password.';
            $adminData = null; // Clear form
        } else {
            $error = 'Failed to update password. Please try again.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Reset Password - DEVSMW Admin</title>
    <link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .auth-card {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 100%;
        }
        .auth-card h1 {
            text-align: center;
            margin-bottom: 10px;
            color: #333;
            font-size: 28px;
        }
        .auth-card .subtitle {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }
        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .button {
            width: 100%;
            padding: 10px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
        }
        .button:hover {
            background: #5568d3;
        }
        .footer-links {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        .footer-links a {
            color: #667eea;
            text-decoration: none;
        }
        .footer-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="auth-bg">
<div class="auth-container">
    <div class="auth-card">
        <h1>Reset Password</h1>
        <p class="subtitle">Enter your new password below</p>

        <?php if ($error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert success"><?= e($success) ?></div>
            <div class="footer-links">
                <a href="<?= e(site_url('admin/login.php')) ?>">Go to login</a>
            </div>
        <?php elseif ($adminData): ?>
            <form method="post">
                <?= csrf_field() ?>
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" value="<?= e($adminData['username']) ?>" disabled>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required autocomplete="new-password">
                    <small>Minimum 12 characters. Use a mix of uppercase, lowercase, numbers, and symbols for security.</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                </div>

                <button type="submit" class="button">Reset Password</button>
            </form>

            <div class="footer-links">
                <a href="<?= e(site_url('admin/login.php')) ?>">Back to login</a>
            </div>
        <?php else: ?>
            <div class="alert error">
                <?php if (empty($token)): ?>
                    No reset token provided. <a href="<?= e(site_url('admin/forgot_password.php')) ?>">Request a password reset</a>
                <?php else: ?>
                    Invalid or expired password reset link. <a href="<?= e(site_url('admin/forgot_password.php')) ?>">Request a new reset link</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
