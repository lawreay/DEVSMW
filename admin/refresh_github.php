<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/github.php';
require_admin();
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);

try {
    refresh_profile_from_github($id);
    $_SESSION['flash'] = 'GitHub profile refreshed.';
} catch (Throwable $e) {
    $_SESSION['flash'] = 'Refresh failed: ' . $e->getMessage();
}

redirect('dashboard.php');
