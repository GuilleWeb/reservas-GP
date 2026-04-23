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

// ── Stats landing (datos reales) ─────────────────────────────────────────────
$lp_empresas_activas = 0;
$lp_citas_total = 0;
$lp_satisfaccion_pct = 0;
try {
  $lp_empresas_activas = (int) ($pdo->query('SELECT COUNT(*) FROM empresas WHERE activo = 1')->fetchColumn() ?: 0);
  $lp_citas_total = (int) ($pdo->query('SELECT COUNT(*) FROM citas')->fetchColumn() ?: 0);
  $lp_citas_completadas = (int) ($pdo->query("SELECT COUNT(*) FROM citas WHERE estado = 'completada'")->fetchColumn() ?: 0);
  $lp_satisfaccion_pct = $lp_citas_total > 0 ? (int) round(($lp_citas_completadas / $lp_citas_total) * 100) : 0;
} catch (Throwable $e) {
  $lp_empresas_activas = 0;
  $lp_citas_total = 0;
  $lp_satisfaccion_pct = 0;
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
    .reveal.visible, .no-js .reveal { opacity: 1; transform: translateY(0); }
    .reveal-left  { opacity: 0; transform: translateX(-40px); transition: opacity .75s cubic-bezier(.16,1,.3,1), transform .75s cubic-bezier(.16,1,.3,1); }
    .reveal-right { opacity: 0; transform: translateX(40px);  transition: opacity .75s cubic-bezier(.16,1,.3,1), transform .75s cubic-bezier(.16,1,.3,1); }
    .reveal-left.visible, .reveal-right.visible, .no-js .reveal-left, .no-js .reveal-right { opacity: 1; transform: translateX(0); }
    .delay-1 { transition-delay: .08s; }
    .delay-2 { transition-delay: .16s; }
    .delay-3 { transition-delay: .24s; }
    /* Fallback: si JS tarda o falla, mostrar después de 2s */
    @media (prefers-reduced-motion: no-preference) {
      .reveal, .reveal-left, .reveal-right { animation: forceReveal 0s 2s forwards; }
      @keyframes forceReveal { to { opacity: 1; transform: none; } }
    }
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
 /* Contenedor con desvanecimiento en los bordes */
.marquee-wrap {
    overflow: hidden;
    position: relative;
    width: 100%;
    /* Máscara para bordes suaves */
    mask-image: linear-gradient(to right, transparent, black 15%, black 85%, transparent);
    -webkit-mask-image: linear-gradient(to right, transparent, black 15%, black 85%, transparent);
}

.marquee-track {
    display: flex;
    width: max-content;
    gap: 2rem; /* Mantén el gap consistente */
    animation: marquee-scroll 10s linear infinite;
}

/* Pausar al pasar el mouse (opcional, pero profesional) */
.marquee-track:hover {
    animation-play-state: paused;
}

@keyframes marquee-scroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); } /* Mueve exactamente la mitad del track duplicado */
}

.marquee-logo-item {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem 2rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    filter: grayscale(100%);
    opacity: 0.6;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.marquee-logo-item:hover {
    filter: grayscale(0%);
    opacity: 1;
    transform: translateY(-5px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}
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
          El software de citas que tu negocio necesita para <span class="grad-text">crecer sin límites.</span>
        </h1>

        <p class="reveal delay-2 mt-6 text-xl leading-relaxed text-slate-600 max-w-xl">
          Gestiona múltiples sucursales, automatiza recordatorios y profesionaliza la experiencia de tus clientes. Todo en una sola plataforma diseñada para negocios de servicios en Guatemala.
        </p>

        <div class="reveal delay-3 mt-10 flex flex-wrap gap-4">
          <a href="#planes"                          class="btn-primary text-lg px-8 py-4">Empezar gratis <i data-lucide="arrow-right" class="w-5 h-5"></i></a>
          <a href="<?= htmlspecialchars($demo_url) ?>" class="btn-ghost text-lg px-6 py-4">
            <i data-lucide="play-circle" class="w-5 h-5"></i> Ver demo
          </a>
        </div>

        <!-- Trust badges -->
        <div class="reveal delay-4 mt-8 flex flex-wrap items-center gap-6 text-sm text-slate-500">
          <div class="flex items-center gap-2">
            <i data-lucide="check-circle" class="w-5 h-5 text-teal-600"></i>
            <span>Sin tarjeta de crédito</span>
          </div>
          <div class="flex items-center gap-2">
            <i data-lucide="check-circle" class="w-5 h-5 text-teal-600"></i>
            <span>Configuración en 5 min</span>
          </div>
          <div class="flex items-center gap-2">
            <i data-lucide="check-circle" class="w-5 h-5 text-teal-600"></i>
            <span>Cancela cuando quieras</span>
          </div>
        </div>

        <!-- Tipos de negocios con imágenes -->
        <div class="reveal delay-4 mt-8">
          <p class="text-sm text-slate-500 mb-4">Perfecto para todo tipo de negocios de servicios:</p>
          <div class="grid grid-cols-5 gap-3">
            <?php
            $biz_types = [
              ['img'=>'https://images.unsplash.com/photo-1633681926035-ec1ac984418a?w=150&h=150&fit=crop', 'label'=>'Barberías', 'color'=>'from-amber-500 to-orange-600'],
              ['img'=>'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=150&h=150&fit=crop', 'label'=>'Spas', 'color'=>'from-teal-400 to-teal-600'],
              ['img'=>'https://images.unsplash.com/photo-1629909613654-28e377c37b09?w=150&h=150&fit=crop', 'label'=>'Clínicas', 'color'=>'from-blue-400 to-blue-600'],
              ['img'=>'https://images.unsplash.com/photo-1628009368231-7603353ae3ca?w=150&h=150&fit=crop', 'label'=>'Veterinarias', 'color'=>'from-emerald-400 to-emerald-600'],
              ['img'=>'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=150&h=150&fit=crop', 'label'=>'Fitness', 'color'=>'from-purple-500 to-pink-600'],
            ];
            foreach($biz_types as $biz):
            ?>
            <div class="relative group cursor-pointer">
              <div class="w-full aspect-square rounded-2xl overflow-hidden shadow-lg group-hover:shadow-xl transition-all group-hover:scale-105">
                <div class="absolute inset-0 bg-gradient-to-t <?= $biz['color'] ?> opacity-60 group-hover:opacity-70 transition-opacity z-10"></div>
                <img src="<?= $biz['img'] ?>" alt="<?= $biz['label'] ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                <div class="absolute bottom-0 left-0 right-0 p-2 z-20 text-center">
                  <span class="text-white font-bold text-sm drop-shadow-lg"><?= $biz['label'] ?></span>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <p class="text-xs text-slate-400 mt-3 text-center">Y muchos más: consultorios, estudios, salones de uñas, consultorios dentales...</p>
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
      $empresas_originales = [];
      try {
          if (isset($pdo_lp)) {
              $stmt = $pdo_lp->query("SELECT id, nombre, logo_path FROM empresas WHERE activo = 1 AND logo_path IS NOT NULL AND logo_path <> '' ORDER BY RAND() LIMIT 20");
              $empresas_originales = $stmt->fetchAll() ?: [];
          }
      } catch (Throwable $e) { $empresas_originales = []; }

      // Si no hay datos, usamos los fallbacks
      if (!empty($empresas_logos)) {
        $count = count($empresas_logos);
        // Si tienes muy pocos logos, multiplícalos más veces
        // Necesitamos que el track sea MUCHO más ancho que el viewport
        $multiplier = ($count < 5) ? 10 : 4; 
        $marquee_items = [];
        for ($i = 0; $i < $multiplier; $i++) {
            $marquee_items = array_merge($marquee_items, $empresas_logos);
        }
    } else {
        // Fallback con los textos
        $items = ['Multisucursal','Reservas 24/7','Confirmación email','Panel métricas','Gestión reseñas'];
        $marquee_items = [];
        for ($i = 0; $i < 10; $i++) { $marquee_items = array_merge($marquee_items, $items); }
    }

      // DUPLICAR EXACTAMENTE UNA VEZ PARA EL LOOP INFINITO
      $marquee_items = array_merge($empresas_originales, $empresas_originales);
    ?>

    <div class="marquee-container py-12 bg-white">
        <p class="text-center text-xs uppercase tracking-widest text-slate-400 mb-8 font-bold">Empresas que confían en nuestra tecnología</p>
        
        <div class="marquee-wrap">
            <div class="marquee-track">
                <?php foreach ($marquee_items as $emp): ?>
                    <?php if (isset($emp['is_fallback'])): ?>
                        <div class="marquee-item border px-6 py-3 rounded-full flex items-center gap-2 shadow-sm text-slate-600 font-semibold">
                            <span class="w-2 h-2 bg-teal-500 rounded-full"></span>
                            <?= $emp['nombre'] ?>
                        </div>
                    <?php else: 
                        $logo_url = preg_match('/^https?:\/\//i', $emp['logo_path']) ? $emp['logo_path'] : $join_url($base, ltrim($emp['logo_path'], '/'));
                    ?>
                        <div class="marquee-logo-item" title="<?= htmlspecialchars($emp['nombre']) ?>">
                            <img src="<?= htmlspecialchars($logo_url) ?>" 
                                alt="<?= htmlspecialchars($emp['nombre']) ?>" 
                                class="h-10 w-auto grayscale-0 object-contain">
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── TESTIMONIOS ─────────────────────────────────────────────────────── -->
    <section id="testimonios" class="py-24 bg-gradient-to-b from-slate-50 to-white">
      <div class="max-w-7xl mx-auto px-5">
        <div class="text-center max-w-2xl mx-auto mb-16">
          <div class="badge-pill reveal mx-auto mb-5">Clientes satisfechos</div>
          <h2 class="reveal text-4xl font-extrabold text-slate-900 delay-1">Lo que dicen nuestros clientes</h2>
          <p class="reveal delay-2 mt-4 text-slate-500 text-lg">Empresas que han transformado su gestión de citas con Reservas GP</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
          <?php
          $testimonios = [
            [
              'nombre' => 'Dra. María Fernández',
              'cargo' => 'Directora médica',
              'empresa' => 'Clínica Salud Integral',
              'foto' => 'https://i.pravatar.cc/150?img=5',
              'texto' => 'Antes perdíamos el 30% de citas por olvidos. Con Reservas GP las cancelaciones bajaron a casi cero. Los recordatorios automáticos son oro puro.',
              'rating' => 5,
              'metrica' => '-30%',
              'metrica_label' => 'cancelaciones'
            ],
            [
              'nombre' => 'Carlos Mendoza',
              'cargo' => 'Propietario',
              'empresa' => 'Studio Cuts Barbería',
              'foto' => 'https://i.pravatar.cc/150?img=11',
              'texto' => 'Mis clientes ahora agendan a medianoche mientras yo duermo. Despertarme con citas confirmadas es la mejor sensación. La página quedó con mi marca.',
              'rating' => 5,
              'metrica' => '24/7',
              'metrica_label' => 'reservas online'
            ],
            [
              'nombre' => 'Ana Lucía Torres',
              'cargo' => 'Gerente',
              'empresa' => 'Spa Radiante',
              'foto' => 'https://i.pravatar.cc/150?img=9',
              'texto' => 'Gestionamos 3 sucursales desde un solo panel. Antes era un caos de llamadas y WhatsApp. Ahora todo está organizado y mi equipo es más productivo.',
              'rating' => 5,
              'metrica' => '3x',
              'metrica_label' => 'más eficiente'
            ]
          ];
          foreach($testimonios as $i=>$t):
          ?>
          <div class="reveal delay-<?= $i+1 ?> bg-white rounded-2xl p-8 shadow-lg shadow-slate-200/50 border border-slate-100 relative">
            <!-- Quote icon -->
            <div class="absolute -top-4 left-8 w-8 h-8 bg-teal-500 rounded-lg flex items-center justify-center shadow-lg">
              <i data-lucide="quote" class="w-4 h-4 text-white"></i>
            </div>
            
            <!-- Rating -->
            <div class="flex gap-1 mb-4">
              <?php for($s=0; $s<$t['rating']; $s++): ?>
                <i data-lucide="star" class="w-4 h-4 text-amber-400 fill-amber-400"></i>
              <?php endfor; ?>
            </div>
            
            <!-- Texto -->
            <p class="text-slate-600 leading-relaxed mb-6">"<?= $t['texto'] ?>"</p>
            
            <!-- Autor -->
            <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
              <img src="<?= $t['foto'] ?>" alt="<?= $t['nombre'] ?>" class="w-12 h-12 rounded-full object-cover">
              <div>
                <div class="font-bold text-slate-900"><?= $t['nombre'] ?></div>
                <div class="text-sm text-slate-500"><?= $t['cargo'] ?> · <?= $t['empresa'] ?></div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Stats de confianza -->
        <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
          <div class="reveal delay-1">
            <div class="text-4xl font-black text-teal-600"><?= number_format($lp_empresas_activas) ?></div>
            <div class="text-sm text-slate-500 mt-1">negocios activos</div>
          </div>
          <div class="reveal delay-2">
            <div class="text-4xl font-black text-teal-600"><?= number_format($lp_citas_total) ?></div>
            <div class="text-sm text-slate-500 mt-1">citas agendadas</div>
          </div>
          <div class="reveal delay-3">
            <div class="text-4xl font-black text-teal-600"><?= (int) $lp_satisfaccion_pct ?>%</div>
            <div class="text-sm text-slate-500 mt-1">satisfacción</div>
          </div>
          <div class="reveal delay-4">
            <div class="text-4xl font-black text-teal-600">24/7</div>
            <div class="text-sm text-slate-500 mt-1">soporte humano</div>
          </div>
        </div>
      </div>
    </section>

    <!-- ── DEMO VISUAL ───────────────────────────────────────────────────────── -->
    <section id="demo" class="py-24 bg-slate-900 relative overflow-hidden">
      <!-- Background decoration -->
      <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-teal-500/20 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-cyan-500/20 rounded-full blur-[120px]"></div>
      </div>
      
      <div class="max-w-7xl mx-auto px-5 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-12">
          <div class="badge-pill reveal mx-auto mb-5" style="background:rgba(94,234,212,.15);border-color:rgba(94,234,212,.3);color:#5eead4">Plataforma en acción</div>
          <h2 class="reveal text-4xl md:text-5xl font-extrabold text-white delay-1">Tu agenda, simplificada</h2>
          <p class="reveal delay-2 mt-4 text-slate-400 text-lg">Así se ve el panel de administración donde gestionas todo tu negocio</p>
        </div>

        <!-- Mockup Dashboard -->
        <div class="reveal delay-3 relative mx-auto max-w-5xl">
          <!-- Browser frame -->
          <div class="bg-white rounded-t-xl p-3 flex items-center gap-2 border border-slate-200 border-b-0">
            <div class="flex gap-1.5">
              <div class="w-3 h-3 rounded-full bg-red-500"></div>
              <div class="w-3 h-3 rounded-full bg-amber-500"></div>
              <div class="w-3 h-3 rounded-full bg-green-500"></div>
            </div>
            <div class="flex-1 bg-slate-100 rounded-lg px-3 py-1 text-xs text-slate-500 text-center">app.reservasgp.com/dashboard</div>
          </div>
          
          <!-- Dashboard content -->
          <div class="bg-white rounded-b-xl p-4 md:p-6 border border-slate-200">
            <div class="grid md:grid-cols-4 gap-4 mb-6">
              <!-- Stats cards -->
              <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                <div class="text-slate-500 text-xs mb-1">Citas hoy</div>
                <div class="text-2xl font-bold text-slate-900">12</div>
                <div class="text-slate-400 text-xs mt-1">Programadas</div>
              </div>
              <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                <div class="text-slate-500 text-xs mb-1">Clientes</div>
                <div class="text-2xl font-bold text-slate-900">5</div>
                <div class="text-slate-400 text-xs mt-1">Activos</div>
              </div>
              <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                <div class="text-slate-500 text-xs mb-1">Asistencia</div>
                <div class="text-2xl font-bold text-slate-900">94%</div>
                <div class="text-emerald-600 text-xs flex items-center gap-1 mt-1">
                  <i data-lucide="check" class="w-3 h-3"></i> Buen estado
                </div>
              </div>
              <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                <div class="text-slate-500 text-xs mb-1">Reseñas</div>
                <div class="text-2xl font-bold text-slate-900">8</div>
                <div class="text-amber-600 text-xs flex items-center gap-1 mt-1">
                  <i data-lucide="star" class="w-3 h-3"></i> 4.8 promedio
                </div>
              </div>
            </div>
            
            <!-- Calendar preview -->
            <div class="bg-white rounded-lg p-4 border border-slate-200">
              <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-lg bg-teal-500/15 flex items-center justify-center">
                    <i data-lucide="calendar" class="w-4 h-4 text-teal-700"></i>
                  </div>
                  <span class="text-slate-900 font-medium">Agenda de hoy</span>
                </div>
                <div class="flex gap-2">
                  <span class="px-2 py-1 bg-emerald-500/15 text-emerald-700 text-xs rounded">Confirmadas: 10</span>
                  <span class="px-2 py-1 bg-amber-500/15 text-amber-700 text-xs rounded">Pendientes: 2</span>
                </div>
              </div>
              
              <!-- Appointment rows -->
              <div class="space-y-2">
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                  <div class="text-slate-500 text-sm w-16">09:00</div>
                  <div class="flex-1">
                    <div class="text-slate-900 font-medium">Juan Pérez</div>
                    <div class="text-slate-500 text-sm">Consulta general · Dr. García</div>
                  </div>
                  <span class="px-2 py-1 bg-emerald-500/15 text-emerald-700 text-xs rounded">Confirmada</span>
                </div>
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                  <div class="text-slate-500 text-sm w-16">10:30</div>
                  <div class="flex-1">
                    <div class="text-slate-900 font-medium">María López</div>
                    <div class="text-slate-500 text-sm">Corte y peinado · Ana (Estilista)</div>
                  </div>
                  <span class="px-2 py-1 bg-emerald-500/15 text-emerald-700 text-xs rounded">En sala</span>
                </div>
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                  <div class="text-slate-500 text-sm w-16">14:00</div>
                  <div class="flex-1">
                    <div class="text-slate-900 font-medium">Pedro Gómez</div>
                    <div class="text-slate-500 text-sm">Masaje relajante · Sucursal Norte</div>
                  </div>
                  <span class="px-2 py-1 bg-amber-500/15 text-amber-700 text-xs rounded">Pendiente</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- CTA below demo -->
        <div class="text-center mt-10 reveal delay-4">
          <a href="<?= htmlspecialchars($demo_url) ?>" class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-400 text-slate-900 font-bold py-4 px-8 rounded-xl transition-all hover:scale-105 shadow-lg shadow-teal-500/25">
            <i data-lucide="play" class="w-5 h-5"></i>
            Probar demo interactiva gratis
          </a>
          <p class="text-slate-500 text-sm mt-3">No requiere tarjeta de crédito · Configuración en 5 minutos</p>
        </div>
      </div>
    </section>

    <!-- ── POR QUÉ ELEGIRNOS ────────────────────────────────────────────────── -->
    <section id="por-que-nosotros" class="py-24 bg-gradient-to-br from-teal-600 via-teal-700 to-cyan-700 relative overflow-hidden">
      <!-- Background decoration -->
      <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-0 right-0 w-1/2 h-full bg-white/5 skew-x-12 transform origin-top-right"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-teal-500/30 rounded-full blur-[100px]"></div>
      </div>
      
      <div class="max-w-7xl mx-auto px-5 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-16">
          <div class="badge-pill reveal mx-auto mb-5" style="background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.3);color:#fff">Reservas GP vs Otros</div>
          <h2 class="reveal text-4xl font-extrabold text-white delay-1">¿Por qué los negocios prefieren Reservas GP?</h2>
          <p class="reveal delay-2 mt-4 text-teal-100 text-lg">No somos solo un calendario. Somos el socio tecnológico que tu negocio necesita para crecer.</p>
        </div>

        <div class="grid lg:grid-cols-2 gap-8 items-center">
          <!-- Comparativa tabla -->
          <div class="reveal delay-3 bg-white/10 backdrop-blur rounded-2xl p-2 border border-white/20">
            <div class="bg-slate-900 rounded-xl overflow-hidden">
              <!-- Header -->
              <div class="grid grid-cols-3 text-sm font-semibold border-b border-slate-700">
                <div class="p-4 text-slate-400">Característica</div>
                <div class="p-4 text-slate-400 text-center">Agenda tradicional</div>
                <div class="p-4 bg-teal-500/20 text-teal-400 text-center font-bold">Reservas GP</div>
              </div>
              <!-- Rows -->
              <div class="grid grid-cols-3 text-sm border-b border-slate-800">
                <div class="p-4 text-white flex items-center gap-2"><i data-lucide="building-2" class="w-4 h-4 text-slate-500"></i> Múltiples sucursales</div>
                <div class="p-4 text-center text-slate-500"><i data-lucide="x" class="w-5 h-5 mx-auto"></i></div>
                <div class="p-4 bg-teal-500/10 text-center"><i data-lucide="check" class="w-5 h-5 text-teal-400 mx-auto"></i></div>
              </div>
              <div class="grid grid-cols-3 text-sm border-b border-slate-800">
                <div class="p-4 text-white flex items-center gap-2"><i data-lucide="globe" class="w-4 h-4 text-slate-500"></i> Página web propia</div>
                <div class="p-4 text-center text-slate-500"><i data-lucide="x" class="w-5 h-5 mx-auto"></i></div>
                <div class="p-4 bg-teal-500/10 text-center"><i data-lucide="check" class="w-5 h-5 text-teal-400 mx-auto"></i></div>
              </div>
              <div class="grid grid-cols-3 text-sm border-b border-slate-800">
                <div class="p-4 text-white flex items-center gap-2"><i data-lucide="users" class="w-4 h-4 text-slate-500"></i> Roles de equipo</div>
                <div class="p-4 text-center text-slate-500"><i data-lucide="x" class="w-5 h-5 mx-auto"></i></div>
                <div class="p-4 bg-teal-500/10 text-center"><i data-lucide="check" class="w-5 h-5 text-teal-400 mx-auto"></i></div>
              </div>
              <div class="grid grid-cols-3 text-sm border-b border-slate-800">
                <div class="p-4 text-white flex items-center gap-2"><i data-lucide="star" class="w-4 h-4 text-slate-500"></i> Sistema de reseñas</div>
                <div class="p-4 text-center text-slate-500"><i data-lucide="x" class="w-5 h-5 mx-auto"></i></div>
                <div class="p-4 bg-teal-500/10 text-center"><i data-lucide="check" class="w-5 h-5 text-teal-400 mx-auto"></i></div>
              </div>
              <div class="grid grid-cols-3 text-sm border-b border-slate-800">
                <div class="p-4 text-white flex items-center gap-2"><i data-lucide="bar-chart-2" class="w-4 h-4 text-slate-500"></i> Métricas y reportes</div>
                <div class="p-4 text-center text-slate-500"><i data-lucide="minus" class="w-5 h-5 mx-auto text-slate-600"></i></div>
                <div class="p-4 bg-teal-500/10 text-center"><i data-lucide="check" class="w-5 h-5 text-teal-400 mx-auto"></i></div>
              </div>
              <div class="grid grid-cols-3 text-sm">
                <div class="p-4 text-white flex items-center gap-2"><i data-lucide="palette" class="w-4 h-4 text-slate-500"></i> Personalización marca</div>
                <div class="p-4 text-center text-slate-500"><i data-lucide="x" class="w-5 h-5 mx-auto"></i></div>
                <div class="p-4 bg-teal-500/10 text-center"><i data-lucide="check" class="w-5 h-5 text-teal-400 mx-auto"></i></div>
              </div>
            </div>
          </div>

          <!-- Ventajas adicionales -->
          <div class="space-y-6">
            <div class="reveal delay-2 flex items-start gap-4">
              <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                <i data-lucide="zap" class="w-6 h-6 text-amber-300"></i>
              </div>
              <div>
                <h3 class="text-xl font-bold text-white mb-2">Todo incluido desde el primer día</h3>
                <p class="text-teal-100">No pagues extra por "plugins" o "add-ons". Reseñas, y roles vienen incluidos en todos los planes.</p>
              </div>
            </div>
            <div class="reveal delay-3 flex items-start gap-4">
              <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                <i data-lucide="headphones" class="w-6 h-6 text-teal-300"></i>
              </div>
              <div>
                <h3 class="text-xl font-bold text-white mb-2">Soporte humano, no bots</h3>
                <p class="text-teal-100">Te ayudamos a configurar tu cuenta y resolver dudas. Hablas con personas reales de Guatemala que entienden tu negocio.</p>
              </div>
            </div>
            <div class="reveal delay-4 flex items-start gap-4">
              <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                <i data-lucide="trending-up" class="w-6 h-6 text-emerald-300"></i>
              </div>
              <div>
                <h3 class="text-xl font-bold text-white mb-2">Crecemos contigo</h3>
                <p class="text-teal-100">Empieza gratis y escala cuando estés listo. De 1 a 10+ sucursales, tenemos el plan que se adapta a tu crecimiento.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- CTA -->
        <div class="text-center mt-16 reveal delay-4">
          <a href="#planes" class="inline-flex items-center gap-2 bg-white text-teal-700 font-bold py-4 px-8 rounded-xl transition-all hover:scale-105 shadow-xl">
            Ver planes y precios
            <i data-lucide="arrow-right" class="w-5 h-5"></i>
          </a>
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

    <?php /*
    ═══════════════════════════════════════════════════════════════════════════
    SECCIÓN COMENTADA: ROLES (Muy técnica para visitantes de landing)
    ═══════════════════════════════════════════════════════════════════════════
    <!-- ── ROLES ──────────────────────────────────────────────────────────── -->
    <section class="py-24 max-w-7xl mx-auto px-5">...</section>
    ═══════════════════════════════════════════════════════════════════════════
    */ ?>

    <!-- ── CARACTERÍSTICAS AVANZADAS ─────────────────────────────────────────── -->
    <section id="features" class="py-24 bg-gradient-to-b from-slate-50 via-white to-slate-50">
      <div class="max-w-7xl mx-auto px-5">
        <div class="text-center max-w-3xl mx-auto mb-16">
          <div class="badge-pill reveal mx-auto mb-5">Todo incluido</div>
          <h2 class="reveal text-4xl md:text-5xl font-extrabold text-slate-900 delay-1">Una plataforma completa<br>para hacer crecer tu negocio</h2>
          <p class="reveal delay-2 mt-4 text-slate-500 text-lg">No necesitas contratar múltiples herramientas. Todo lo que necesitas está aquí, incluido en tu plan.</p>
        </div>

        <?php
        $features_grid = [
          [
            'icon' => 'calendar-check',
            'title' => 'Agenda Inteligente',
            'desc' => 'Sistema de citas multi-sucursal con recordatorios automáticos por email y WhatsApp. Tus clientes reciben confirmaciones instantáneas.',
            'color' => 'from-teal-500 to-cyan-500',
            'size' => 'large', // ocupa 2 cols
            'badge' => 'Disponible'
          ],
          [
            'icon' => 'globe',
            'title' => 'Página Web Profesional',
            'desc' => 'Tu propio sitio web con tu logo, colores y dominio personalizado. Los clientes agendan directamente desde tu página.',
            'color' => 'from-violet-500 to-purple-600',
            'size' => 'normal',
            'badge' => 'Disponible'
          ],
          [
            'icon' => 'users',
            'title' => 'CRM de Clientes',
            'desc' => 'Ficha completa de cada cliente: historial de servicios, preferencias, notas internas y seguimiento de fidelización.',
            'color' => 'from-blue-500 to-indigo-600',
            'size' => 'large',
            'badge' => 'Próximamente'
          ],
          [
            'icon' => 'mail',
            'title' => 'Email Marketing',
            'desc' => 'Envía campañas promocionales, newsletters y recordatorios personalizados. Segmenta por tipo de cliente y comportamiento.',
            'color' => 'from-amber-500 to-orange-600',
            'size' => 'normal',
            'badge' => 'Próximamente'
          ],
          [
            'icon' => 'gift',
            'title' => 'Programa de Lealtad',
            'desc' => 'Puntos por visita, recompensas automáticas y niveles de cliente VIP. Fideliza a tus clientes más valiosos.',
            'color' => 'from-pink-500 to-rose-500',
            'size' => 'normal',
            'badge' => 'Próximamente'
          ],
          [
            'icon' => 'share-2',
            'title' => 'Tarjeta Digital',
            'desc' => 'Comparte tu perfil de negocio con un solo link. Incluye servicios, precios, horarios y botón directo de agendamiento para redes sociales.',
            'color' => 'from-emerald-500 to-green-600',
            'size' => 'large',
            'badge' => 'Disponible'
          ],
          [
            'icon' => 'bar-chart-3',
            'title' => 'Analíticas Avanzadas',
            'desc' => 'Reportes de ingresos, ocupación, clientes recurrentes y métricas de crecimiento. Exporta a Excel o PDF.',
            'color' => 'from-cyan-500 to-blue-600',
            'size' => 'normal',
            'badge' => 'Disponible'
          ],
          [
            'icon' => 'smartphone',
            'title' => 'App Móvil',
            'desc' => 'Gestiona tu negocio desde cualquier lugar. Recibe notificaciones push y consulta tu agenda en tiempo real.',
            'color' => 'from-slate-600 to-slate-800',
            'size' => 'normal',
            'badge' => 'En desarrollo'
          ],
          [
            'icon' => 'tags',
            'title' => 'Promociones y Descuentos',
            'desc' => 'Crea códigos promocionales, ofertas por temporada y descuentos por volumen. Automatiza tus campañas comerciales.',
            'color' => 'from-red-500 to-pink-600',
            'size' => 'normal',
            'badge' => 'Próximamente'
          ],
          [
            'icon' => 'star',
            'title' => 'Sistema de Reseñas',
            'desc' => 'Los clientes califican sus visitas. Construye reputación online y mejora tu posicionamiento en Google.',
            'color' => 'from-yellow-400 to-amber-500',
            'size' => 'normal',
            'badge' => 'Disponible'
          ],
          [
            'icon' => 'shield-check',
            'title' => 'Seguridad Enterprise',
            'desc' => 'Encriptación SSL, backups automáticos diarios, roles de permisos granulares y cumplimiento con protección de datos.',
            'color' => 'from-teal-600 to-emerald-600',
            'size' => 'large',
            'badge' => 'Disponible'
          ],
        ];
        ?>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-5">
          <?php foreach($features_grid as $i=>$feat): 
            $col_span = $feat['size'] === 'large' ? 'md:col-span-2' : '';
            $is_available = $feat['badge'] === 'Disponible';
            $badge_class = $is_available ? 'bg-emerald-100 text-emerald-700' : ($feat['badge'] === 'En desarrollo' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700');
          ?>
          <div class="reveal delay-<?= ($i%4)+1 ?> <?= $col_span ?> group">
            <div class="h-full bg-white rounded-2xl p-6 border border-slate-200 hover:border-teal-300 hover:shadow-xl hover:shadow-teal-500/10 transition-all duration-300 relative overflow-hidden">
              <!-- Gradient bg on hover -->
              <div class="absolute inset-0 bg-gradient-to-br <?= $feat['color'] ?> opacity-0 group-hover:opacity-5 transition-opacity duration-300"></div>
              
              <!-- Badge -->
              <span class="absolute top-4 right-4 text-xs font-bold px-2.5 py-1 rounded-full <?= $badge_class ?>">
                <?= $feat['badge'] ?>
              </span>

              <!-- Icon -->
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br <?= $feat['color'] ?> flex items-center justify-center mb-4 shadow-lg shadow-teal-500/20">
                <i data-lucide="<?= $feat['icon'] ?>" class="w-6 h-6 text-white"></i>
              </div>

              <!-- Content -->
              <h3 class="text-xl font-bold text-slate-900 mb-2"><?= $feat['title'] ?></h3>
              <p class="text-slate-500 text-sm leading-relaxed"><?= $feat['desc'] ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- CTA debajo de features -->
        <div class="text-center mt-14 reveal">
          <div class="inline-flex items-center gap-3 bg-slate-100 rounded-full px-6 py-3">
            <div class="flex -space-x-2">
              <div class="w-8 h-8 rounded-full bg-teal-500 flex items-center justify-center text-white text-xs font-bold">11</div>
              <div class="w-8 h-8 rounded-full bg-white border-2 border-slate-200 flex items-center justify-center text-slate-400 text-xs">+</div>
            </div>
            <span class="text-slate-600 font-medium">módulos incluidos sin costo extra</span>
          </div>
          <p class="text-slate-400 text-sm mt-4">Nuevas funciones agregadas mensualmente sin aumento de precio</p>
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

        <div id="planes-container" class="mt-10 grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
          <!-- Los planes se cargan dinámicamente desde el API -->
          <div class="md:col-span-3 text-center py-12">
            <div class="inline-flex items-center gap-2 text-slate-400">
              <i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
              <span>Cargando planes...</span>
            </div>
          </div>
        </div>

        <p class="reveal text-center mt-8 text-xs text-slate-400">¿Tienes dudas? <a href="#faq" class="text-teal-600 font-semibold hover:underline">Lee las preguntas frecuentes</a> o <a href="<?= htmlspecialchars($login_url) ?>" class="text-teal-600 font-semibold hover:underline">contáctanos</a>.</p>
      </div>
    </section>

    <!-- ── FAQ ────────────────────────────────────────────────────────────── -->
    <section id="faq" class="py-24 max-w-5xl mx-auto px-5">
      <div class="text-center mb-12">
        <div class="badge-pill reveal mx-auto mb-5">Preguntas frecuentes</div>
        <h2 class="reveal delay-1 text-4xl font-extrabold text-slate-900">Todo lo que necesitas saber</h2>
        <p class="reveal delay-2 mt-4 text-slate-500">Si no encuentras tu respuesta, escríbenos.</p>
      </div>
      <div class="grid md:grid-cols-2 gap-4">
        <?php
        // Dividir FAQs en 2 columnas
        $faq_left = array_slice($faq_schema_items, 0, ceil(count($faq_schema_items)/2));
        $faq_right = array_slice($faq_schema_items, ceil(count($faq_schema_items)/2));
        ?>
        <div class="space-y-4">
          <?php foreach($faq_left as $i=>$f): ?>
          <details class="faq-item reveal delay-<?= ($i%3)+1 ?>">
            <summary>
              <span><?= htmlspecialchars($f['q']) ?></span>
              <i data-lucide="plus" class="faq-icon w-5 h-5"></i>
            </summary>
            <div class="faq-body"><?= htmlspecialchars($f['a']) ?></div>
          </details>
          <?php endforeach; ?>
        </div>
        <div class="space-y-4">
          <?php foreach($faq_right as $i=>$f): ?>
          <details class="faq-item reveal delay-<?= ($i%3)+1 ?>">
            <summary>
              <span><?= htmlspecialchars($f['q']) ?></span>
              <i data-lucide="plus" class="faq-icon w-5 h-5"></i>
            </summary>
            <div class="faq-body"><?= htmlspecialchars($f['a']) ?></div>
          </details>
          <?php endforeach; ?>
        </div>
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
  // Try-catch global para capturar cualquier error
  try {
    (function initApp() {
      'use strict';
      
      // Manejador de errores global
      window.addEventListener('error', function(e) {
        console.error('Global Error:', e.message, 'Line:', e.lineno);
        alert('Error JS: ' + e.message + ' en linea ' + e.lineno);
      });
      
      // ── Lucide icons ──────────────────────────────────────────────────────
      document.addEventListener('DOMContentLoaded', function() {
        if (window.lucide) lucide.createIcons();
      });
      window.addEventListener('load', function() {
        if (window.lucide) lucide.createIcons();
      });

      // ── Scroll progress ───────────────────────────────────────────────────
      var progressBar = document.getElementById('progress-bar');
      if (progressBar) {
        window.addEventListener('scroll', function() {
          var scrolled = window.scrollY / (document.body.scrollHeight - window.innerHeight) * 100;
          progressBar.style.width = Math.min(100, scrolled) + '%';
        }, { passive: true });
      }

      // ── Sticky header shadow ──────────────────────────────────────────────
      var header = document.getElementById('site-header');
      if (header) {
        window.addEventListener('scroll', function() {
          header.classList.toggle('scrolled', window.scrollY > 30);
        }, { passive: true });
      }

      // ── Floating CTA ──────────────────────────────────────────────────────
      var floatCta = document.getElementById('float-cta');
      if (floatCta) {
        window.addEventListener('scroll', function() {
          floatCta.classList.toggle('show', window.scrollY > 500);
        }, { passive: true });
      }

      // ── Spotlight cards ───────────────────────────────────────────────────
      document.querySelectorAll('.feat-card').forEach(function(card) {
        card.addEventListener('mousemove', function(e) {
          var r = card.getBoundingClientRect();
          card.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100) + '%');
          card.style.setProperty('--my', ((e.clientY - r.top)  / r.height * 100) + '%');
        });
      });

      // ── Intersection observer: reveal + counter ────────────────────────────
      var obs = new IntersectionObserver(function(entries) {
        entries.forEach(function(en) {
          if (!en.isIntersecting) return;
          var el = en.target;
          el.classList.add('visible');
          // counter
          if (el.dataset.counter && !el.dataset.counted) {
            el.dataset.counted = '1';
            var target = parseInt(el.dataset.counter, 10);
            var suffix = el.dataset.suffix || '';
            var start = performance.now();
            var dur = 1800;
            var tick = function(now) {
              var p = Math.min((now - start) / dur, 1);
              var ease = 1 - Math.pow(1 - p, 3);
              el.textContent = Math.round(target * ease) + suffix;
              if (p < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
          }
          obs.unobserve(el);
        });
      }, { threshold: 0.12 });

      document.querySelectorAll('.reveal,.reveal-left,.reveal-right,[data-counter]').forEach(function(el) {
        obs.observe(el);
      });

      // ── Roles tabs ────────────────────────────────────────────────────────
      document.querySelectorAll('.role-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
          document.querySelectorAll('.role-tab').forEach(function(t) { t.classList.remove('active'); });
          document.querySelectorAll('.role-panel').forEach(function(p) { p.classList.remove('active'); });
          tab.classList.add('active');
          var rolePanel = document.getElementById('role-' + tab.dataset.role);
          if (rolePanel) rolePanel.classList.add('active');
          if (window.lucide) lucide.createIcons();
        });
      });

      // ── Cargar planes dinámicamente desde API ────────────────────────────
      (function loadPlanes() {
        var apiUrl = <?= json_encode($join_url($base, 'api/public/planes.php')) ?>;
        var container = document.getElementById('planes-container');
        var loginUrl = <?= json_encode($login_url) ?>;

        fetch(apiUrl)
          .then(function(r) { return r.json(); })
          .then(function(res) {
            if (!res || !res.success || !Array.isArray(res.data) || res.data.length === 0) {
              container.innerHTML = '<div class="md:col-span-3 text-center py-8 text-slate-500">No hay planes disponibles en este momento.</div>';
              return;
            }

            var html = res.data.map(function(p, idx) {
              var isFree = p.precio_mensual <= 0;
              var isFeatured = idx === 1; // El del medio es destacado
              var featuredClass = isFeatured ? 'featured' : '';
              var badge = isFeatured ? '<span class="plan-badge">Más popular</span>' : '';

              // Precios
              var priceHtml;
              if (isFree) {
                priceHtml = '<div class="plan-price-val text-slate-900">Gratis</div><div class="text-xs text-slate-400 mt-1">Para siempre</div>';
              } else {
                priceHtml = '<div class="flex items-end gap-1.5">' +
                  '<span class="plan-price-val text-slate-900" data-price-monthly="' + p.precio_mensual + '" data-price-yearly="' + p.precio_anual + '">Q' + Math.round(p.precio_mensual).toLocaleString('es-GT') + '</span>' +
                  '<span class="text-slate-400 font-semibold mb-1.5 price-period">/mes</span>' +
                  '</div>' +
                  '<div class="text-xs text-slate-400 mt-1 plan-annual-note">' +
                  'O <strong class="text-teal-600">Q' + Math.round(p.precio_anual).toLocaleString('es-GT') + '</strong>/año (ahorra Q' + Math.round((p.precio_mensual * 12) - p.precio_anual).toLocaleString('es-GT') + ')' +
                  '</div>';
              }

              // Features
              var feats = [
                [p.max_sucursales + ' ' + (p.max_sucursales === 1 ? 'Sucursal' : 'Sucursales'), 'building-2'],
                [p.max_empleados + ' Profesionales', 'users'],
                [p.max_servicios + ' Servicios', 'list'],
                [p.max_clientes.toLocaleString('es-GT') + ' Clientes', 'user-check']
              ];
              var bonus = [];
              if (!isFree) bonus.push(['Blog integrado', 'file-text']);
              if (idx >= 2) bonus.push(['Sucursales ilimitadas disponibles', 'building']);

              var featsHtml = feats.concat(bonus).map(function(f) {
                return '<li class="plan-feat"><div class="w-4 h-4 rounded-full bg-teal-50 grid place-items-center flex-shrink-0"><i data-lucide="check" class="w-2.5 h-2.5 text-teal-600"></i></div><span class="text-slate-600">' + f[0] + '</span></li>';
              }).join('');

              if (isFree) {
                featsHtml += '<li class="plan-feat text-slate-400"><div class="w-4 h-4 rounded-full bg-slate-50 grid place-items-center flex-shrink-0"><i data-lucide="x" class="w-2.5 h-2.5 text-slate-300"></i></div><span>Blog (plan de pago)</span></li>';
              }

              var ctaText = isFree ? 'Comenzar gratis' : 'Elegir plan';

              return '<article class="plan-card reveal delay-' + (idx + 1) + ' ' + featuredClass + '">' +
                badge +
                '<div class="mb-6"><h3 class="text-xl font-extrabold text-slate-900">' + p.nombre + '</h3><p class="text-sm text-slate-500 mt-1">' + p.descripcion + '</p></div>' +
                '<div class="mb-8 pb-8 border-b border-slate-100">' + priceHtml + '</div>' +
                '<ul class="space-y-2.5 mb-8 flex-grow">' + featsHtml + '</ul>' +
                '<a href="' + loginUrl + '" class="plan-cta-primary">' + ctaText + '</a>' +
                '</article>';
            }).join('');

            container.innerHTML = html;
            if (window.lucide) lucide.createIcons();

            // Re-inicializar precios después de cargar
            initPriceToggle();
          })
          .catch(function(err) {
            console.error('Error cargando planes:', err);
            container.innerHTML = '<div class="md:col-span-3 text-center py-8 text-slate-500">Error al cargar los planes. Intenta recargar la página.</div>';
          });
      })();

      function initPriceToggle() {
      // ── Plans toggle monthly/annual ───────────────────────────────────────
      var btnM = document.getElementById('btn-mensual');
      var btnA = document.getElementById('btn-anual');
      var prices = document.querySelectorAll('[data-price-monthly]');
      var periods = document.querySelectorAll('.price-period');
      var annNotes = document.querySelectorAll('.plan-annual-note');

      if (btnM) {
        btnM.addEventListener('click', function() {
          btnM.classList.add('active'); 
          btnA.classList.remove('active');
          prices.forEach(function(el) {
            var v = parseFloat(el.dataset.priceMonthly) || 0;
            el.textContent = v <= 0 ? 'Gratis' : 'Q' + v.toLocaleString('es-GT');
          });
          periods.forEach(function(el) { el.textContent = '/mes'; });
          annNotes.forEach(function(el) { el.style.display = 'none'; });
        });
      }

      if (btnA) {
        btnA.addEventListener('click', function() {
          btnA.classList.add('active');
          btnM.classList.remove('active');
          prices.forEach(function(el) {
            var v = parseFloat(el.dataset.priceYearly) || 0;
            el.textContent = v <= 0 ? 'Gratis' : 'Q' + v.toLocaleString('es-GT');
          });
          periods.forEach(function(el) { el.textContent = '/año'; });
          annNotes.forEach(function(el) { el.style.display = ''; });
        });
      }
      } // end initPriceToggle

      // ── Hero mini-calendar ────────────────────────────────────────────────
      (function initHeroCalendar() {
        var MONTHS = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        var cursor = new Date();
        var selected = null;
        cursor.setDate(1);

        var label = document.getElementById('hc-month-label');
        var grid = document.getElementById('hc-days');
        var prevBtn = document.getElementById('hc-prev');
        var nextBtn = document.getElementById('hc-next');
        var info = document.getElementById('hc-selected-info');
        var infoTxt = document.getElementById('hc-selected-text');
        var times = document.querySelectorAll('.hc-time');

        if (!grid) return;

        function render() {
          var y = cursor.getFullYear();
          var m = cursor.getMonth();
          label.textContent = MONTHS[m] + ' ' + y;
          var today = new Date();
          today.setHours(0,0,0,0);
          var firstDay = new Date(y, m, 1).getDay();
          var daysInMonth = new Date(y, m+1, 0).getDate();
          var daysInPrev = new Date(y, m, 0).getDate();

          var html = '';
          // Prev month padding
          for (var i = firstDay - 1; i >= 0; i--) {
            html += '<div class="hc-day other-month">' + (daysInPrev - i) + '</div>';
          }
          // Current month
          for (var d = 1; d <= daysInMonth; d++) {
            var date = new Date(y, m, d);
            var isPast = date < today;
            var isToday = date.getTime() === today.getTime();
            var isSel = selected && date.getTime() === selected.getTime();
            var cls = 'hc-day';
            if (isPast) cls += ' disabled';
            if (isToday) cls += ' today';
            if (isSel) cls += ' selected';
            var dateStr = y + '-' + String(m+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
            html += '<div class="' + cls + '" data-date="' + dateStr + '">' + d + '</div>';
          }
          // Next month padding
          var total = firstDay + daysInMonth;
          var remaining = total % 7 === 0 ? 0 : 7 - (total % 7);
          for (var d2 = 1; d2 <= remaining; d2++) {
            html += '<div class="hc-day other-month">' + d2 + '</div>';
          }
          grid.innerHTML = html;

          // Bind day clicks
          grid.querySelectorAll('.hc-day:not(.disabled):not(.other-month)').forEach(function(el) {
            el.addEventListener('click', function() {
              var parts = el.dataset.date.split('-');
              selected = new Date(parseInt(parts[0]), parseInt(parts[1])-1, parseInt(parts[2]));
              render();
              times.forEach(function(t) { t.classList.remove('selected'); });
              info.classList.add('hidden');
            });
          });
        }

        if (prevBtn) {
          prevBtn.addEventListener('click', function() { cursor.setMonth(cursor.getMonth()-1); render(); });
        }
        if (nextBtn) {
          nextBtn.addEventListener('click', function() { cursor.setMonth(cursor.getMonth()+1); render(); });
        }

        times.forEach(function(t) {
          t.addEventListener('click', function() {
            times.forEach(function(x) { x.classList.remove('selected'); });
            t.classList.add('selected');
            if (selected) {
              var opts = { weekday:'long', year:'numeric', month:'long', day:'numeric' };
              infoTxt.textContent = selected.toLocaleDateString('es-GT', opts) + ' a las ' + t.dataset.t;
              info.classList.remove('hidden');
              if (window.lucide) lucide.createIcons();
            }
          });
        });

        render();
      })();

      // ── Contact form handler ─────────────────────────────────────────────
      var contactForm = document.getElementById('contactFormHome');
      if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
          e.preventDefault();
          e.stopPropagation();
          
          var btn = this.querySelector('button[type="submit"]');
          if (!btn) return;
          
          var originalText = btn.innerHTML;
          btn.disabled = true;
          btn.innerHTML = '<span class="inline-flex items-center gap-2"><svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="4" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Enviando...</span>';

          var form = this;
          var formData = new FormData(form);
          
          // Rate limiting frontend: 15 minutos = 900 segundos
          var RATE_LIMIT_SECONDS = 900;
          var lastSentKey = 'contact_last_sent';
          
          function checkRateLimit() {
            var lastSent = localStorage.getItem(lastSentKey);
            if (!lastSent) return { allowed: true };
            
            var now = Date.now();
            var elapsed = (now - parseInt(lastSent)) / 1000;
            var remaining = Math.ceil(RATE_LIMIT_SECONDS - elapsed);
            
            if (elapsed < RATE_LIMIT_SECONDS) {
              var minutes = Math.floor(remaining / 60);
              var seconds = remaining % 60;
              return { 
                allowed: false, 
                message: 'Has enviado un mensaje recientemente. Por favor espera ' + minutes + ' minutos' + (seconds > 0 ? ' y ' + seconds + ' segundos' : '') + ' antes de enviar otro.'
              };
            }
            return { allowed: true };
          }
          
          function setRateLimit() {
            localStorage.setItem(lastSentKey, Date.now().toString());
          }
          
          // Verificar rate limit antes de enviar
          var rateCheck = checkRateLimit();
          if (!rateCheck.allowed) {
            showRateLimitError(rateCheck.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
            return;
          }

          // Función para mostrar mensaje de rate limiting
          function showRateLimitError(message) {
            var container = document.getElementById('contactFormHome');
            if (!container) return;
            
            // Remover mensaje anterior si existe
            var existing = document.getElementById('rateLimitMessage');
            if (existing) existing.remove();
            
            // Crear mensaje de error
            var errorDiv = document.createElement('div');
            errorDiv.id = 'rateLimitMessage';
            errorDiv.className = 'mt-6 p-4 rounded-xl bg-red-50 border border-red-200';
            errorDiv.innerHTML = '<div class="flex items-start gap-3">' +
              '<div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">' +
                '<svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>' +
              '</div>' +
              '<div class="flex-1">' +
                '<p class="font-semibold text-red-900">' + message + '</p>' +
                '<p class="text-sm text-red-700 mt-2">Mientras tanto, puedes contactarnos directamente por:</p>' +
                '<div class="flex flex-wrap gap-3 mt-3">' +
                  '<a href="https://wa.me/50251036244" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">' +
                    '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>' +
                    'WhatsApp' +
                  '</a>' +
                  '<a href="mailto:info@reservasgp.com" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">' +
                    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>' +
                    'Email' +
                  '</a>' +
                '</div>' +
              '</div>' +
            '</div>';
            
            container.parentNode.insertBefore(errorDiv, container.nextSibling);
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }

          fetch('<?= $join_url($base, 'api/public/contacto-superadmin.php') ?>', {
            method: 'POST',
            body: formData,
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          }).then(function(response) {
            // Siempre intentar parsear JSON, incluso para errores
            return response.json().then(function(data) {
              return { ok: response.ok, status: response.status, data: data };
            }).catch(function() {
              // Si no es JSON válido, es error de servidor
              throw new Error('Respuesta no válida del servidor');
            });
          }).then(function(result) {
            var data = result.data;
            
            if (result.status === 429 || data.message && data.message.toLowerCase().indexOf('espera') > -1) {
              // Rate limit del backend o frontend
              showRateLimitError(data.message || 'Has enviado demasiados mensajes. Por favor espera antes de enviar otro.');
              return;
            }
            
            if (data.success) {
              setRateLimit(); // Guardar timestamp del envío exitoso
              form.reset();
              // Remover mensaje de error si existe
              var existingError = document.getElementById('rateLimitMessage');
              if (existingError) existingError.remove();
              // Mostrar éxito
              var successMsg = document.getElementById('successMessageHome');
              if (successMsg) {
                successMsg.classList.remove('hidden');
                successMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(function() { successMsg.classList.add('hidden'); }, 5000);
              }
            } else {
              // Error del backend (validación, etc.)
              if (data.message && data.message.indexOf('10 caracteres') > -1) {
                showRateLimitError(data.message); // Usar el mismo estilo para errores de validación
              } else {
                showRateLimitError(data.message || 'Error al enviar el mensaje. Intenta de nuevo.');
              }
            }
          }).catch(function(err) {
            console.error('Error enviando formulario:', err);
            showRateLimitError('Error de conexión. Revisa tu internet e intenta de nuevo, o contáctanos por WhatsApp o email.');
          }).finally(function() {
            btn.disabled = false;
            btn.innerHTML = originalText;
          });
        });
      }
      
    })();
  } catch (err) {
    console.error('Error crítico en JavaScript:', err);
    alert('Error crítico: ' + err.message);
    document.querySelectorAll('form').forEach(function(form) {
      form.setAttribute('novalidate', 'true');
    });
  }
  </script>
</body>
</html>
