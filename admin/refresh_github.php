<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/github.php';
require_admin();
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);

try {
    refresh_profile_from_github($id);
    audit_log('refresh_github', 'profile', $id);
    flash('GitHub profile refreshed.');
} catch (Throwable $e) {
    flash('Refresh failed: ' . $e->getMessage(), 'error');
}

redirect('dashboard.php');
