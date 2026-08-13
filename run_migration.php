<?php
require __DIR__ . '/includes/bootstrap.php';

try {
    // Drop existing tables
    echo "Dropping existing tables if they exist...\n";
    db()->exec('DROP TABLE IF EXISTS password_reset_tokens');
    db()->exec('DROP TABLE IF EXISTS email_config');
    
    $migration = file_get_contents(__DIR__ . '/database/migrations/002_add_email_tables.sql');
    
    // Split multiple statements and execute each
    $statements = array_filter(array_map('trim', explode(';', $migration)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            db()->exec($statement);
            echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "\n✅ Database migration completed successfully!\n";
    echo "Tables created:\n";
    echo "  - email_config (SMTP configuration)\n";
    echo "  - password_reset_tokens (Password reset tokens)\n";
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
