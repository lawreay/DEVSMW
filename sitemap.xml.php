<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
set_page_cache_control('sitemap');  // 24 hour cache for sitemap

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=86400');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Home page
echo '  <url>' . "\n";
echo '    <loc>' . e(site_url('index.php')) . '</loc>' . "\n";
echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
echo '    <changefreq>weekly</changefreq>' . "\n";
echo '    <priority>1.0</priority>' . "\n";
echo '  </url>' . "\n";

// Profiles directory
echo '  <url>' . "\n";
echo '    <loc>' . e(site_url('profiles.php')) . '</loc>' . "\n";
echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
echo '    <changefreq>daily</changefreq>' . "\n";
echo '    <priority>0.9</priority>' . "\n";
echo '  </url>' . "\n";

// Individual profiles
$stmt = db()->query('SELECT github_username, updated_at FROM profiles WHERE visibility = "published" ORDER BY updated_at DESC');
$profiles = $stmt->fetchAll();

foreach ($profiles as $profile) {
    $lastMod = $profile['updated_at'] ? substr($profile['updated_at'], 0, 10) : date('Y-m-d');
    echo '  <url>' . "\n";
    echo '    <loc>' . e(site_url('profile.php?u=' . urlencode($profile['github_username']))) . '</loc>' . "\n";
    echo '    <lastmod>' . e($lastMod) . '</lastmod>' . "\n";
    echo '    <changefreq>monthly</changefreq>' . "\n";
    echo '    <priority>0.8</priority>' . "\n";
    echo '  </url>' . "\n";
}

// Documentation pages
$docPages = ['project-goal.md', 'privacy-policy.md', 'terms-and-conditions.md', 'engineering-standards.md'];
foreach ($docPages as $page) {
    echo '  <url>' . "\n";
    echo '    <loc>' . e(site_url('docs/' . urlencode($page))) . '</loc>' . "\n";
    echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
    echo '    <changefreq>yearly</changefreq>' . "\n";
    echo '    <priority>0.5</priority>' . "\n";
    echo '  </url>' . "\n";
}

echo '</urlset>' . "\n";
?>
