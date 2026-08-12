<?php

declare(strict_types=1);

session_start();

$config = require __DIR__ . '/../config/config.php';

function config(string $key, mixed $default = null): mixed
{
    global $config;
    $parts = explode('.', $key);
    $value = $config;
    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $db = config('db');
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $db['host'],
        $db['port'],
        $db['name'],
        $db['charset']
    );
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid security token.');
    }
}

function is_admin(): bool
{
    return !empty($_SESSION['admin_user_id']);
}

function require_admin(): void
{
    if (!is_admin()) {
        redirect('login.php');
    }
}

function current_admin_id(): ?int
{
    return isset($_SESSION['admin_user_id']) ? (int) $_SESSION['admin_user_id'] : null;
}

function find_admin_by_username(string $username): ?array
{
    $stmt = db()->prepare('SELECT * FROM admin_users WHERE username = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    return $admin ?: null;
}

function login_admin(array $admin): void
{
    session_regenerate_id(true);
    $_SESSION['admin_user_id'] = (int) $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];

    $stmt = db()->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?');
    $stmt->execute([(int) $admin['id']]);
}

function audit_log(string $action, string $entityType, ?int $entityId = null, ?array $before = null, ?array $after = null): void
{
    $stmt = db()->prepare(
        'INSERT INTO audit_logs (admin_user_id, action, entity_type, entity_id, before_json, after_json, ip_address, user_agent, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        current_admin_id(),
        $action,
        $entityType,
        $entityId,
        $before ? json_encode($before, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
        $after ? json_encode($after, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
    ]);
}

function flash(string $message, string $type = 'info'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function consume_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    if (is_string($flash)) {
        return ['message' => $flash, 'type' => 'info'];
    }

    return $flash;
}

function markdown_to_html(string $markdown): string
{
    $markdown = trim($markdown);
    if ($markdown === '') {
        return '';
    }

    $html = e($markdown);
    $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
    $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
    $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
    $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);
    $html = preg_replace('/\[(.+?)\]\((https?:\/\/[^)\s]+|mailto:[^)\s]+)\)/', '<a href="$2">$1</a>', $html);
    $html = preg_replace('/^\* (.+)$/m', '<li>$1</li>', $html);
    $html = preg_replace('/(<li>.*<\/li>)/sU', '<ul>$1</ul>', $html);
    $blocks = preg_split("/\n{2,}/", $html);

    return implode("\n", array_map(static function (string $block): string {
        if (preg_match('/^\s*<(h1|h2|h3|ul|blockquote|hr)/', $block)) {
            return $block;
        }
        return '<p>' . nl2br($block) . '</p>';
    }, $blocks));
}

function nullable_field(string $value): ?string
{
    $value = trim($value);
    return $value === '' ? null : $value;
}
