<?php

declare(strict_types=1);

function ensure_profile_visit_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    db()->exec(
        'CREATE TABLE IF NOT EXISTS profile_visits (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            profile_id INT UNSIGNED NOT NULL,
            ip_address VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            referer VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_profile_id (profile_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function record_profile_visit(int $profileId): void
{
    ensure_profile_visit_table();

    $stmt = db()->prepare(
        'INSERT INTO profile_visits (profile_id, ip_address, user_agent, referer) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([
        $profileId,
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        substr($_SERVER['HTTP_REFERER'] ?? '', 0, 255),
    ]);
}

function get_profile_visit_count(int $profileId): int
{
    ensure_profile_visit_table();

    $stmt = db()->prepare('SELECT COUNT(*) FROM profile_visits WHERE profile_id = ?');
    $stmt->execute([$profileId]);
    return (int) $stmt->fetchColumn();
}

function get_profile_last_visit(int $profileId): ?string
{
    ensure_profile_visit_table();

    $stmt = db()->prepare('SELECT created_at FROM profile_visits WHERE profile_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$profileId]);
    $value = $stmt->fetchColumn();

    return $value !== false ? (string) $value : null;
}

function get_profile_visit_summary(int $profileId, int $limit = 5): array
{
    ensure_profile_visit_table();

    $stmt = db()->prepare(
        'SELECT ip_address, user_agent, referer, created_at
         FROM profile_visits
         WHERE profile_id = ?
         ORDER BY created_at DESC
         LIMIT ?'
    );
    $stmt->bindValue(1, $profileId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
