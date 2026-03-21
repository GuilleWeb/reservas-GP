<?php
header('Content-Type: application/xml; charset=utf-8');

$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? null) == 443);
$scheme = $is_https ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_name = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/sitemap.php'));
$base_path = trim(dirname($script_name), '/');
$base_path = ($base_path === '.' || $base_path === '') ? '' : '/' . $base_path;
$base = rtrim($scheme . '://' . $host . $base_path, '/');

$urls = [
    $base . '/',
    $base . '/vistas/public/login.php',
];

$slugs = ['prueba'];
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        getenv('DB_HOST') ?: '127.0.0.1',
        (int) (getenv('DB_PORT') ?: 3306),
        getenv('DB_NAME') ?: 'citas_gp'
    );
    $pdo = new PDO(
        $dsn,
        getenv('DB_USER') ?: 'root',
        getenv('DB_PASS') ?: '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    $stmt = $pdo->query("SELECT slug FROM empresas WHERE activo = 1 AND slug IS NOT NULL AND slug <> '' ORDER BY id ASC LIMIT 100");
    $db_slugs = array_map(static fn($r) => (string) $r['slug'], $stmt->fetchAll());
    if ($db_slugs) {
        $slugs = array_values(array_unique($db_slugs));
    }
} catch (Throwable $e) {
    // fallback al slug de demo
}

foreach ($slugs as $slug) {
    $slug_q = rawurlencode($slug);
    $urls[] = $base . '/vistas/public/inicio.php?empresa=' . $slug_q;
    $urls[] = $base . '/vistas/public/ver-sedes.php?empresa=' . $slug_q;
    $urls[] = $base . '/vistas/public/citas.php?empresa=' . $slug_q;
    $urls[] = $base . '/vistas/public/blog.php?empresa=' . $slug_q;
}

$urls = array_values(array_unique($urls));
$now = gmdate('Y-m-d\TH:i:s\Z');

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($u, ENT_XML1) . "</loc>\n";
    echo "    <lastmod>{$now}</lastmod>\n";
    echo "    <changefreq>daily</changefreq>\n";
    echo "    <priority>0.8</priority>\n";
    echo "  </url>\n";
}
echo "</urlset>";
