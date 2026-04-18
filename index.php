<?php
// Landing desacoplada (sin bootstrap/topbar/footer compartidos).
require_once __DIR__ . '/conexion.php';
$script_name = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$base_path = trim(dirname($script_name), '/');
$base_path = ($base_path === '.' || $base_path === '') ? '' : '/' . $base_path;

$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
  || (($_SERVER['SERVER_PORT'] ?? null) == 443);
$scheme = $is_https ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = rtrim($scheme . '://' . $host, '/');
$path_prefix = trim((string) $base_path, '/');
if ($path_prefix !== '') {
  $base .= '/' . $path_prefix;
}
$join_url = static function (string $baseUrl, string $path): string {
  return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
};

$site_name = 'Reservas GP';
$title = 'Reservas GP | Sistema de citas online para clínicas, salones y negocios de servicios';
$description = 'Sistema de reservas online multiempresa. Gestiona citas, sucursales, servicios, equipo y clientes desde un solo panel moderno. Prueba gratis.';
$keywords = 'sistema de citas online, agenda online, software para clínicas, reservas web, gestión de citas, reservas en línea Guatemala, agenda para salones, agenda para negocios';

$canonical = rtrim($base, '/') . '/';
$logo_rel = $join_url($base, 'assets/logo.avif');
$logo_abs = rtrim($base, '/') . '/assets/logo.avif';

$login_url   = $join_url($base, 'vistas/public/login.php');
$demo_url    = $join_url($base, 'prueba/inicio');
$robots_url  = $join_url($base, 'robots.txt');
$sitemap_url = $join_url($base, 'sitemap.php');
$citas_url   = $join_url($base, 'prueba/citas');

// ── Planes desde DB ──────────────────────────────────────────────────────────
$plans = [];
try {
  $pdo_lp = $pdo;
  $stmt  = $pdo_lp->query('SELECT id, nombre, descripcion, max_sucursales, max_empleados, max_servicios, max_clientes, COALESCE(precio_mensual, precio, 0) AS precio, COALESCE(precio_anual, 0) AS precio_anual, modulos_json FROM planes WHERE activo = 1 ORDER BY COALESCE(precio_mensual, precio, 0) ASC LIMIT 6');
  $plans = $stmt->fetchAll() ?: [];
} catch (Throwable $e) {
  try {
    // Fallback para esquemas antiguos sin precio_mensual.
    $stmt  = $pdo->query('SELECT id, nombre, descripcion, max_sucursales, max_empleados, max_servicios, max_clientes, COALESCE(precio, 0) AS precio, COALESCE(precio_anual, 0) AS precio_anual, modulos_json FROM planes WHERE activo = 1 ORDER BY COALESCE(precio, 0) ASC LIMIT 6');
    $plans = $stmt->fetchAll() ?: [];
  } catch (Throwable $e2) {
    $plans = [];
  }
}

// Calcular precio anual si la columna no existe (fallback: -15 %)
foreach ($plans as &$p) {
  if (empty($p['precio_anual']) && (float)$p['precio'] > 0) {
    $p['precio_anual'] = round(((float)$p['precio']) * 12 * 0.85, 2);
  }
}
unset($p);

// ── Structured Data ───────────────────────────────────────────────────────────
$software_schema = [
  '@context' => 'https://schema.org', '@type' => 'SoftwareApplication',
  'name' => $site_name, 'applicationCategory' => 'BusinessApplication',
  'operatingSystem' => 'Web', 'url' => $canonical, 'description' => $description,
  'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'GTQ'],
];
$org_schema = [
  '@context' => 'https://schema.org', '@type' => 'Organization',
  'name' => $site_name, 'url' => $canonical, 'logo' => $logo_abs,
];
$faq_schema_items = [
  ['q' => '¿Puedo usar mi propia marca y colores?',        'a' => 'Sí. Puedes personalizar tu página pública con tu logo, color primario corporativo y datos de la empresa desde el panel de administración.'],
  ['q' => '¿Cómo pueden agendar mis clientes?',            'a' => 'Cada empresa recibe una URL propia. Tus clientes entran desde cualquier navegador, eligen sede, servicio, especialista, fecha y hora en minutos.'],
  ['q' => '¿Puedo gestionar varias sucursales?',           'a' => 'Sí. Puedes crear múltiples sedes, con horarios y equipos independientes. Un solo panel para todo.'],
  ['q' => '¿La información está segura?',                  'a' => 'Totalmente. Cada empresa está aislada por identificadores únicos. Los datos de tus clientes y citas están encriptados y protegidos.'],
  ['q' => '¿Puedo cancelar o cambiar de plan cuando quiera?', 'a' => 'Sí. No hay plazos forzosos. Puedes actualizar, bajar o cancelar tu plan en cualquier momento desde ajustes.'],
];
$faq_schema = [
  '@context' => 'https://schema.org', '@type' => 'FAQPage',
  'mainEntity' => array_map(fn($f) => [
    '@type' => 'Question', 'name' => $f['q'],
    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
  ], $faq_schema_items),
];
?>
<!doctype html>
<html lang="es-GT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <!-- SEO primario -->
  <title><?= htmlspecialchars($title) ?></title>
  <meta name="description" content="<?= htmlspecialchars($description) ?>">
  <meta name="keywords"    content="<?= htmlspecialchars($keywords) ?>">
  <meta name="author"      content="<?= htmlspecialchars($site_name) ?>">
  <meta name="robots"      content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
  <link rel="canonical"    href="<?= htmlspecialchars($canonical) ?>">

  <!-- Open Graph -->
  <meta property="og:type"        content="website">
  <meta property="og:url"         content="<?= htmlspecialchars($canonical) ?>">
  <meta property="og:title"       content="<?= htmlspecialchars($title) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($description) ?>">
  <meta property="og:image"       content="<?= htmlspecialchars($logo_abs) ?>">
  <meta property="og:locale"      content="es_GT">

  <!-- Twitter Card -->
  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:title"       content="<?= htmlspecialchars($title) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($description) ?>">
  <meta name="twitter:image"       content="<?= htmlspecialchars($logo_abs) ?>">

  <!-- Preconnects -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <!-- Fuentes: DM Sans (body) + Clash Display (display) vía Google -->
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,700;0,9..40,800;1,9..40,400&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Lucide icons -->
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>

  <!-- Structured Data -->
  <script type="application/ld+json"><?= json_encode($software_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
  <script type="application/ld+json"><?= json_encode($org_schema,      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
  <script type="application/ld+json"><?= json_encode($faq_schema,      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

  <style>
    /* ── Tokens ──────────────────────────────────────────────────────────── */
    :root {
      --brand:      #0d9488;
      --brand-mid:  #14b8a6;
      --brand-dark: #0f766e;
      --brand-glow: rgba(13,148,136,.18);
      --ink:        #0b1120;
      --ink-soft:   #374151;
      --muted:      #64748b;
      --surface:    #ffffff;
      --surface-2:  #f8fafc;
      --border:     #e2e8f0;
    }

    /* ── Reset / Base ────────────────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; font-size: 16px; }
    body {
      font-family: 'DM Sans', sans-serif;
      color: var(--ink);
      background: #fff;
      overflow-x: hidden;
    }
    h1, h2, h3, h4, h5 {
      font-family: 'Space Grotesk', sans-serif;
      letter-spacing: -.025em;
      line-height: 1.15;
    }

    /* ── Noise overlay ───────────────────────────────────────────────────── */
    body::before {
      content: '';
      position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.04'/%3E%3C/svg%3E");
      background-size: 200px 200px;
      opacity: .45;
    }

    /* ── Glass ───────────────────────────────────────────────────────────── */
    .glass {
      background: rgba(255,255,255,.82);
      backdrop-filter: blur(16px) saturate(180%);
      -webkit-backdrop-filter: blur(16px) saturate(180%);
    }

    /* ── Scroll reveal ───────────────────────────────────────────────────── */
    .reveal {
      opacity: 0;
      transform: translateY(36px);
      transition: opacity .75s cubic-bezier(.16,1,.3,1), transform .75s cubic-bezier(.16,1,.3,1);
    }
    .reveal.visible { opacity: 1; transform: translateY(0); }
    .reveal-left  { opacity: 0; transform: translateX(-40px); transition: opacity .75s cubic-bezier(.16,1,.3,1), transform .75s cubic-bezier(.16,1,.3,1); }
    .reveal-right { opacity: 0; transform: translateX(40px);  transition: opacity .75s cubic-bezier(.16,1,.3,1), transform .75s cubic-bezier(.16,1,.3,1); }
    .reveal-left.visible, .reveal-right.visible { opacity: 1; transform: translateX(0); }
    .delay-1 { transition-delay: .08s; }
    .delay-2 { transition-delay: .16s; }
    .delay-3 { transition-delay: .24s; }
    .delay-4 { transition-delay: .32s; }
    .delay-5 { transition-delay: .40s; }

    /* ── Float animation ─────────────────────────────────────────────────── */
    @keyframes floatY { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-14px)} }
    .float-anim { animation: floatY 7s ease-in-out infinite; }

    /* ── Gradient text ───────────────────────────────────────────────────── */
    .grad-text {
      background: linear-gradient(135deg, var(--brand) 0%, #06b6d4 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    /* ── Gradient mesh bg ────────────────────────────────────────────────── */
    .mesh-bg {
      background:
        radial-gradient(ellipse 70% 55% at 10% -5%, rgba(45,212,191,.18) 0%, transparent 60%),
        radial-gradient(ellipse 50% 40% at 90% 10%, rgba(6,182,212,.12) 0%, transparent 55%),
        #ffffff;
    }

    /* ── Header ──────────────────────────────────────────────────────────── */
    #site-header {
      position: sticky; top: 0; z-index: 100;
      border-bottom: 1px solid rgba(14,165,233,.12);
      transition: box-shadow .3s;
    }
    #site-header.scrolled { box-shadow: 0 4px 30px rgba(0,0,0,.06); }

    /* ── Pill badge ──────────────────────────────────────────────────────── */
    .badge-pill {
      display: inline-flex; align-items: center; gap: .45rem;
      font-size: .68rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase;
      padding: .4rem .9rem; border-radius: 9999px;
      background: rgba(13,148,136,.08); border: 1px solid rgba(13,148,136,.22);
      color: var(--brand-dark);
    }
    .pulse-dot {
      width: 7px; height: 7px; border-radius: 50%; background: var(--brand);
      position: relative;
    }
    .pulse-dot::before {
      content:''; position:absolute; inset:-3px; border-radius:50%;
      background: var(--brand-mid); opacity:.4;
      animation: ping 1.4s cubic-bezier(0,0,.2,1) infinite;
    }
    @keyframes ping { 0%{transform:scale(1);opacity:.6} 100%{transform:scale(2.2);opacity:0} }

    /* ── Hero CTA buttons ────────────────────────────────────────────────── */
    .btn-primary {
      display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
      padding: .85rem 2rem; border-radius: .875rem; font-weight: 800; font-size: .93rem;
      background: var(--brand); color: #fff;
      box-shadow: 0 8px 30px var(--brand-glow);
      transition: background .2s, transform .15s, box-shadow .2s;
    }
    .btn-primary:hover { background: var(--brand-dark); transform: translateY(-2px); box-shadow: 0 14px 36px var(--brand-glow); }
    .btn-ghost {
      display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
      padding: .85rem 2rem; border-radius: .875rem; font-weight: 700; font-size: .93rem;
      border: 2px solid var(--border); color: var(--ink-soft); background: transparent;
      transition: border-color .2s, color .2s, transform .15s;
    }
    .btn-ghost:hover { border-color: var(--brand); color: var(--brand); transform: translateY(-2px); }

    /* ── Mini calendar (Hero) ────────────────────────────────────────────── */
    #hero-calendar {
      background: #fff;
      border-radius: 1.5rem;
      box-shadow: 0 24px 64px rgba(13,148,136,.14), 0 4px 16px rgba(0,0,0,.06);
      overflow: hidden;
      width: 100%;
      max-width: 380px;
      font-family: 'DM Sans', sans-serif;
    }
    .hc-header {
      background: linear-gradient(135deg, var(--brand) 0%, #06b6d4 100%);
      padding: 1.1rem 1.4rem;
      display: flex; align-items: center; justify-content: space-between;
      color: #fff;
    }
    .hc-nav { background: rgba(255,255,255,.2); border: none; border-radius: .5rem; width:28px; height:28px; cursor:pointer; display:grid; place-items:center; color:#fff; transition: background .2s; }
    .hc-nav:hover { background: rgba(255,255,255,.35); }
    .hc-body { padding: 1rem 1.2rem 1.4rem; }
    .hc-grid { display: grid; grid-template-columns: repeat(7,1fr); gap:4px; }
    .hc-day-label { text-align:center; font-size:.62rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color: var(--muted); padding-bottom:.4rem; }
    .hc-day {
      text-align:center; border-radius:.6rem; padding:.45rem .1rem;
      font-size: .78rem; font-weight: 600; cursor:pointer;
      transition: background .18s, color .18s, transform .15s;
      color: var(--ink);
    }
    .hc-day:hover { background: rgba(13,148,136,.08); color: var(--brand); transform: scale(1.12); }
    .hc-day.today { background: rgba(13,148,136,.1); color: var(--brand); font-weight:800; }
    .hc-day.selected { background: var(--brand); color:#fff !important; font-weight:800; box-shadow:0 4px 14px var(--brand-glow); }
    .hc-day.other-month { color: #cbd5e1; }
    .hc-day.disabled { color:#e2e8f0; pointer-events:none; }
    .hc-time-strip { display:flex; gap:6px; flex-wrap:wrap; padding: .8rem 1.2rem 1rem; border-top:1px solid #f1f5f9; }
    .hc-time { padding:.32rem .7rem; border-radius:.5rem; font-size:.72rem; font-weight:700; background:#f8fafc; border:1px solid #e2e8f0; color:var(--ink-soft); cursor:pointer; transition: all .18s; }
    .hc-time:hover { border-color:var(--brand); color:var(--brand); background:rgba(13,148,136,.06); }
    .hc-time.selected { background:var(--brand); color:#fff; border-color:var(--brand); }
    .hc-step-label { font-size:.6rem; font-weight:900; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); padding: .55rem 1.2rem .25rem; }

    /* ── Feature cards ───────────────────────────────────────────────────── */
    .feat-card {
      background: #fff; border: 1px solid var(--border);
      border-radius: 1.25rem; padding: 1.75rem;
      transition: transform .25s, box-shadow .25s, border-color .25s;
      position: relative; overflow: hidden;
    }
    .feat-card::after {
      content:''; position:absolute; inset:0; border-radius:inherit;
      background: radial-gradient(circle at var(--mx,50%) var(--my,50%), rgba(13,148,136,.07) 0%, transparent 65%);
      opacity:0; transition:opacity .3s;
    }
    .feat-card:hover::after { opacity:1; }
    .feat-card:hover { transform:translateY(-5px); box-shadow:0 20px 50px rgba(13,148,136,.1); border-color: rgba(13,148,136,.25); }
    .feat-icon { width:48px; height:48px; border-radius:14px; background:rgba(13,148,136,.1); display:grid; place-items:center; color:var(--brand); margin-bottom:1.1rem; }

    /* ── Stats ticker ────────────────────────────────────────────────────── */
    .stat-pill { text-align:center; }
    .stat-number { font-family:'Space Grotesk',sans-serif; font-size:2.8rem; font-weight:800; letter-spacing:-.04em; color:var(--ink); line-height:1; }
    .stat-label  { font-size:.8rem; color:var(--muted); margin-top:.3rem; font-weight:500; }

    /* ── Dark section ────────────────────────────────────────────────────── */
    .dark-section {
      background: #09111f;
      color: #e2e8f0;
      border-radius: 2rem;
      overflow: hidden;
      position: relative;
    }
    .dark-section::before {
      content:''; position:absolute; top:-40%; right:-10%; width:600px; height:600px;
      background: radial-gradient(circle, rgba(13,148,136,.18) 0%, transparent 65%);
      pointer-events:none;
    }

    /* ── Feature list ────────────────────────────────────────────────────── */
    .feat-list-item { display:flex; gap:.7rem; align-items:flex-start; margin-bottom:.9rem; }
    .feat-check { flex-shrink:0; width:20px; height:20px; border-radius:6px; background:rgba(13,148,136,.15); display:grid; place-items:center; margin-top:.15rem; }

    /* ── Roles showcase ──────────────────────────────────────────────────── */
    .role-tab {
      padding:.5rem 1.2rem; border-radius:.75rem; font-size:.82rem; font-weight:700; cursor:pointer;
      border:2px solid transparent; transition: all .2s; color: var(--muted);
    }
    .role-tab.active { border-color:var(--brand); color:var(--brand); background:rgba(13,148,136,.06); }
    .role-panel { display:none; }
    .role-panel.active { display:block; }

    /* ── Plans ───────────────────────────────────────────────────────────── */
    .plan-card {
      background:#fff; border:2px solid var(--border); border-radius:1.5rem; padding:2rem;
      position:relative; display:flex; flex-direction:column;
      transition: transform .25s, box-shadow .25s;
    }
    .plan-card:hover { transform:translateY(-5px); box-shadow:0 24px 50px rgba(0,0,0,.08); }
    .plan-card.featured { border-color:var(--brand); box-shadow:0 8px 40px var(--brand-glow); }
    .plan-card.featured:hover { box-shadow:0 24px 60px var(--brand-glow); }
    .plan-badge { position:absolute; top:-1px; left:50%; transform:translateX(-50%) translateY(-50%); background:var(--brand); color:#fff; font-size:.65rem; font-weight:900; letter-spacing:.1em; text-transform:uppercase; padding:.3rem 1rem; border-radius:9999px; white-space:nowrap; }
    .plan-price-val { font-family:'Space Grotesk',sans-serif; font-size:2.6rem; font-weight:800; letter-spacing:-.04em; }
    .plan-cta-primary { display:block; width:100%; text-align:center; padding:.9rem; border-radius:1rem; font-weight:800; background:var(--brand); color:#fff; margin-top:auto; transition: background .2s, transform .15s; }
    .plan-cta-primary:hover { background:var(--brand-dark); transform:translateY(-1px); }
    .plan-cta-secondary { display:block; width:100%; text-align:center; padding:.9rem; border-radius:1rem; font-weight:700; border:2px solid var(--border); color:var(--ink); margin-top:auto; transition: border-color .2s, color .2s; }
    .plan-cta-secondary:hover { border-color:var(--brand); color:var(--brand); }
    .plan-feat { display:flex; gap:.55rem; align-items:center; font-size:.85rem; color:var(--ink-soft); padding:.35rem 0; }

    /* ── Toggle switch ───────────────────────────────────────────────────── */
    .period-toggle { display:inline-flex; background:#f1f5f9; border-radius:9999px; padding:3px; gap:2px; }
    .period-btn { padding:.45rem 1.3rem; border-radius:9999px; font-size:.8rem; font-weight:700; cursor:pointer; transition: all .2s; color:var(--muted); border:none; background:transparent; }
    .period-btn.active { background:#fff; color:var(--ink); box-shadow:0 2px 8px rgba(0,0,0,.1); }

    /* ── FAQ ─────────────────────────────────────────────────────────────── */
    details.faq-item { border-radius:1rem; border:1px solid var(--border); overflow:hidden; transition: box-shadow .2s; }
    details.faq-item[open] { box-shadow:0 6px 24px rgba(13,148,136,.08); border-color:rgba(13,148,136,.3); }
    details.faq-item summary { cursor:pointer; display:flex; justify-content:space-between; align-items:center; padding:1.15rem 1.4rem; font-weight:700; font-size:.95rem; list-style:none; user-select:none; }
    details.faq-item summary::-webkit-details-marker { display:none; }
    details.faq-item summary .faq-icon { transition: transform .3s; flex-shrink:0; color:var(--brand); }
    details.faq-item[open] summary .faq-icon { transform:rotate(45deg); }
    details.faq-item .faq-body { padding:0 1.4rem 1.2rem; font-size:.9rem; color:var(--muted); line-height:1.7; }

    /* ── Footer ──────────────────────────────────────────────────────────── */
    .site-footer { background:#09111f; color:#94a3b8; }
    .site-footer a { transition:color .2s; }
    .site-footer a:hover { color:#5eead4; }

    /* ── Scroll-progress bar ─────────────────────────────────────────────── */
    #progress-bar { position:fixed; top:0; left:0; width:0%; height:3px; background:linear-gradient(90deg,var(--brand),#06b6d4); z-index:9999; transition:width .1s linear; }

    /* ── Floating CTA ────────────────────────────────────────────────────── */
    #float-cta {
      position:fixed; bottom:1.5rem; right:1.5rem; z-index:500;
      opacity:0; transform:translateY(20px) scale(.9);
      transition:opacity .35s, transform .35s;
      pointer-events:none;
    }
    #float-cta.show { opacity:1; transform:translateY(0) scale(1); pointer-events:auto; }

    /* ── Counter ─────────────────────────────────────────────────────────── */
    [data-counter] { will-change: contents; }

    /* ── Horizontal marquee ──────────────────────────────────────────────── */
    .marquee-wrap { overflow:hidden; }
    .marquee-track { display:flex; gap:2.5rem; width:max-content; animation:marquee 28s linear infinite; }
    @keyframes marquee { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }
    .marquee-item { display:inline-flex; align-items:center; gap:.65rem; font-size:.8rem; font-weight:700; color:var(--muted); white-space:nowrap; padding:.5rem 1.2rem; border-radius:9999px; border:1px solid var(--border); background:#fff; }
    .marquee-logo-item { display:inline-flex; align-items:center; justify-content:center; padding:.5rem 1.5rem; border-radius:1rem; background:#fff; border:1px solid var(--border); filter:grayscale(100%); opacity:.7; transition:all .3s ease; }
    .marquee-logo-item:hover { filter:grayscale(0%); opacity:1; transform:scale(1.05); box-shadow:0 4px 20px rgba(13,148,136,.12); }
  </style>
</head>

<body class="mesh-bg">

  <!-- Progress bar -->
  <div id="progress-bar" role="progressbar" aria-hidden="true"></div>

  <!-- ── HEADER ──────────────────────────────────────────────────────────── -->
  <header id="site-header" class="glass">
    <div class="max-w-7xl mx-auto px-5 h-16 flex items-center justify-between">
      <a href="<?= htmlspecialchars($canonical) ?>" class="flex items-center gap-2.5 group" aria-label="<?= htmlspecialchars($site_name) ?>">
        <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-teal-500 to-cyan-500 flex items-center justify-center text-white font-black text-sm shadow-md shadow-teal-500/30 transition-transform group-hover:rotate-6 group-hover:scale-105">GP</div>
        <span class="font-extrabold text-base text-slate-900 tracking-tight" style="font-family:'Space Grotesk',sans-serif"><?= htmlspecialchars($site_name) ?></span>
      </a>
      <nav class="hidden md:flex items-center gap-7 text-sm font-semibold text-slate-500" aria-label="Navegación principal">
        <a href="#beneficios"  class="hover:text-teal-600 transition-colors">Beneficios</a>
        <a href="#como-funciona" class="hover:text-teal-600 transition-colors">Cómo funciona</a>
        <a href="#planes"      class="hover:text-teal-600 transition-colors">Planes</a>
        <a href="#faq"         class="hover:text-teal-600 transition-colors">FAQ</a>
      </nav>
      <div class="flex items-center gap-3">
        <a href="<?= htmlspecialchars($demo_url) ?>" class="hidden sm:inline-flex items-center gap-1.5 text-sm font-semibold text-teal-700 hover:text-teal-900 transition-colors">
          <i data-lucide="play-circle" class="w-4 h-4"></i> Demo
        </a>
        <a href="<?= htmlspecialchars($login_url) ?>" class="btn-primary py-2 px-5 text-sm">Acceder</a>
      </div>
    </div>
  </header>

  <main>

    <!-- ── HERO ──────────────────────────────────────────────────────────── -->
    <section class="max-w-7xl mx-auto px-5 pt-20 pb-28 grid lg:grid-cols-2 gap-14 items-center" aria-labelledby="hero-h1">

      <!-- Left copy -->
      <div>
        <div class="badge-pill reveal mb-7">
          <span class="pulse-dot"></span>
          Sistema de reservas inteligente
        </div>

        <h1 id="hero-h1" class="reveal delay-1 text-5xl sm:text-6xl font-extrabold leading-[1.08] text-slate-900">
          Agenda online que <span class="grad-text">potencia tu negocio.</span>
        </h1>

        <p class="reveal delay-2 mt-6 text-lg leading-relaxed text-slate-500 max-w-lg">
          Centraliza sucursales, servicios y equipo. Profesionaliza la experiencia de reserva de tus clientes desde un solo panel.
        </p>

        <div class="reveal delay-3 mt-10 flex flex-wrap gap-4">
          <a href="#planes"                          class="btn-primary">Ver planes</a>
          <a href="<?= htmlspecialchars($demo_url) ?>" class="btn-ghost">
            <i data-lucide="play" class="w-4 h-4"></i> Demo en vivo
          </a>
        </div>
      </div>

      <!-- Right: Interactive mini-calendar -->
      <div class="reveal-right delay-2 flex justify-center lg:justify-end">
        <div class="relative">
          <div class="float-anim absolute -top-10 -right-8 w-56 h-56 bg-teal-300 rounded-full pointer-events-none"></div>

          <div id="hero-calendar" class="relative z-10">
            <!-- Calendar header -->
            <div class="hc-header">
              <button class="hc-nav" id="hc-prev" aria-label="Mes anterior">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="15 18 9 12 15 6"/></svg>
              </button>
              <div>
                <div id="hc-month-label" class="text-sm font-bold text-center"></div>
                <div class="text-[10px] text-teal-100 text-center mt-0.5 font-semibold tracking-wide">Selecciona una fecha</div>
              </div>
              <button class="hc-nav" id="hc-next" aria-label="Mes siguiente">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="9 18 15 12 9 6"/></svg>
              </button>
            </div>

            <!-- Day labels -->
            <div class="hc-body">
              <div class="hc-grid" id="hc-day-labels">
                <?php foreach(['D','L','M','X','J','V','S'] as $d): ?>
                <div class="hc-day-label"><?= $d ?></div>
                <?php endforeach; ?>
              </div>
              <!-- Days grid injected by JS -->
              <div class="hc-grid" id="hc-days"></div>
            </div>

            <!-- Time strip -->
            <div class="hc-step-label">Horarios disponibles</div>
            <div class="hc-time-strip" id="hc-times">
              <?php foreach(['9:00','10:00','11:30','14:00','15:30','17:00'] as $t): ?>
              <span class="hc-time" data-t="<?= $t ?>"><?= $t ?></span>
              <?php endforeach; ?>
            </div>

            <!-- Selected state -->
            <div id="hc-selected-info" class="hidden px-5 py-3 bg-teal-50 border-t border-teal-100 text-xs text-teal-800 font-semibold flex items-center gap-2">
              <i data-lucide="calendar-check" class="w-3.5 h-3.5 text-teal-600"></i>
              <span id="hc-selected-text"></span>
            </div>

            <!-- CTA inside calendar -->
            <div class="px-5 pb-5 pt-3">
              <a href="vistas/public/login.php" class="plan-cta-primary text-sm">Quiero Unirme ahora →</a>
            </div>
          </div>

          
        </div>
      </div>
    </section>

    <!-- ── MARQUEE LOGOS / EMPRESAS ───────────────────────────────────────── -->
    <?php
    // Obtener empresas con logos para el marquee
    $empresas_logos = [];
    try {
      if (isset($pdo_lp)) {
        $stmt = $pdo_lp->query("SELECT id, nombre, logo_path FROM empresas WHERE activo = 1 AND logo_path IS NOT NULL AND logo_path <> '' ORDER BY RAND() LIMIT 25");
        $empresas_logos = $stmt->fetchAll() ?: [];
      }
    } catch (Throwable $e) {
      $empresas_logos = [];
    }
    ?>
    <div class="marquee-wrap py-6 border-y border-slate-100 bg-slate-50/60">
      <div class="marquee-track">
        <?php if (!empty($empresas_logos)): ?>
          <?php
          // Duplicar para efecto infinito
          $logos_marquee = array_merge($empresas_logos, $empresas_logos);
          foreach ($logos_marquee as $emp):
            $logo_path = trim((string) ($emp['logo_path'] ?? ''));
            if ($logo_path === '') {
              continue;
            }
            $logo_url = preg_match('/^https?:\/\//i', $logo_path)
              ? $logo_path
              : $join_url($base, ltrim($logo_path, '/'));
          ?>
          <div class="marquee-logo-item grayscale hover:grayscale-0 transition-all duration-300" title="<?= htmlspecialchars($emp['nombre']) ?>">
            <img src="<?= htmlspecialchars($logo_url) ?>" alt="<?= htmlspecialchars($emp['nombre']) ?>" class="h-10 w-auto object-contain">
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <?php
          // Fallback a features si no hay logos
          $items = ['Multisucursal','Reservas 24/7','Confirmación por email','Panel de métricas','Gestión de reseñas','Blog integrado','Personalización de marca','Control por roles','Página web propia','Catálogo de servicios'];
          $icons = ['building-2','clock','mail','bar-chart-2','star','file-text','palette','shield-check','globe','list'];
          foreach(array_merge($items,$items) as $k=>$it):
            $ic = $icons[$k % count($icons)];
          ?>
          <span class="marquee-item"><i data-lucide="<?= $ic ?>" class="w-3.5 h-3.5 text-teal-600"></i><?= $it ?></span>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- ── STATS ──────────────────────────────────────────────────────────── -->
    <section class="max-w-7xl mx-auto px-5 py-20">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-10">
        <?php
        $stats = [
          ['n'=>20,'s'=>'+','l'=>'Negocios activos'],
          ['n'=>900,'s'=>'+','l'=>'Citas gestionadas'],
          ['n'=>95,'s'=>'%','l'=>'Satisfacción de clientes'],
          ['n'=>24,'s'=>'/7','l'=>'Disponibilidad del sistema'],
        ];
        foreach($stats as $i=>$st):
        ?>
        <div class="stat-pill reveal delay-<?= $i+1 ?>">
          <div class="stat-number"><span data-counter="<?= $st['n'] ?>" data-suffix="<?= $st['s'] ?>">0</span></div>
          <div class="stat-label"><?= $st['l'] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- ── BENEFICIOS ──────────────────────────────────────────────────────── -->
    <section id="beneficios" class="py-24 bg-white border-y border-slate-100">
      <div class="max-w-7xl mx-auto px-5">
        <div class="text-center max-w-2xl mx-auto mb-16">
          <div class="badge-pill reveal mx-auto mb-5">Diseñado para crecer</div>
          <h2 class="reveal text-4xl font-extrabold text-slate-900 delay-1">Todo lo que tu negocio necesita para profesionalizarse</h2>
          <p class="reveal delay-2 mt-4 text-slate-500 text-lg">Una sola plataforma para gestionar cada aspecto de tu agenda y equipo.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <?php
          $feats = [
            ['icon'=>'building-2','t'=>'Multisucursal','d'=>'Configura horarios, equipo y servicios de forma independiente por cada sede.'],
            ['icon'=>'zap','t'=>'Reservas 24/7','d'=>'Tus clientes agendan en segundos desde cualquier dispositivo, sin llamadas.'],
            ['icon'=>'shield-check','t'=>'Roles de acceso','d'=>'Admin, Gerente y Empleado: cada uno ve solo lo que necesita.'],
            ['icon'=>'bar-chart-3','t'=>'Métricas reales','d'=>'Conoce servicios más vendidos, tasa de conversión y rendimiento del equipo.'],
            ['icon'=>'star','t'=>'Reseñas automáticas','d'=>'Al completar una cita, el cliente recibe un correo para dejar su opinión.'],
            ['icon'=>'mail','t'=>'Notificaciones','d'=>'Confirmaciones, recordatorios y actualizaciones de estado por email.'],
            ['icon'=>'palette','t'=>'Tu marca','d'=>'Logo, color corporativo y dominio propio. La plataforma luce como tuya.'],
            ['icon'=>'file-text','t'=>'Blog integrado','d'=>'Publica contenido valioso para atraer clientes orgánicamente.'],
          ];
          foreach($feats as $i=>$f):
            $d = $i < 4 ? $i+1 : (($i-4)+1);
          ?>
          <article class="feat-card reveal delay-<?= $d ?>">
            <div class="feat-icon"><i data-lucide="<?= $f['icon'] ?>" class="w-5 h-5"></i></div>
            <h3 class="text-base font-bold mb-2"><?= $f['t'] ?></h3>
            <p class="text-sm text-slate-500 leading-relaxed"><?= $f['d'] ?></p>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ── CÓMO FUNCIONA ───────────────────────────────────────────────────── -->
    <section id="como-funciona" class="py-24 max-w-7xl mx-auto px-5">
      <div class="text-center mb-16">
        <div class="badge-pill reveal mx-auto mb-5">Proceso simple</div>
        <h2 class="reveal delay-1 text-4xl font-extrabold text-slate-900">En 3 pasos, listo para recibir citas</h2>
        <p class="reveal delay-2 mt-4 text-slate-500 text-lg max-w-xl mx-auto">Sin instalaciones. Sin configuraciones complicadas. Empieza hoy.</p>
      </div>
      <div class="grid md:grid-cols-3 gap-8 relative">
        <!-- Connector line desktop -->
        <div class="hidden md:block absolute top-10 left-1/6 right-1/6 h-px bg-gradient-to-r from-transparent via-teal-300 to-transparent"></div>
        <?php
        $steps = [
          ['n'=>'01','icon'=>'user-plus','t'=>'Crea tu cuenta','d'=>'Registra tu empresa, elige tu plan y personaliza tu perfil con logo y colores.'],
          ['n'=>'02','icon'=>'settings','t'=>'Configura tu negocio','d'=>'Agrega sucursales, servicios y empleados. Define horarios por sede.'],
          ['n'=>'03','icon'=>'calendar-check','t'=>'Recibe reservas','d'=>'Comparte tu link único. Tus clientes agendan solos, tú recibes la notificación.'],
        ];
        foreach($steps as $i=>$s):
        ?>
        <div class="reveal delay-<?= $i+1 ?> text-center">
          <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-teal-500 to-cyan-500 text-white flex items-center justify-center mx-auto mb-5 shadow-lg shadow-teal-500/25 text-xl font-black" style="font-family:'Space Grotesk',sans-serif"><?= $s['n'] ?></div>
          <h3 class="text-xl font-bold mb-3"><?= $s['t'] ?></h3>
          <p class="text-slate-500 text-sm leading-relaxed max-w-xs mx-auto"><?= $s['d'] ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- ── DARK FEATURE SECTION ────────────────────────────────────────────── -->
    <section id="caracteristicas" class="py-10 max-w-7xl mx-auto px-5">
      <div class="dark-section p-10 md:p-16 reveal">
        <div class="relative z-10 grid lg:grid-cols-2 gap-14 items-center">
          <div>
            <div class="badge-pill mb-6" style="background:rgba(13,148,136,.15);border-color:rgba(13,148,136,.3);color:#5eead4">Control total</div>
            <h2 class="text-3xl md:text-4xl font-extrabold text-white">Un panel para administrar todo tu negocio.</h2>
            <p class="mt-4 text-slate-400 leading-relaxed">Panel administrativo moderno con dashboard de métricas, gestión de usuarios con roles, clientes, servicios, reseñas y más.</p>
            <div class="mt-8 space-y-3">
              <?php
              $dark_feats = [
                'Dashboard con métricas en tiempo real por sucursal',
                'Calendario de citas con filtros por día, semana y mes',
                'Gestión de clientes con historial completo',
                'Blog propio para posicionamiento SEO',
                'Página pública automática con tu URL personalizada',
                'Sistema de mensajería interna entre roles',
              ];
              foreach($dark_feats as $df):
              ?>
              <div class="feat-list-item">
                <div class="feat-check"><i data-lucide="check" class="w-3 h-3 text-teal-400"></i></div>
                <span class="text-sm text-slate-300"><?= $df ?></span>
              </div>
              <?php endforeach; ?>
            </div>
            <a href="<?= htmlspecialchars($demo_url) ?>" class="btn-primary mt-8 inline-flex">Ver demo en vivo <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <?php
            $dark_cards = [
              ['icon'=>'layout-dashboard','t'=>'Dashboard',       'd'=>'Métricas en tiempo real de toda tu empresa.'],
              ['icon'=>'calendar',        't'=>'Calendario',      'd'=>'Vista de citas con filtros y edición rápida.'],
              ['icon'=>'users',           't'=>'Equipo',          'd'=>'Roles: admin, gerente y empleado.'],
              ['icon'=>'star',            't'=>'Reseñas',         'd'=>'Aprobación de reseñas por servicio.'],
              ['icon'=>'globe',           't'=>'Página pública',  'd'=>'URL propia con tu marca y colores.'],
              ['icon'=>'bell',            't'=>'Notificaciones',  'd'=>'Emails automáticos en cada evento clave.'],
            ];
            foreach($dark_cards as $dc):
            ?>
            <div class="bg-white/5 border border-white/8 rounded-2xl p-5 backdrop-blur-sm hover:bg-white/8 transition-colors">
              <div class="w-8 h-8 rounded-lg bg-teal-500/15 flex items-center justify-center text-teal-400 mb-3"><i data-lucide="<?= $dc['icon'] ?>" class="w-4 h-4"></i></div>
              <h4 class="font-bold text-white text-sm"><?= $dc['t'] ?></h4>
              <p class="text-xs text-slate-400 mt-1 leading-relaxed"><?= $dc['d'] ?></p>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>

    <!-- ── ROLES ──────────────────────────────────────────────────────────── -->
    <section class="py-24 max-w-7xl mx-auto px-5">
      <div class="text-center mb-12">
        <div class="badge-pill reveal mx-auto mb-5">Para todo tu equipo</div>
        <h2 class="reveal delay-1 text-4xl font-extrabold text-slate-900">Cada persona tiene lo que necesita</h2>
        <p class="reveal delay-2 mt-4 text-slate-500 max-w-xl mx-auto">Accesos diferenciados para que cada rol trabaje de forma eficiente y enfocada.</p>
      </div>

      <div class="reveal delay-2 bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
        <!-- Tab nav -->
        <div class="border-b border-slate-100 px-6 py-4 flex gap-3 flex-wrap">
          <button class="role-tab active" data-role="admin">Administrador</button>
          <button class="role-tab" data-role="gerente">Gerente</button>
          <button class="role-tab" data-role="empleado">Empleado</button>
          <button class="role-tab" data-role="cliente">Cliente</button>
        </div>

        <!-- Panels -->
        <div class="p-8">
          <?php
          $roles = [
            'admin' => [
              'desc' => 'Control total de la empresa. Ve, gestiona y actúa en nombre de cualquier rol inferior.',
              'items' => ['Dashboard con métricas avanzadas de toda la empresa','Gestión de usuarios y asignación de roles','CRUD de servicios y asignación a empleados','Gestión de sucursales con horarios independientes','Calendario de citas con filtros avanzados','Administración de clientes y reseñas','Blog corporativo propio','Personalización completa de la página pública','Configuración de empresa: colores, logo, SEO','Mensajería interna con todo el equipo'],
            ],
            'gerente' => [
              'desc' => 'Responsable de su sucursal. Gestiona el equipo y las operaciones del día a día.',
              'items' => ['Dashboard de métricas de su sucursal','Gestión de usuarios de su sucursal','Servicios asignados a su sucursal','RUD de citas de su sucursal','Ajustes de sucursal: foto, horario, información','Mensajería con roles superiores e inferiores'],
            ],
            'empleado' => [
              'desc' => 'Accede solo a lo relevante para su trabajo diario y sus propias citas.',
              'items' => ['Dashboard personal con sus métricas','Vista y cambio de estado de sus citas','Ajustes de perfil personal'],
            ],
            'cliente' => [
              'desc' => 'Experiencia simple y rápida para agendar y gestionar sus reservas.',
              'items' => ['Historial de citas anteriores','Motor de agendamiento en pocos pasos','Ajustes de perfil personal'],
            ],
          ];
          foreach($roles as $key=>$r):
          ?>
          <div class="role-panel <?= $key==='admin'?'active':'' ?>" id="role-<?= $key ?>">
            <p class="text-slate-500 mb-6 text-sm"><?= $r['desc'] ?></p>
            <div class="grid sm:grid-cols-2 gap-3">
              <?php foreach($r['items'] as $item): ?>
              <div class="flex items-start gap-2.5 text-sm text-slate-700">
                <div class="w-5 h-5 rounded-full bg-teal-50 flex items-center justify-center flex-shrink-0 mt-0.5"><i data-lucide="check" class="w-3 h-3 text-teal-600"></i></div>
                <?= $item ?>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ── PLANES ──────────────────────────────────────────────────────────── -->
    <section id="planes" class="py-24 bg-slate-50 border-y border-slate-100">
      <div class="max-w-7xl mx-auto px-5">
        <div class="text-center max-w-2xl mx-auto mb-12">
          <div class="badge-pill reveal mx-auto mb-5">Precios transparentes</div>
          <h2 class="reveal delay-1 text-4xl font-extrabold text-slate-900">El plan ideal para tu negocio</h2>
          <p class="reveal delay-2 mt-4 text-slate-500">Sin letra pequeña. Cambia o cancela cuando quieras.</p>
          <!-- Period toggle -->
          <div class="reveal delay-3 mt-8 flex justify-center">
            <div class="period-toggle">
              <button class="period-btn active" id="btn-mensual">Mensual</button>
              <button class="period-btn" id="btn-anual">Anual <span class="inline-block ml-1 text-[9px] font-black bg-teal-100 text-teal-700 px-1.5 py-0.5 rounded-full">−15%</span></button>
            </div>
          </div>
        </div>

        <div class="mt-10 grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
          <?php if (empty($plans)): ?>
          <div class="md:col-span-3 bg-white border rounded-2xl p-6 text-center text-slate-500">
            No pudimos cargar los planes en este momento. Intenta recargar en unos segundos.
          </div>
          <?php endif; ?>
          <?php foreach($plans as $idx=>$p):
            $pm = (float)$p['precio'];
            $pa = (float)($p['precio_anual'] ?? 0);
            $is_free = $pm <= 0;
            $featured = $idx === 1;
          ?>
          <article class="plan-card reveal delay-<?= $idx+1 ?> <?= $featured?'featured':'' ?>">
            <?php if($featured): ?><span class="plan-badge">Más popular</span><?php endif; ?>

            <div class="mb-6">
              <h3 class="text-xl font-extrabold text-slate-900"><?= htmlspecialchars($p['nombre']) ?></h3>
              <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($p['descripcion']) ?></p>
            </div>

            <div class="mb-8 pb-8 border-b border-slate-100">
              <?php if($is_free): ?>
              <div class="plan-price-val text-slate-900">Gratis</div>
              <div class="text-xs text-slate-400 mt-1">Para siempre</div>
              <?php else: ?>
              <div class="flex items-end gap-1.5">
                <span class="plan-price-val text-slate-900" data-price-monthly="<?= $pm ?>" data-price-yearly="<?= $pa ?>">Q<?= number_format($pm,0) ?></span>
                <span class="text-slate-400 font-semibold mb-1.5 price-period">/mes</span>
              </div>
              <div class="text-xs text-slate-400 mt-1 plan-annual-note" <?= !$pa?'style="display:none"':'' ?>>
                O <strong class="text-teal-600">Q<?= number_format($pa,0) ?></strong>/año (ahorra <?= $pm>0?number_format(($pm*12)-$pa,0):'0' ?> Q)
              </div>
              <?php endif; ?>
            </div>

            <ul class="space-y-2.5 mb-8 flex-grow">
              <?php
              $feats_plan = [
                [$p['max_sucursales'].' '.($p['max_sucursales']==1?'Sucursal':'Sucursales'), 'building-2'],
                [$p['max_empleados'].' Profesionales', 'users'],
                [$p['max_servicios'].' Servicios', 'list'],
                [number_format((int)$p['max_clientes']).' Clientes', 'user-check'],
              ];
              // Bonus feats por plan
              $bonus = [];
              if(!$is_free) $bonus[] = ['Blog integrado','file-text'];
              if($idx>=2)   $bonus[] = ['Sucursales ilimitadas disponibles','building'];
              foreach(array_merge($feats_plan,$bonus) as $pf):
              ?>
              <li class="plan-feat">
                <div class="w-4 h-4 rounded-full bg-teal-50 grid place-items-center flex-shrink-0"><i data-lucide="check" class="w-2.5 h-2.5 text-teal-600"></i></div>
                <span class="text-slate-600"><?= $pf[0] ?></span>
              </li>
              <?php endforeach; ?>
              <?php if($is_free): ?>
              <li class="plan-feat text-slate-400">
                <div class="w-4 h-4 rounded-full bg-slate-50 grid place-items-center flex-shrink-0"><i data-lucide="x" class="w-2.5 h-2.5 text-slate-300"></i></div>
                <span>Blog (plan de pago)</span>
              </li>
              <?php endif; ?>
            </ul>

            <?php if($is_free): ?>
            <a href="<?= htmlspecialchars($login_url) ?>" class="plan-cta-primary">Comenzar gratis</a>
            <?php elseif($featured): ?>
            <a href="<?= htmlspecialchars($login_url) ?>" class="plan-cta-primary">Contratar <?= htmlspecialchars($p['nombre']) ?></a>
            <?php else: ?>
            <a href="<?= htmlspecialchars($login_url) ?>" class="plan-cta-secondary">Contratar <?= htmlspecialchars($p['nombre']) ?></a>
            <?php endif; ?>
          </article>
          <?php endforeach; ?>
        </div>

        <p class="reveal text-center mt-8 text-xs text-slate-400">¿Tienes dudas? <a href="#faq" class="text-teal-600 font-semibold hover:underline">Lee las preguntas frecuentes</a> o <a href="<?= htmlspecialchars($login_url) ?>" class="text-teal-600 font-semibold hover:underline">contáctanos</a>.</p>
      </div>
    </section>

    <!-- ── FAQ ────────────────────────────────────────────────────────────── -->
    <section id="faq" class="py-24 max-w-3xl mx-auto px-5">
      <div class="text-center mb-12">
        <div class="badge-pill reveal mx-auto mb-5">Preguntas frecuentes</div>
        <h2 class="reveal delay-1 text-4xl font-extrabold text-slate-900">Todo lo que necesitas saber</h2>
        <p class="reveal delay-2 mt-4 text-slate-500">Si no encuentras tu respuesta, escríbenos.</p>
      </div>
      <div class="space-y-3">
        <?php foreach($faq_schema_items as $i=>$f): ?>
        <details class="faq-item reveal delay-<?= ($i%4)+1 ?>">
          <summary>
            <span><?= htmlspecialchars($f['q']) ?></span>
            <i data-lucide="plus" class="faq-icon w-5 h-5"></i>
          </summary>
          <div class="faq-body"><?= htmlspecialchars($f['a']) ?></div>
        </details>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- ── CTA FINAL ───────────────────────────────────────────────────────── -->
    <section class="py-16 max-w-7xl mx-auto px-5">
      <div class="reveal rounded-3xl bg-gradient-to-br from-teal-600 to-cyan-600 p-12 md:p-16 text-center relative overflow-hidden shadow-2xl shadow-teal-500/20">
        <div class="absolute inset-0 pointer-events-none opacity-20">
          <div class="absolute top-0 left-1/4 w-80 h-80 bg-white rounded-full blur-[100px]"></div>
          <div class="absolute bottom-0 right-1/4 w-64 h-64 bg-white rounded-full blur-[80px]"></div>
        </div>
        <div class="relative z-10">
          <h2 class="text-3xl md:text-5xl font-extrabold text-white leading-tight mb-5">Empieza hoy y<br>recibe tu primera reserva.</h2>
          <p class="text-teal-100 text-lg max-w-xl mx-auto mb-8">Crea tu cuenta gratuita en minutos. Sin tarjeta de crédito.</p>
          <a href="<?= htmlspecialchars($login_url) ?>" class="inline-flex items-center gap-2 bg-white text-teal-700 font-black px-8 py-4 rounded-xl shadow-xl hover:bg-teal-50 transition-all transform hover:scale-105 text-base">
            Crear cuenta gratis <i data-lucide="arrow-right" class="w-4 h-4"></i>
          </a>
        </div>
      </div>
    </section>

    <!-- ── CONTACTO ─────────────────────────────────────────────────────────── -->
    <section class="py-16 max-w-7xl mx-auto px-5" id="contacto">
      <div class="grid lg:grid-cols-2 gap-12 items-center">
        <!-- Texto -->
        <div class="reveal">
          <div class="badge-pill mb-5">
            <span class="pulse-dot"></span>
            ¿Tienes preguntas?
          </div>
          <h2 class="text-4xl md:text-5xl font-extrabold text-slate-900 leading-tight mb-5">
            ¿Necesitas ayuda para empezar? <span class="grad-text">Contáctanos</span>
          </h2>
          <p class="text-lg text-slate-500 mb-8">
            ¿Tienes dudas sobre cómo funciona? ¿Necesitas una solución personalizada para tu negocio? 
            Escríbenos y te ayudaremos a automatizar tu agenda de reservas.
          </p>
          <div class="flex flex-wrap gap-4">
            <a href="https://wa.me/50251036244" target="_blank" class="btn-primary inline-flex items-center gap-2">
              <i data-lucide="message-circle" class="w-5 h-5"></i>
              WhatsApp
            </a>
            <a href="mailto:soporte@reservasgp.com" class="btn-ghost inline-flex items-center gap-2">
              <i data-lucide="mail" class="w-5 h-5"></i>
              soporte@reservasgp.com
            </a>
          </div>
        </div>

        <!-- Formulario -->
        <div class="reveal-right delay-2">
          <div class="bg-white rounded-3xl p-8 shadow-xl border border-slate-100">
            <h3 class="text-2xl font-bold text-slate-900 mb-2">Envíanos un mensaje</h3>
            <p class="text-slate-500 mb-6">Te responderemos en menos de 24 horas</p>
            
            <form id="contactFormHome" class="space-y-4">
              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
                  <input type="text" name="nombre" required
                         class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 transition-all outline-none"
                         placeholder="Tu nombre">
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">Correo *</label>
                  <input type="email" name="email" required
                         class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 transition-all outline-none"
                         placeholder="tu@email.com">
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Empresa (opcional)</label>
                <input type="text" name="empresa_remitente"
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 transition-all outline-none"
                       placeholder="Nombre de tu negocio">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Mensaje *</label>
                <textarea name="mensaje" required rows="4"
                          class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 transition-all outline-none resize-none"
                          placeholder="¿En qué podemos ayudarte?"></textarea>
              </div>
              
              <button type="submit" 
                      class="w-full py-4 bg-gradient-to-r from-teal-500 to-cyan-500 text-white font-bold rounded-xl shadow-lg shadow-teal-500/30 hover:shadow-xl hover:shadow-teal-500/40 transform hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                <i data-lucide="send" class="w-5 h-5"></i>
                Enviar mensaje
              </button>
            </form>

            <!-- Mensaje de éxito -->
            <div id="successMessageHome" class="hidden mt-6 p-4 rounded-xl bg-green-50 border border-green-200">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                  <i data-lucide="check" class="w-5 h-5 text-green-600"></i>
                </div>
                <div>
                  <p class="font-semibold text-green-900">¡Mensaje enviado!</p>
                  <p class="text-sm text-green-700">Te contactaremos pronto.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- ── FOOTER ─────────────────────────────────────────────────────────── -->
  <footer class="site-footer py-16">
    <div class="max-w-7xl mx-auto px-5 grid md:grid-cols-4 gap-10 mb-12">
      <div class="md:col-span-2">
        <div class="flex items-center gap-2.5 mb-4">
          <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-teal-500 to-cyan-500 text-white flex items-center justify-center font-black text-xs">GP</div>
          <span class="text-white font-extrabold" style="font-family:'Space Grotesk',sans-serif">Reservas GP</span>
        </div>
        <p class="text-sm max-w-xs leading-relaxed">Automatiza tu agenda y enfócate en lo que realmente importa: tus clientes.</p>
      </div>
      <div>
        <h4 class="text-white font-bold mb-4 text-sm">Navegación</h4>
        <ul class="space-y-2 text-sm">
          <li><a href="#beneficios">Beneficios</a></li>
          <li><a href="#como-funciona">Cómo funciona</a></li>
          <li><a href="#planes">Planes</a></li>
          <li><a href="#faq">FAQ</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-white font-bold mb-4 text-sm">Sistema</h4>
        <ul class="space-y-2 text-sm">
          <li><a href="<?= htmlspecialchars($login_url) ?>" class="text-teal-400 font-semibold">Acceso Admin</a></li>
          <li><a href="<?= htmlspecialchars($demo_url) ?>">Demo en vivo</a></li>
          <li><a href="<?= htmlspecialchars($robots_url) ?>">Robots.txt</a></li>
          <li><a href="<?= htmlspecialchars($sitemap_url) ?>">Sitemap</a></li>
        </ul>
      </div>
    </div>
    <div class="max-w-7xl mx-auto px-5 pt-8 border-t border-white/5 text-center text-xs text-slate-600">
      © <?= date('Y') ?> <?= htmlspecialchars($site_name) ?>. Todos los derechos reservados.
    </div>
  </footer>

  <!-- ── FLOATING CTA ────────────────────────────────────────────────────── -->
  <div id="float-cta">
    <a href="https://wa.me/50251036244" target="_blank" class="btn-primary shadow-2xl shadow-teal-500/30 text-sm py-3 px-5">
      <i data-lucide="message-circle-check" class="w-4 h-4"></i> Contactanos
    </a>
  </div>

  <!-- ── SCRIPTS ─────────────────────────────────────────────────────────── -->
  <script>
  (() => {
    // ── Lucide icons ──────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
      if (window.lucide) lucide.createIcons();
    });
    window.addEventListener('load', () => {
      if (window.lucide) lucide.createIcons();
    });

    // ── Scroll progress ───────────────────────────────────────────────────
    const progressBar = document.getElementById('progress-bar');
    window.addEventListener('scroll', () => {
      const scrolled = window.scrollY / (document.body.scrollHeight - window.innerHeight) * 100;
      progressBar.style.width = Math.min(100, scrolled) + '%';
    }, { passive: true });

    // ── Sticky header shadow ──────────────────────────────────────────────
    const header = document.getElementById('site-header');
    window.addEventListener('scroll', () => {
      header.classList.toggle('scrolled', window.scrollY > 30);
    }, { passive: true });

    // ── Floating CTA ──────────────────────────────────────────────────────
    const floatCta = document.getElementById('float-cta');
    window.addEventListener('scroll', () => {
      floatCta.classList.toggle('show', window.scrollY > 500);
    }, { passive: true });

    // ── Spotlight cards ───────────────────────────────────────────────────
    document.querySelectorAll('.feat-card').forEach(card => {
      card.addEventListener('mousemove', e => {
        const r = card.getBoundingClientRect();
        card.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100) + '%');
        card.style.setProperty('--my', ((e.clientY - r.top)  / r.height * 100) + '%');
      });
    });

    // ── Intersection observer: reveal + counter ────────────────────────────
    const obs = new IntersectionObserver(entries => {
      entries.forEach(en => {
        if (!en.isIntersecting) return;
        const el = en.target;
        el.classList.add('visible');
        // counter
        if (el.dataset.counter && !el.dataset.counted) {
          el.dataset.counted = '1';
          const target = +el.dataset.counter;
          const suffix = el.dataset.suffix || '';
          const start  = performance.now();
          const dur    = 1800;
          const tick   = now => {
            const p = Math.min((now - start) / dur, 1);
            const ease = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(target * ease) + suffix;
            if (p < 1) requestAnimationFrame(tick);
          };
          requestAnimationFrame(tick);
        }
        obs.unobserve(el);
      });
    }, { threshold: 0.12 });

    document.querySelectorAll('.reveal,.reveal-left,.reveal-right,[data-counter]').forEach(el => obs.observe(el));

    // ── Roles tabs ────────────────────────────────────────────────────────
    document.querySelectorAll('.role-tab').forEach(tab => {
      tab.addEventListener('click', () => {
        document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.role-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('role-' + tab.dataset.role)?.classList.add('active');
        if (window.lucide) lucide.createIcons();
      });
    });

    // ── Plans toggle monthly/annual ───────────────────────────────────────
    const btnM = document.getElementById('btn-mensual');
    const btnA = document.getElementById('btn-anual');
    const prices    = document.querySelectorAll('[data-price-monthly]');
    const periods   = document.querySelectorAll('.price-period');
    const annNotes  = document.querySelectorAll('.plan-annual-note');

    btnM.addEventListener('click', () => {
      btnM.classList.add('active'); btnA.classList.remove('active');
      prices.forEach(el => {
        const v = +el.dataset.priceMonthly;
        el.textContent = v <= 0 ? 'Gratis' : 'Q' + v.toLocaleString('es-GT');
      });
      periods.forEach(el => el.textContent = '/mes');
      annNotes.forEach(el => el.style.display = 'none');
    });

    btnA.addEventListener('click', () => {
      btnA.classList.add('active'); btnM.classList.remove('active');
      prices.forEach(el => {
        const v = +el.dataset.priceYearly;
        el.textContent = v <= 0 ? 'Gratis' : 'Q' + v.toLocaleString('es-GT');
      });
      periods.forEach(el => el.textContent = '/año');
      annNotes.forEach(el => el.style.display = '');
    });

    // ── Hero mini-calendar ────────────────────────────────────────────────
    (function initHeroCalendar() {
      const MONTHS = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
      let cursor = new Date();
      let selected = null;
      cursor.setDate(1);

      const label   = document.getElementById('hc-month-label');
      const grid    = document.getElementById('hc-days');
      const prevBtn = document.getElementById('hc-prev');
      const nextBtn = document.getElementById('hc-next');
      const info    = document.getElementById('hc-selected-info');
      const infoTxt = document.getElementById('hc-selected-text');
      const times   = document.querySelectorAll('.hc-time');

      function render() {
        const y = cursor.getFullYear(), m = cursor.getMonth();
        label.textContent = MONTHS[m] + ' ' + y;
        const today = new Date(); today.setHours(0,0,0,0);
        const firstDay = new Date(y, m, 1).getDay();
        const daysInMonth = new Date(y, m+1, 0).getDate();
        const daysInPrev  = new Date(y, m, 0).getDate();

        let html = '';
        // Prev month padding
        for (let i = firstDay - 1; i >= 0; i--) {
          html += `<div class="hc-day other-month">${daysInPrev - i}</div>`;
        }
        // Current month
        for (let d = 1; d <= daysInMonth; d++) {
          const date = new Date(y, m, d);
          const isPast = date < today;
          const isToday = date.getTime() === today.getTime();
          const isSel = selected && date.getTime() === selected.getTime();
          let cls = 'hc-day';
          if (isPast) cls += ' disabled';
          if (isToday) cls += ' today';
          if (isSel) cls += ' selected';
          html += `<div class="${cls}" data-date="${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}">${d}</div>`;
        }
        // Next month padding
        const total = firstDay + daysInMonth;
        const remaining = total % 7 === 0 ? 0 : 7 - (total % 7);
        for (let d = 1; d <= remaining; d++) {
          html += `<div class="hc-day other-month">${d}</div>`;
        }
        grid.innerHTML = html;

        // Bind day clicks
        grid.querySelectorAll('.hc-day:not(.disabled):not(.other-month)').forEach(el => {
          el.addEventListener('click', () => {
            const [yy, mm, dd] = el.dataset.date.split('-').map(Number);
            selected = new Date(yy, mm-1, dd);
            render();
            times.forEach(t => t.classList.remove('selected'));
            info.classList.add('hidden');
          });
        });
      }

      prevBtn.addEventListener('click', () => { cursor.setMonth(cursor.getMonth()-1); render(); });
      nextBtn.addEventListener('click', () => { cursor.setMonth(cursor.getMonth()+1); render(); });

      times.forEach(t => {
        t.addEventListener('click', () => {
          times.forEach(x => x.classList.remove('selected'));
          t.classList.add('selected');
          if (selected) {
            const opts = { weekday:'long', year:'numeric', month:'long', day:'numeric' };
            infoTxt.textContent = selected.toLocaleDateString('es-GT', opts) + ' a las ' + t.dataset.t;
            info.classList.remove('hidden');
            if (window.lucide) lucide.createIcons();
          }
        });
      });

      render();
    })();

    // ── Contact form handler ─────────────────────────────────────────────
    document.getElementById('contactFormHome')?.addEventListener('submit', async function(e) {
      e.preventDefault();
      const btn = this.querySelector('button[type="submit"]');
      const originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i> Enviando...';
      if (window.lucide) lucide.createIcons();

      try {
        const formData = new FormData(this);
        const response = await fetch('<?= app_url('api/public/contacto-superadmin.php') ?>', {
          method: 'POST',
          body: formData
        });
        const data = await response.json();
        
        if (data.success) {
          this.reset();
          document.getElementById('successMessageHome').classList.remove('hidden');
          setTimeout(() => document.getElementById('successMessageHome').classList.add('hidden'), 5000);
        } else {
          alert(data.message || 'Error al enviar el mensaje');
        }
      } catch (err) {
        alert('Error de conexión. Intenta de nuevo.');
      } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (window.lucide) lucide.createIcons();
      }
    });

  })();
  </script>
</body>
</html>
