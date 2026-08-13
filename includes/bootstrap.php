<?php

declare(strict_types=1);

session_start();

/**
 * Set cache control headers for page type
 * Public pages have short TTL so search engines see fresh SEO metadata
 * Admin pages don't cache
 */
function set_page_cache_control(string $type = 'public'): void
{
    if (headers_sent()) {
        return;
    }

    match ($type) {
        'public' => header('Cache-Control: public, max-age=300, s-maxage=300'),  // 5 min for users, 5 min for proxies
        'sitemap' => header('Cache-Control: public, max-age=86400, s-maxage=86400'),  // 24 hours
        'admin' => header('Cache-Control: no-cache, no-store, must-revalidate, private, max-age=0'),
        'static' => header('Cache-Control: public, max-age=2592000, immutable'),  // 30 days, immutable
        default => header('Cache-Control: public, max-age=300'),
    };
}

load_dotenv(__DIR__ . '/../.env');

$config = require __DIR__ . '/../config/config.php';

function load_dotenv(string $filePath): void
{
    if (!file_exists($filePath) || !is_readable($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $name = trim($parts[0]);
        $value = trim($parts[1]);

        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            $value = substr($value, 1, -1);
        }

        if ($name === '') {
            continue;
        }

        if (getenv($name) === false || getenv($name) === '') {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

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

function site_url(string $path = ''): string
{
    $base = rtrim(config('app_url') ?: '/', '/');
    return $base . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return site_url(trim($path, '/'));
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

/**
 * Generate meta description tag (150-160 chars ideal)
 */
function meta_description(string $description): string
{
    $clean = strip_tags($description);
    $clean = preg_replace('/\s+/', ' ', trim($clean));
    $truncated = mb_substr($clean, 0, 160);
    return '<meta name="description" content="' . e($truncated) . '">';
}

/**
 * Generate Open Graph meta tags for social sharing
 */
function og_tags(array $data): string
{
    $defaults = [
        'title' => config('app_name'),
        'description' => 'Discover Malawi\'s top developer profiles, technologies, and projects.',
        'type' => 'website',
        'url' => site_url(),
        'image' => site_url('assets/og-image.png'),
    ];
    $data = array_merge($defaults, array_filter($data));

    $tags = [];
    foreach ($data as $key => $value) {
        if ($value) {
            $tags[] = '<meta property="og:' . e($key) . '" content="' . e($value) . '">';
        }
    }
    return implode("\n    ", $tags);
}

/**
 * Generate Twitter Card meta tags
 */
function twitter_card(array $data): string
{
    $defaults = [
        'card' => 'summary_large_image',
        'title' => config('app_name'),
        'description' => 'Discover Malawi\'s top developer profiles, technologies, and projects.',
        'image' => site_url('assets/og-image.png'),
    ];
    $data = array_merge($defaults, array_filter($data));

    $tags = [];
    foreach ($data as $key => $value) {
        if ($value) {
            $tags[] = '<meta name="twitter:' . e($key) . '" content="' . e($value) . '">';
        }
    }
    return implode("\n    ", $tags);
}

/**
 * Generate JSON-LD structured data for Organization
 */
function schema_organization(): string
{
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => config('app_name'),
        'url' => site_url(),
        'description' => 'Showcase and directory of Malawi\'s top developer talent, technologies, and projects.',
        'sameAs' => [
            'https://github.com',
        ],
        'address' => [
            '@type' => 'PostalAddress',
            'addressCountry' => 'MW',
            'addressLocality' => 'Malawi',
        ],
    ];
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate JSON-LD structured data for Person profile
 */
function schema_person(array $profile): string
{
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => $profile['name'] ?: $profile['github_username'],
        'jobTitle' => $profile['title'] ?: '',
        'description' => $profile['bio'] ?: '',
        'url' => site_url('profile.php?u=' . urlencode($profile['github_username'])),
        'image' => 'https://github.com/' . $profile['github_username'] . '.png',
    ];

    if ($profile['location']) {
        $schema['location'] = [
            '@type' => 'Place',
            'name' => $profile['location'],
        ];
    }

    if ($profile['email']) {
        $schema['email'] = $profile['email'];
    }

    if ($profile['website']) {
        $schema['sameAs'] = [$profile['website']];
    }

    if ($profile['linkedin_url']) {
        $schema['sameAs'][] = $profile['linkedin_url'];
    }

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate canonical URL meta tag
 */
function canonical_url(string $url): string
{
    return '<link rel="canonical" href="' . e($url) . '">';
}

/**
 * Format time ago (e.g., "2 hours ago", "3 days ago")
 * Useful for showing when profile was last updated
 */
function time_ago(string $dateTime): string
{
    $time = strtotime($dateTime);
    $now = time();
    $secondsAgo = $now - $time;

    if ($secondsAgo < 60) {
        return 'just now';
    }

    $minutesAgo = (int) ($secondsAgo / 60);
    if ($minutesAgo < 60) {
        return $minutesAgo === 1 ? '1 minute ago' : $minutesAgo . ' minutes ago';
    }

    $hoursAgo = (int) ($secondsAgo / 3600);
    if ($hoursAgo < 24) {
        return $hoursAgo === 1 ? '1 hour ago' : $hoursAgo . ' hours ago';
    }

    $daysAgo = (int) ($secondsAgo / 86400);
    if ($daysAgo < 30) {
        return $daysAgo === 1 ? '1 day ago' : $daysAgo . ' days ago';
    }

    $monthsAgo = (int) ($secondsAgo / 2592000);
    if ($monthsAgo < 12) {
        return $monthsAgo === 1 ? '1 month ago' : $monthsAgo . ' months ago';
    }

    $yearsAgo = (int) ($secondsAgo / 31536000);
    return $yearsAgo === 1 ? '1 year ago' : $yearsAgo . ' years ago';
}
    }

    $yearsAgo = (int) ($secondsAgo / 31536000);
    return $yearsAgo === 1 ? '1 year ago' : $yearsAgo . ' years ago';
}
