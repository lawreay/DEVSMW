<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/email.php';
require_admin();
set_page_cache_control('admin');

$config = get_smtp_config();
$testStatus = null;
$testMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $updated = update_smtp_config([
            'smtp_host' => $_POST['smtp_host'] ?? '',
            'smtp_port' => $_POST['smtp_port'] ?? 587,
            'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
            'smtp_username' => $_POST['smtp_username'] ?? '',
            'smtp_password' => $_POST['smtp_password'] ?? '',
            'from_address' => $_POST['from_address'] ?? '',
            'from_name' => $_POST['from_name'] ?? 'DEVSMW',
        ]);

        if ($updated) {
            audit_log('update_email_config', 'email_config', null, null, $_POST);
            flash('SMTP configuration saved successfully.');
            $config = get_smtp_config();
        } else {
            flash('Failed to save SMTP configuration.', 'error');
        }
        redirect('smtp_config.php');
    } elseif ($action === 'test') {
        $testEmail = trim($_POST['test_email'] ?? '');
        if (empty($testEmail)) {
            $testStatus = 'error';
            $testMessage = 'Please enter a test email address.';
        } else {
            $result = send_email(
                $testEmail,
                'DEVSMW SMTP Configuration Test',
                '<p>This is a test email from DEVSMW. If you received this, SMTP is configured correctly!</p>',
                'This is a test email from DEVSMW. If you received this, SMTP is configured correctly!'
            );

            if ($result) {
                $testStatus = 'success';
                $testMessage = "Test email sent to $testEmail. Check your inbox.";
            } else {
                $testStatus = 'error';
                $testMessage = 'Failed to send test email. Check error logs for details.';
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
    <title>SMTP Email Configuration</title>
    <link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
    <style>
        .config-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        .config-section h3 {
            margin-top: 0;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            max-width: 400px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 14px;
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .button-group button {
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            border-radius: 3px;
        }
        .btn-save {
            background: #28a745;
            color: white;
        }
        .btn-save:hover {
            background: #218838;
        }
        .btn-test {
            background: #007bff;
            color: white;
        }
        .btn-test:hover {
            background: #0056b3;
        }
        .alert {
            padding: 12px 15px;
            border-radius: 3px;
            margin-bottom: 15px;
        }
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .test-section {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 15px;
            border-radius: 3px;
            margin-top: 20px;
        }
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-indicator.configured {
            background: #28a745;
        }
        .status-indicator.not-configured {
            background: #dc3545;
        }
        .info-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 3px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= e(site_url('admin/dashboard.php')) ?>">DEVSMW Admin</a>
    <nav>
        <a href="<?= e(site_url('admin/dashboard.php')) ?>">Dashboard</a>
        <a href="<?= e(site_url('admin/logout.php')) ?>">Logout</a>
    </nav>
</header>

<main class="page">
    <section class="admin-head">
        <div>
            <h1>Email Configuration (SMTP)</h1>
            <p>Configure SMTP settings for sending password reset emails and notifications.</p>
        </div>
    </section>

    <?php if ($flash = consume_flash()): ?>
        <div class="alert <?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <?php if ($testStatus): ?>
        <div class="alert <?= e($testStatus) ?>">
            <?= e($testMessage) ?>
        </div>
    <?php endif; ?>

    <div class="info-box">
        <strong>ℹ️ SMTP Configuration Guide:</strong>
        <ul style="margin: 10px 0 0 0; padding-left: 20px;">
            <li><strong>Gmail:</strong> Host: smtp.gmail.com, Port: 587, Encryption: TLS, Username: your@gmail.com, Password: <a href="https://support.google.com/accounts/answer/185833" target="_blank">App Password</a></li>
            <li><strong>Office 365:</strong> Host: smtp.office365.com, Port: 587, Encryption: TLS</li>
            <li><strong>Custom Server:</strong> Check with your email provider for SMTP settings</li>
        </ul>
    </div>

    <form method="post" class="edit-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">

        <div class="config-section">
            <h3>SMTP Server Settings</h3>
            
            <div class="form-group">
                <label for="smtp_host">SMTP Host <span style="color: red;">*</span></label>
                <input type="text" id="smtp_host" name="smtp_host" value="<?= e($config['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com" required>
                <small>The hostname or IP address of your SMTP server</small>
            </div>

            <div class="form-group">
                <label for="smtp_port">SMTP Port <span style="color: red;">*</span></label>
                <input type="number" id="smtp_port" name="smtp_port" value="<?= e((string) ($config['smtp_port'] ?? 587)) ?>" min="1" max="65535" required>
                <small>Usually 587 (TLS) or 465 (SSL)</small>
            </div>

            <div class="form-group">
                <label for="smtp_encryption">Encryption <span style="color: red;">*</span></label>
                <select id="smtp_encryption" name="smtp_encryption" required>
                    <option value="tls" <?= ($config['smtp_encryption'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS</option>
                    <option value="ssl" <?= ($config['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                    <option value="none" <?= ($config['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' ?>>None</option>
                </select>
                <small>TLS is most common and recommended</small>
            </div>
        </div>

        <div class="config-section">
            <h3>SMTP Credentials</h3>
            
            <div class="form-group">
                <label for="smtp_username">Username <span style="color: red;">*</span></label>
                <input type="text" id="smtp_username" name="smtp_username" value="<?= e($config['smtp_username'] ?? '') ?>" placeholder="your-email@gmail.com" required>
                <small>Your email address or SMTP username</small>
            </div>

            <div class="form-group">
                <label for="smtp_password">Password <span style="color: red;">*</span></label>
                <input type="password" id="smtp_password" name="smtp_password" value="<?= e($config['smtp_password'] ?? '') ?>" placeholder="••••••••••••">
                <small>Your password or app-specific password</small>
            </div>
        </div>

        <div class="config-section">
            <h3>Email Sender Information</h3>
            
            <div class="form-group">
                <label for="from_address">From Email Address <span style="color: red;">*</span></label>
                <input type="email" id="from_address" name="from_address" value="<?= e($config['from_address'] ?? '') ?>" placeholder="noreply@devsmw.com" required>
                <small>The email address that appears as the sender</small>
            </div>

            <div class="form-group">
                <label for="from_name">From Name</label>
                <input type="text" id="from_name" name="from_name" value="<?= e($config['from_name'] ?? 'DEVSMW') ?>" placeholder="DEVSMW">
                <small>The display name for the sender</small>
            </div>
        </div>

        <div class="button-group">
            <button type="submit" class="btn-save">Save Configuration</button>
        </div>
    </form>

    <div class="test-section">
        <h3>Test SMTP Configuration</h3>
        <p>Send a test email to verify your SMTP settings are working correctly.</p>
        
        <form method="post" style="display: flex; gap: 10px; align-items: flex-end;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="test">
            <div style="flex: 1;">
                <label for="test_email" style="display: block; font-weight: bold; margin-bottom: 5px;">Test Email Address</label>
                <input type="email" id="test_email" name="test_email" placeholder="your-email@example.com" required>
            </div>
            <button type="submit" class="btn-test">Send Test Email</button>
        </form>
    </div>
</main>

</body>
</html>
