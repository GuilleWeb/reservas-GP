<?php
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/xml; charset=utf-8');

$empresa_slug = trim((string) ($_GET['empresa'] ?? ''));
$urls = [];

// Helper para convertir URL relativa a absoluta
function sitemap_make_absolute($url) {
    $url = trim((string) $url);
    if ($url === '') return '';
    // Ya es absoluta
    if (preg_match('/^https?:\/\//i', $url)) return $url;
    // Usar el helper existente
    return app_url_absolute($url);
}

// Sitemap por empresa: si no hay slug, mostrar solo la landing.
if ($empresa_slug === '') {
    $urls[] = [
        'loc' => app_url_absolute('/'),
        'priority' => '1.0',
        'changefreq' => 'daily',
    ];
} else {
    $stmt = $pdo->prepare("SELECT id, slug FROM empresas WHERE activo=1 AND slug=? LIMIT 1");
    $stmt->execute([$empresa_slug]);
    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($empresa) {
        $slug = (string) $empresa['slug'];

        // URLs principales usando pretty URLs (slug/vista)
        $urls[] = [
            'loc' => sitemap_make_absolute(view_url('vistas/public/inicio.php', $slug)),
            'priority' => '1.0',
            'changefreq' => 'daily',
        ];
        $urls[] = [
            'loc' => sitemap_make_absolute(view_url('vistas/public/ver-sedes.php', $slug)),
            'priority' => '0.8',
            'changefreq' => 'weekly',
        ];
        $urls[] = [
            'loc' => sitemap_make_absolute(view_url('vistas/public/servicios.php', $slug)),
            'priority' => '0.8',
            'changefreq' => 'weekly',
        ];

        if (plan_allows_module((int) $empresa['id'], 'citas')) {
            $urls[] = [
                'loc' => sitemap_make_absolute(view_url('vistas/public/citas.php', $slug)),
                'priority' => '0.9',
                'changefreq' => 'daily',
            ];
        }

        if (plan_allows_module((int) $empresa['id'], 'blog')) {
            // Página principal del blog
            $urls[] = [
                'loc' => sitemap_make_absolute(view_url('vistas/public/blog.php', $slug)),
                'priority' => '0.8',
                'changefreq' => 'daily',
            ];

            // Posts individuales del blog con sus slugs
            $stmtPosts = $pdo->prepare("SELECT slug, updated_at FROM blog_posts WHERE empresa_id = ? AND publicado = 1 ORDER BY publicado_at DESC");
            $stmtPosts->execute([(int) $empresa['id']]);
            $posts = $stmtPosts->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($posts as $post) {
                $post_slug = (string) ($post['slug'] ?? '');
                if ($post_slug === '') continue;

                // URL: /slug/blog/post-slug
                $postUrl = rtrim(view_url('vistas/public/blog.php', $slug), '/') . '/' . $post_slug;

                $lastmod = !empty($post['updated_at'])
                    ? gmdate('Y-m-d\TH:i:s\Z', strtotime($post['updated_at']))
                    : gmdate('Y-m-d\TH:i:s\Z');

                $urls[] = [
                    'loc' => sitemap_make_absolute($postUrl),
                    'priority' => '0.7',
                    'changefreq' => 'monthly',
                    'lastmod' => $lastmod,
                ];
            }
        }
    }
}

$urls = array_values(array_filter($urls, function($u) {
    return !empty($u['loc']);
}));

$defaultNow = gmdate('Y-m-d\TH:i:s\Z');

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach ($urls as $u) {
    $loc = trim((string) ($u['loc'] ?? ''));
    if ($loc === '') continue;

    $lastmod = $u['lastmod'] ?? $defaultNow;
    $changefreq = $u['changefreq'] ?? 'daily';
    $priority = $u['priority'] ?? '0.5';

    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($loc, ENT_XML1, 'UTF-8', false) . "</loc>\n";
    echo "    <lastmod>" . htmlspecialchars($lastmod, ENT_XML1, 'UTF-8', false) . "</lastmod>\n";
    echo "    <changefreq>" . htmlspecialchars($changefreq, ENT_XML1, 'UTF-8', false) . "</changefreq>\n";
    echo "    <priority>" . htmlspecialchars($priority, ENT_XML1, 'UTF-8', false) . "</priority>\n";
    echo "  </url>\n";
}
echo "</urlset>";
