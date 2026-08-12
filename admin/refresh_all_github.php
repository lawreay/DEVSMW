<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/github.php';
require_admin();
verify_csrf();

set_time_limit(0);
ignore_user_abort(true);

$profiles = db()->query('SELECT id, github_username FROM profiles WHERE visibility != "hidden" ORDER BY COALESCE(rank_private, 999999), github_username')->fetchAll();
$success = 0;
$failed = 0;

foreach ($profiles as $profile) {
    try {
        refresh_profile_from_github((int) $profile['id']);
        $success++;
        usleep(250000);
    } catch (Throwable $e) {
        $failed++;
    }
}

audit_log('refresh_all_github', 'profile', null, null, ['success' => $success, 'failed' => $failed]);
flash("GitHub refresh complete. {$success} updated, {$failed} failed.", $failed > 0 ? 'warning' : 'info');
redirect('dashboard.php');
