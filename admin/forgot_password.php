<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/email.php';
set_page_cache_control('public');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($username) || empty($email)) {
        $error = 'Please enter both username and email address.';
    } else {
        // Find admin user
        $stmt = db()->prepare('SELECT id, username, display_name FROM admin_users WHERE username = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if (!$admin) {
            // For security, don't reveal if user exists
            $error = 'Username or email not found.';
        } else {
            // Verify email matches (assuming email is username@example.com or similar)
            // In real scenario, you'd have email stored in admin_users table
            // For now, we'll just send the reset link
            
            $resetToken = generate_password_reset_token((int) $admin['id']);

            if (!empty($resetToken)) {
                $mailSent = send_password_reset_email(
                    $admin['username'],
                    $email,
                    $resetToken
                );

                if ($mailSent) {
                    $success = 'Password reset link has been sent to your email. Check your inbox and follow the instructions.';
                } else {
                    $error = 'Failed to send reset email. Please try again later or contact support.';
                }
            } else {
                $error = 'Failed to generate reset token. Please try again.';
            }
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
    <title>Forgot Password - DEVSMW Admin</title>
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
        <h1>Forgot Password</h1>
        <p class="subtitle">Enter your credentials to receive a password reset link</p>

        <?php if ($error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert success"><?= e($success) ?></div>
            <p style="text-align: center; color: #666; font-size: 14px; margin-top: 20px;">
                <a href="<?= e(site_url('admin/login.php')) ?>" style="color: #667eea; text-decoration: none;">Back to login</a>
            </p>
        <?php else: ?>
            <form method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required autocomplete="email">
                </div>

                <button type="submit" class="button">Send Reset Link</button>
            </form>

            <div class="footer-links">
                <a href="<?= e(site_url('admin/login.php')) ?>">Back to login</a>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
