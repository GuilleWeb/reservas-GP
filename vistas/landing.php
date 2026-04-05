<?php
// Landing desacoplada (sin bootstrap/topbar/footer compartidos).
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

$site_name = 'Sistema de Reservas GP';
$title = 'Sistema de Reservas GP | Agenda online para clínicas y negocios';
$description = 'Agenda online multiempresa para administrar citas, sucursales, servicios, equipo y clientes en un solo sistema.';
$keywords = 'agenda online, sistema de citas, software para clinicas, reservas web, multiempresa, sucursales, gestion de citas, reservas en linea';

$canonical = rtrim($base, '/') . '/';
$logo_rel = $join_url($base, 'assets/logo.avif');
$logo_abs = rtrim($base, '/') . '/assets/logo.avif';
$hero_image = 'https://images.unsplash.com/photo-1666214280391-8ff5bd3c0bf0?auto=format&fit=crop&w=1600&q=80';

$login_url = $join_url($base, 'vistas/public/login.php');
$demo_url = $join_url($base, 'vistas/public/inicio.php') . '?empresa=prueba';
$blog_url = $join_url($base, 'vistas/public/blog.php') . '?empresa=prueba';
$sedes_url = $join_url($base, 'vistas/public/ver-sedes.php') . '?empresa=prueba';
$citas_url = $join_url($base, 'vistas/public/citas.php') . '?empresa=prueba';
$robots_url = $join_url($base, 'robots.txt');
$sitemap_url = $join_url($base, 'sitemap.php');

$plans = [];
try {
  $dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    getenv('DB_HOST') ?: '127.0.0.1',
    (int) (getenv('DB_PORT') ?: 3306),
    getenv('DB_NAME') ?: 'citas_gp'
  );
  $pdo_lp = new PDO(
    $dsn,
    getenv('DB_USER') ?: 'root',
    getenv('DB_PASS') ?: '',
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
  try {
    $stmt = $pdo_lp->query('SELECT id, nombre, descripcion, max_sucursales, max_empleados, max_servicios, max_clientes, precio, modulos_json FROM planes WHERE activo = 1 ORDER BY id ASC LIMIT 6');
    $plans = $stmt->fetchAll() ?: [];
  } catch (Throwable $e2) {
    $stmt = $pdo_lp->query('SELECT id, nombre, descripcion, max_sucursales, max_empleados, max_servicios, max_clientes FROM planes WHERE activo = 1 ORDER BY id ASC LIMIT 6');
    $plans = $stmt->fetchAll() ?: [];
    foreach ($plans as &$pp) {
      $pp['precio'] = 0;
      $pp['modulos_json'] = '[]';
    }
    unset($pp);
  }
} catch (Throwable $e) {
  $plans = [];
}
if (!$plans) {
  $plans = [
    [
      'nombre' => 'Starter',
      'descripcion' => 'Comienza con todo lo esencial para operar.',
      'max_sucursales' => 1,
      'max_empleados' => 5,
      'max_servicios' => 30,
      'max_clientes' => 3000,
      'precio' => 0
    ],
    [
      'nombre' => 'Pro',
      'descripcion' => 'Más capacidad para equipos en crecimiento.',
      'max_sucursales' => 3,
      'max_empleados' => 20,
      'max_servicios' => 80,
      'max_clientes' => 15000,
      'precio' => 100
    ],
    [
      'nombre' => 'Scale',
      'descripcion' => 'Operación avanzada para empresas con volumen.',
      'max_sucursales' => 10,
      'max_empleados' => 60,
      'max_servicios' => 200,
      'max_clientes' => 50000,
      'precio' => 190
    ],
  ];
}

$software_schema = [
  '@context' => 'https://schema.org',
  '@type' => 'SoftwareApplication',
  'name' => $site_name,
  'applicationCategory' => 'BusinessApplication',
  'operatingSystem' => 'Web',
  'url' => $canonical,
  'description' => $description,
  'offers' => [
    '@type' => 'Offer',
    'price' => '0',
    'priceCurrency' => 'USD',
  ],
];

$org_schema = [
  '@context' => 'https://schema.org',
  '@type' => 'Organization',
  'name' => $site_name,
  'url' => $canonical,
  'logo' => $logo_abs,
];

$website_schema = [
  '@context' => 'https://schema.org',
  '@type' => 'WebSite',
  'name' => $site_name,
  'url' => $canonical,
  'inLanguage' => 'es',
];

$faq_schema = [
  '@context' => 'https://schema.org',
  '@type' => 'FAQPage',
  'mainEntity' => [
    [
      '@type' => 'Question',
      'name' => '¿Puedo usar mi marca y colores por empresa?',
      'acceptedAnswer' => [
        '@type' => 'Answer',
        'text' => 'Sí. Cada empresa puede tener nombre, logo, colores y configuración independiente.',
      ],
    ],
    [
      '@type' => 'Question',
      'name' => '¿La vista pública funciona con slug?',
      'acceptedAnswer' => [
        '@type' => 'Answer',
        'text' => 'Sí. Las vistas públicas trabajan con ?empresa=slug y las privadas con ?id_e=id.',
      ],
    ],
    [
      '@type' => 'Question',
      'name' => '¿Puedo probar ahora mismo?',
      'acceptedAnswer' => [
        '@type' => 'Answer',
        'text' => 'Sí. Puedes abrir la demo de la empresa prueba o iniciar sesión desde el acceso principal.',
      ],
    ],
  ],
];
?>
<!doctype html>
<html lang="es-MX">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($title) ?></title>
  <meta name="description" content="<?= htmlspecialchars($description) ?>">
  <meta name="keywords" content="<?= htmlspecialchars($keywords) ?>">
  <meta name="author" content="<?= htmlspecialchars($site_name) ?>">
  <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
  <meta name="googlebot" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
  <meta name="theme-color" content="#0d9488">
  <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
  <link rel="sitemap" type="application/xml" title="Sitemap" href="<?= htmlspecialchars($sitemap_url) ?>">
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($logo_rel) ?>">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars($logo_rel) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="preconnect" href="https://images.unsplash.com" crossorigin>
  <link rel="preload" as="image" href="<?= htmlspecialchars($hero_image) ?>">

  <meta property="og:type" content="website">
  <meta property="og:locale" content="es_MX">
  <meta property="og:site_name" content="<?= htmlspecialchars($site_name) ?>">
  <meta property="og:title" content="<?= htmlspecialchars($title) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($description) ?>">
  <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($hero_image) ?>">
  <meta property="og:image:secure_url" content="<?= htmlspecialchars($hero_image) ?>">
  <meta property="og:image:type" content="image/jpeg">
  <meta property="og:image:width" content="1600">
  <meta property="og:image:height" content="900">
  <meta property="og:image:alt" content="Sistema de Reservas GP - agenda online">

  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= htmlspecialchars($title) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($description) ?>">
  <meta name="twitter:image" content="<?= htmlspecialchars($hero_image) ?>">

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    :root {
      --brand: #0d9488;
      --brand-dark: #115e59;
      --bg-soft: #f0fdfa;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      margin: 0;
      color: #0f172a;
      font-family: 'Manrope', system-ui, sans-serif;
      background:
        radial-gradient(circle at 15% 0%, rgba(45, 212, 191, .25), transparent 38%),
        radial-gradient(circle at 100% 0%, rgba(20, 184, 166, .20), transparent 35%),
        linear-gradient(180deg, var(--bg-soft), #ffffff 30%, #f8fafc 100%);
    }

    h1,
    h2,
    h3 {
      font-family: 'Sora', 'Manrope', sans-serif;
      letter-spacing: -.01em;
    }

    .glass {
      background: rgba(255, 255, 255, .75);
      backdrop-filter: blur(10px);
    }

    .reveal {
      opacity: 0;
      transform: translateY(20px);
      transition: transform .7s ease, opacity .7s ease;
    }

    .reveal.show {
      opacity: 1;
      transform: translateY(0);
    }

    .float-soft {
      animation: floatSoft 7s ease-in-out infinite;
    }

    @keyframes floatSoft {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }

    .gradient-border {
      position: relative;
    }

    .gradient-border::before {
      content: '';
      position: absolute;
      inset: -1px;
      z-index: -1;
      border-radius: 1.5rem;
      background: linear-gradient(120deg, rgba(13, 148, 136, .6), rgba(45, 212, 191, .3), rgba(56, 189, 248, .35));
    }
  </style>
  <script type="application/ld+json"><?= json_encode($software_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
  <script type="application/ld+json"><?= json_encode($org_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
  <script type="application/ld+json"><?= json_encode($website_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
  <script type="application/ld+json"><?= json_encode($faq_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</head>

<body>
  <header class="sticky top-0 z-40 border-b border-teal-100/80 glass">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <a href="<?= htmlspecialchars($canonical) ?>" class="flex items-center gap-3">
        <img src="<?= htmlspecialchars($logo_rel) ?>" alt="<?= htmlspecialchars($site_name) ?>" class="h-10 w-10 rounded-full object-cover ring-2 ring-teal-100">
        <span class="font-extrabold text-lg text-teal-800"><?= htmlspecialchars($site_name) ?></span>
      </a>
      <nav class="hidden md:flex items-center gap-6 text-sm font-semibold text-slate-700">
        <a href="#beneficios" class="hover:text-teal-700 transition">Beneficios</a>
        <a href="#modulos" class="hover:text-teal-700 transition">Módulos</a>
        <a href="#planes" class="hover:text-teal-700 transition">Planes</a>
        <a href="#faq" class="hover:text-teal-700 transition">FAQ</a>
      </nav>
      <a href="<?= htmlspecialchars($login_url) ?>" class="inline-flex items-center justify-center rounded-xl bg-teal-600 text-white px-4 py-2 text-sm font-bold shadow hover:bg-teal-700 transition">
        Iniciar sesión
      </a>
    </div>
  </header>

  <main>
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 pb-20">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
        <div class="reveal">
          <p class="inline-flex items-center gap-2 text-xs font-extrabold tracking-widest uppercase bg-teal-50 text-teal-700 border border-teal-100 px-3 py-1 rounded-full">
            <i data-lucide="sparkles" class="w-4 h-4"></i> listo para crecer
          </p>
          <h1 class="mt-5 text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-tight">
            Agenda online moderna para convertir visitas en ventas.
          </h1>
          <p class="mt-5 text-lg text-slate-600 max-w-xl">
            Administra citas, sucursales, servicios, empleados y clientes en una plataforma profesional.
            Tu operación diaria más simple, rápida y escalable.
          </p>
          <div class="mt-8 flex flex-col sm:flex-row gap-3">
            <a href="<?= htmlspecialchars($login_url) ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-600 text-white px-6 py-3 font-bold shadow-lg shadow-teal-200 hover:bg-teal-700 transition">
              <i data-lucide="log-in" class="w-4 h-4"></i> Entrar al sistema
            </a>
            <a href="<?= htmlspecialchars($demo_url) ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-teal-200 bg-white text-teal-800 px-6 py-3 font-bold hover:bg-teal-50 transition">
              <i data-lucide="play-circle" class="w-4 h-4"></i> Ver demo "prueba"
            </a>
          </div>
          <div class="mt-8 grid grid-cols-3 gap-3 max-w-md">
            <div class="rounded-xl bg-white border border-slate-100 p-3 text-center shadow-sm">
              <div class="text-2xl font-extrabold text-teal-700" data-counter="99" data-suffix="%">0%</div>
              <div class="text-xs text-slate-500">Disponibilidad</div>
            </div>
            <div class="rounded-xl bg-white border border-slate-100 p-3 text-center shadow-sm">
              <div class="text-2xl font-extrabold text-teal-700" data-counter="6">0</div>
              <div class="text-xs text-slate-500">Roles listos</div>
            </div>
            <div class="rounded-xl bg-white border border-slate-100 p-3 text-center shadow-sm">
              <div class="text-2xl font-extrabold text-teal-700" data-counter="24" data-suffix="/7">0</div>
              <div class="text-xs text-slate-500">Reservas web</div>
            </div>
          </div>
        </div>

        <div class="relative reveal">
          <div class="float-soft absolute -top-8 -right-5 w-44 h-44 rounded-full bg-teal-200/60 blur-3xl"></div>
          <div class="float-soft absolute -bottom-8 -left-5 w-44 h-44 rounded-full bg-cyan-200/50 blur-3xl"></div>
          <article class="gradient-border relative rounded-3xl bg-white shadow-2xl overflow-hidden">
            <div class="h-1.5 bg-gradient-to-r from-teal-400 via-emerald-400 to-cyan-400"></div>
            <img
              src="<?= htmlspecialchars($hero_image) ?>"
              alt="Equipo gestionando agenda de citas en sistema moderno"
              class="w-full h-52 object-cover"
              loading="eager"
              fetchpriority="high"
              decoding="async">
            <div class="p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs uppercase tracking-widest text-slate-400 font-extrabold">panel inteligente</p>
                  <p class="text-xl font-extrabold text-slate-900">Vista operativa</p>
                </div>
                <span class="text-xs px-2 py-1 rounded-full bg-teal-50 text-teal-700 font-bold">Live</span>
              </div>
              <div class="mt-5 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-4">
                  <p class="text-slate-500">Citas hoy</p>
                  <p class="text-2xl font-extrabold">46</p>
                </div>
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-4">
                  <p class="text-slate-500">Servicios</p>
                  <p class="text-2xl font-extrabold">18</p>
                </div>
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-4">
                  <p class="text-slate-500">Sucursales</p>
                  <p class="text-2xl font-extrabold">3</p>
                </div>
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-4">
                  <p class="text-slate-500">Clientes</p>
                  <p class="text-2xl font-extrabold">1,284</p>
                </div>
              </div>
              <p class="mt-4 rounded-xl border border-teal-100 bg-teal-50 p-4 text-sm text-teal-900">
                Tu cliente reserva en minutos y tu equipo administra todo en un solo flujo.
              </p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section id="beneficios" class="py-16 border-y border-slate-100 bg-white/70">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="reveal text-3xl sm:text-4xl font-extrabold text-center">Hecho para operar y crecer</h2>
        <p class="reveal text-center text-slate-600 mt-3">Una base sólida para negocios de servicios que quieren verse y operar de forma profesional.</p>
        <div class="mt-10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <article class="reveal rounded-2xl border border-slate-100 bg-white p-5 shadow-sm hover:shadow-md transition">
            <i data-lucide="building-2" class="w-6 h-6 text-teal-600"></i>
            <h3 class="mt-4 font-extrabold">Multisucursal real</h3>
            <p class="mt-2 text-sm text-slate-600">Cada Empresa/sucursal tiene branding, configuración y datos independientes.</p>
          </article>
          <article class="reveal rounded-2xl border border-slate-100 bg-white p-5 shadow-sm hover:shadow-md transition">
            <i data-lucide="calendar-check-2" class="w-6 h-6 text-teal-600"></i>
            <h3 class="mt-4 font-extrabold">Reserva sin fricción</h3>
            <p class="mt-2 text-sm text-slate-600">Flujo público optimizado para elevar conversión de citas.</p>
          </article>
          <article class="reveal rounded-2xl border border-slate-100 bg-white p-5 shadow-sm hover:shadow-md transition">
            <i data-lucide="shield-check" class="w-6 h-6 text-teal-600"></i>
            <h3 class="mt-4 font-extrabold">Roles y control</h3>
            <p class="mt-2 text-sm text-slate-600">Superadmin, admin, sucursal, empleado y cliente con permisos claros.</p>
          </article>
          <article class="reveal rounded-2xl border border-slate-100 bg-white p-5 shadow-sm hover:shadow-md transition">
            <i data-lucide="line-chart" class="w-6 h-6 text-teal-600"></i>
            <h3 class="mt-4 font-extrabold">Operación medible</h3>
            <p class="mt-2 text-sm text-slate-600">Control de agenda, estados y actividad del negocio.</p>
          </article>
        </div>
      </div>
    </section>

    <section id="modulos" class="py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal rounded-3xl bg-gradient-to-br from-teal-700 to-teal-900 text-white p-8 md:p-10 shadow-2xl">
          <h2 class="text-3xl sm:text-4xl font-extrabold">Módulos incluidos</h2>
          <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
            <div class="rounded-xl bg-white/10 border border-white/20 p-4">Gestión de empresas y planes</div>
            <div class="rounded-xl bg-white/10 border border-white/20 p-4">Usuarios y permisos por rol</div>
            <div class="rounded-xl bg-white/10 border border-white/20 p-4">Servicios, sucursales y equipo</div>
            <div class="rounded-xl bg-white/10 border border-white/20 p-4">Agenda de citas con estados</div>
            <div class="rounded-xl bg-white/10 border border-white/20 p-4">Clientes y seguimiento</div>
            <div class="rounded-xl bg-white/10 border border-white/20 p-4">Home pública por empresa</div>
          </div>
          <div class="mt-8">
            <a href="<?= htmlspecialchars($login_url) ?>" class="inline-flex items-center justify-center rounded-xl bg-white text-teal-800 px-6 py-3 font-extrabold hover:bg-teal-50 transition">
              Comenzar ahora
            </a>
          </div>
        </div>
      </div>
    </section>

    <section id="planes" class="py-16 border-t border-slate-100 bg-gradient-to-b from-white to-teal-50/40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal text-center">
          <h2 class="text-3xl sm:text-4xl font-extrabold">Planes</h2>
          <p class="mt-3 text-slate-600">Elige el plan que mejor se adapta a tu operación actual y escala cuando lo necesites.</p>
        </div>
        <div class="mt-10 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
          <?php foreach ($plans as $idx => $p): ?>
            <article class="reveal rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-lg transition relative overflow-hidden">
              <?php if ($idx === 1): ?>
                <span class="absolute top-3 right-3 text-[11px] font-extrabold bg-teal-600 text-white px-2 py-1 rounded-full">Más elegido</span>
              <?php endif; ?>
              <div class="text-sm text-teal-700 font-extrabold uppercase tracking-wider">Plan <?= htmlspecialchars((string) ($p['nombre'] ?? '')) ?></div>
              <?php $precioPlan = isset($p['precio']) ? (float) $p['precio'] : 0.0; ?>
              <div class="mt-2 text-3xl font-extrabold text-slate-900">
                <?= $precioPlan <= 0 ? 'Gratis' : ('Q' . number_format($precioPlan, 2) . '/mes') ?>
              </div>
              <p class="mt-2 text-sm text-slate-600 min-h-10"><?= htmlspecialchars((string) ($p['descripcion'] ?? '')) ?></p>
              <ul class="mt-5 space-y-2 text-sm text-slate-700">
                <li class="flex items-center gap-2"><i data-lucide="check-circle-2" class="w-4 h-4 text-teal-600"></i>Hasta <?= (int) ($p['max_sucursales'] ?? 0) ?> sucursales</li>
                <li class="flex items-center gap-2"><i data-lucide="check-circle-2" class="w-4 h-4 text-teal-600"></i>Hasta <?= (int) ($p['max_empleados'] ?? 0) ?> empleados</li>
                <li class="flex items-center gap-2"><i data-lucide="check-circle-2" class="w-4 h-4 text-teal-600"></i>Hasta <?= (int) ($p['max_servicios'] ?? 0) ?> servicios</li>
                <li class="flex items-center gap-2"><i data-lucide="check-circle-2" class="w-4 h-4 text-teal-600"></i>Hasta <?= (int) ($p['max_clientes'] ?? 0) ?> clientes</li>
                <?php
                  $mods = json_decode((string) ($p['modulos_json'] ?? '[]'), true);
                  if (!is_array($mods)) { $mods = []; }
                  $mods = array_slice(array_values(array_filter(array_map('strval', $mods))), 0, 3);
                  foreach ($mods as $m):
                ?>
                  <li class="flex items-center gap-2"><i data-lucide="check-circle-2" class="w-4 h-4 text-teal-600"></i>Módulo: <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $m))) ?></li>
                <?php endforeach; ?>
              </ul>
              <a href="<?= htmlspecialchars($login_url) ?>" class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-slate-900 text-white py-2.5 font-bold hover:bg-slate-800 transition">
                Empezar gratis
              </a>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section id="faq" class="py-16 bg-white/70 border-t border-slate-100">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="reveal text-3xl sm:text-4xl font-extrabold text-center">Preguntas frecuentes</h2>
        <div class="mt-8 space-y-3">
          <details class="reveal rounded-xl border border-slate-200 bg-white p-4 open:shadow-sm">
            <summary class="cursor-pointer font-bold text-slate-900">¿Puedo usar mi marca y colores por empresa?</summary>
            <p class="mt-2 text-sm text-slate-600">Sí. Cada empresa puede tener nombre, logo, colores y configuración independiente.</p>
          </details>
          <details class="reveal rounded-xl border border-slate-200 bg-white p-4 open:shadow-sm">
            <summary class="cursor-pointer font-bold text-slate-900">¿La vista pública funciona con slug?</summary>
            <p class="mt-2 text-sm text-slate-600">Sí. Las vistas públicas trabajan con `?empresa=slug` y las privadas con `?id_e=id`.</p>
          </details>
          <details class="reveal rounded-xl border border-slate-200 bg-white p-4 open:shadow-sm">
            <summary class="cursor-pointer font-bold text-slate-900">¿Puedo probar ahora mismo?</summary>
            <p class="mt-2 text-sm text-slate-600">Sí. Puedes abrir la demo de la empresa `prueba` o iniciar sesión desde el acceso principal.</p>
          </details>
        </div>
      </div>
    </section>
  </main>

  <footer class="border-t border-teal-100 bg-white/90">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 grid grid-cols-1 md:grid-cols-3 gap-8 text-sm text-slate-600">
      <div>
        <div class="flex items-center gap-2">
          <img src="<?= htmlspecialchars($logo_rel) ?>" alt="<?= htmlspecialchars($site_name) ?>" class="h-7 w-7 rounded-full object-cover">
          <span class="font-extrabold text-slate-900"><?= htmlspecialchars($site_name) ?></span>
        </div>
        <p class="mt-3">Software de reservas para negocios de servicios con enfoque en conversión, orden operativo y experiencia del cliente.</p>
      </div>
      <div>
        <div class="font-extrabold text-slate-900">Navegación</div>
        <ul class="mt-3 space-y-2">
          <li><a href="<?= htmlspecialchars($canonical) ?>" class="hover:text-teal-700">Inicio</a></li>
          <li><a href="<?= htmlspecialchars($login_url) ?>" class="hover:text-teal-700">Login</a></li>
          <li><a href="<?= htmlspecialchars($demo_url) ?>" class="hover:text-teal-700">Demo empresa prueba</a></li>
          <li><a href="<?= htmlspecialchars($blog_url) ?>" class="hover:text-teal-700">Blog demo</a></li>
        </ul>
      </div>
      <div>
        <div class="font-extrabold text-slate-900">SEO técnico</div>
        <ul class="mt-3 space-y-2">
          <li><a href="<?= htmlspecialchars($robots_url) ?>" class="hover:text-teal-700">Robots</a></li>
          <li><a href="<?= htmlspecialchars($sitemap_url) ?>" class="hover:text-teal-700">Sitemap</a></li>
          <li><a href="<?= htmlspecialchars($sedes_url) ?>" class="hover:text-teal-700">Sedes públicas</a></li>
          <li><a href="<?= htmlspecialchars($citas_url) ?>" class="hover:text-teal-700">Reservar cita</a></li>
        </ul>
      </div>
    </div>
    <div class="border-t border-slate-100 py-4 text-center text-xs text-slate-500">
      © <?= date('Y') ?> <?= htmlspecialchars($site_name) ?>. Todos los derechos reservados.
    </div>
  </footer>

  <script>
    function animateCounter(el) {
      const target = Number(el.dataset.counter || 0);
      const suffix = el.dataset.suffix || '';
      const duration = 1200;
      const startTime = performance.now();

      function step(now) {
        const progress = Math.min((now - startTime) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const value = Math.round(target * eased);
        el.textContent = `${value}${suffix}`;
        if (progress < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('show');
        if (entry.target.dataset.counter && !entry.target.dataset.counted) {
          entry.target.dataset.counted = '1';
          animateCounter(entry.target);
        }
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.2 });

    document.querySelectorAll('.reveal, [data-counter]').forEach((el) => observer.observe(el));

    if (window.lucide) {
      lucide.createIcons();
    }
  </script>
</body>

</html>
