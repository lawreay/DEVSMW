<?php
require __DIR__ . '/includes/bootstrap.php';

echo "=== admin_users table structure ===\n";
$result = db()->query('DESCRIBE admin_users');
$columns = $result->fetchAll();
foreach ($columns as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}
?>
