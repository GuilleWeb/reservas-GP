<?php
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/xml; charset=utf-8');

$empresa_slug = trim((string) ($_GET['empresa'] ?? ''));
$urls = [];

// Sitemap por empresa: si no hay slug, mostrar solo la landing.
if ($empresa_slug === '') {
    $urls[] = app_url_absolute('/');
} else {
    $stmt = $pdo->prepare("SELECT id, slug FROM empresas WHERE activo=1 AND slug=? LIMIT 1");
    $stmt->execute([$empresa_slug]);
    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($empresa) {
        $slug_q = rawurlencode((string) $empresa['slug']);
        $urls[] = app_url_absolute('vistas/public/inicio.php?empresa=' . $slug_q);
        $urls[] = app_url_absolute('vistas/public/ver-sedes.php?empresa=' . $slug_q);
        $urls[] = app_url_absolute('vistas/public/servicios.php?empresa=' . $slug_q);
        if (plan_allows_module((int) $empresa['id'], 'citas')) {
            $urls[] = app_url_absolute('vistas/public/citas.php?empresa=' . $slug_q);
        }
        if (plan_allows_module((int) $empresa['id'], 'blog')) {
            $urls[] = app_url_absolute('vistas/public/blog.php?empresa=' . $slug_q);
        }
    }
}

$urls = array_values(array_unique(array_filter($urls)));
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
