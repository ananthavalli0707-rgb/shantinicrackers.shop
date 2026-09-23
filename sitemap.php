<?php
require_once __DIR__ . '/config/db.php';

header("Content-Type: application/xml; charset=utf-8");

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
$baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$baseUrl = $protocol . $domainName . $baseDir;

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Static pages
$staticPages = [
    '',
    '/products',
    '/about',
    '/contact',
    '/cart'
];

$date = date('Y-m-d');

foreach ($staticPages as $page) {
    echo "  <url>\n";
    echo "      <loc>" . htmlspecialchars($baseUrl . $page) . "</loc>\n";
    echo "      <lastmod>" . $date . "</lastmod>\n";
    echo "      <changefreq>weekly</changefreq>\n";
    echo "      <priority>" . ($page === '' ? '1.0' : '0.8') . "</priority>\n";
    echo "  </url>\n";
}

// Dynamic product pages
try {
    $stmt = $pdo->query("SELECT id FROM products ORDER BY id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  <url>\n";
        echo "      <loc>" . htmlspecialchars($baseUrl . '/product-detail?id=' . $row['id']) . "</loc>\n";
        echo "      <lastmod>" . $date . "</lastmod>\n";
        echo "      <changefreq>monthly</changefreq>\n";
        echo "      <priority>0.6</priority>\n";
        echo "  </url>\n";
    }
} catch (Exception $e) {
    // Ignore db error
}

echo '</urlset>';
