<?php
require __DIR__ . '/../includes/bootstrap.php';

if (is_admin()) {
    audit_log('logout', 'admin_user', current_admin_id());
}

session_destroy();
redirect('login.php');
