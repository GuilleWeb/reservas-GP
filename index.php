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
$title = 'Sistema de Reservas GP | Agenda online para clínicas, salones, y negocios que ofrecen servicios';
$description = 'Agenda online multiempresa para administrar citas, sucursales, servicios, equipo y clientes en un solo sistema.';
$keywords = 'agenda online, sistema de citas, software para clinicas, reservas web, multiempresa, sucursales, gestion de citas, reservas en linea';

$canonical = rtrim($base, '/') . '/';
$logo_rel = $join_url($base, 'assets/logo.avif');
$logo_abs = rtrim($base, '/') . '/assets/logo.avif';
$hero_image = 'https://images.unsplash.com/photo-1666214280391-8ff5bd3c0bf0?auto=format,compress&fit=crop&w=1200&q=80';

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
    $plans = [];
  }
} catch (Throwable $e) {
  $plans = [];
}

if (!$plans) {
  $plans = [
    ['nombre' => 'Starter', 'descripcion' => 'Ideal para emprendedores.', 'max_sucursales' => 1, 'max_empleados' => 5, 'max_servicios' => 30, 'max_clientes' => 3000, 'precio' => 0, 'modulos_json' => '[]'],
    ['nombre' => 'Pro', 'descripcion' => 'Para negocios en crecimiento.', 'max_sucursales' => 3, 'max_empleados' => 20, 'max_servicios' => 80, 'max_clientes' => 15000, 'precio' => 100, 'modulos_json' => '[]'],
    ['nombre' => 'Scale', 'descripcion' => 'Empresas de alto volumen.', 'max_sucursales' => 10, 'max_empleados' => 60, 'max_servicios' => 200, 'max_clientes' => 50000, 'precio' => 190, 'modulos_json' => '[]']
  ];
}

// ESQUEMAS SEO COMPLETOS
$software_schema = [
  '@context' => 'https://schema.org',
  '@type' => 'SoftwareApplication',
  'name' => $site_name,
  'applicationCategory' => 'BusinessApplication',
  'operatingSystem' => 'Web',
  'url' => $canonical,
  'description' => $description,
  'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'GTQ']
];

$org_schema = [
  '@context' => 'https://schema.org',
  '@type' => 'Organization',
  'name' => $site_name,
  'url' => $canonical,
  'logo' => $logo_abs
];

$website_schema = [
  '@context' => 'https://schema.org',
  '@type' => 'WebSite',
  'name' => $site_name,
  'url' => $canonical
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
  <meta name="robots" content="index,follow,max-image-preview:large">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="preload" as="image" href="<?= htmlspecialchars($hero_image) ?>">

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  
  <style>
    :root { --brand: #0d9488; --brand-dark: #115e59; --bg-soft: #f0fdfa; }
    html { scroll-behavior: smooth; }
    body { margin: 0; color: #0f172a; font-family: 'Manrope', sans-serif; background: radial-gradient(circle at 15% 0%, rgba(45, 212, 191, .15), transparent 40%), linear-gradient(180deg, var(--bg-soft), #ffffff 20%, #f8fafc 100%); }
    h1, h2, h3 { font-family: 'Sora', sans-serif; letter-spacing: -.02em; }
    .glass { background: rgba(255, 255, 255, .75); backdrop-filter: blur(12px); }
    .reveal { opacity: 0; transform: translateY(30px); transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1); }
    .reveal.show { opacity: 1; transform: translateY(0); }
    .stagger-1 { transition-delay: 100ms; }
    .stagger-2 { transition-delay: 200ms; }
    .stagger-3 { transition-delay: 300ms; }
    .float-soft { animation: floatSoft 6s ease-in-out infinite; }
    @keyframes floatSoft { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
    .spotlight-card { position: relative; overflow: hidden; }
    .spotlight-card::before { content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(600px circle at var(--mouse-x) var(--mouse-y), rgba(45, 212, 191, 0.08), transparent 40%); opacity: 0; transition: opacity 0.3s ease; z-index: 0; pointer-events: none; }
    .spotlight-card:hover::before { opacity: 1; }
    .spotlight-content { position: relative; z-index: 1; }
  </style>

  <script type="application/ld+json"><?= json_encode($software_schema) ?></script>
  <script type="application/ld+json"><?= json_encode($org_schema) ?></script>
  <script type="application/ld+json"><?= json_encode($website_schema) ?></script>
</head>

<body>
  <header class="sticky top-0 z-40 border-b border-teal-100/60 glass">
    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
      <a href="<?= htmlspecialchars($canonical) ?>" class="flex items-center gap-3 group">
        <div class="h-10 w-10 rounded-xl bg-teal-600 flex items-center justify-center text-white font-bold transition-transform group-hover:rotate-6">GP</div>
        <span class="font-extrabold text-lg text-teal-900"><?= htmlspecialchars($site_name) ?></span>
      </a>
      <nav class="hidden md:flex items-center gap-8 text-sm font-bold text-slate-600">
        <a href="#beneficios" class="hover:text-teal-600">Beneficios</a>
        <a href="#caracteristicas" class="hover:text-teal-600">Características</a>
        <a href="#planes" class="hover:text-teal-600">Planes</a>
        <a href="#faq" class="hover:text-teal-600">FAQ</a>
      </nav>
      <a href="<?= htmlspecialchars($login_url) ?>" class="rounded-xl bg-slate-900 text-white px-5 py-2.5 text-sm font-bold shadow-md hover:bg-teal-700 transition-all active:scale-95">
        Acceso
      </a>
    </div>
  </header>

  <main>
    <section class="max-w-7xl mx-auto px-4 pt-16 pb-24 grid lg:grid-cols-2 gap-12 items-center">
      <div class="reveal">
        <div class="inline-flex items-center gap-2 text-[10px] font-extrabold tracking-widest uppercase bg-teal-50 text-teal-700 border border-teal-200 px-4 py-1.5 rounded-full mb-6">
          <span class="relative flex h-2 w-2"><span class="animate-ping absolute h-full w-full rounded-full bg-teal-400 opacity-75"></span><span class="relative rounded-full h-2 w-2 bg-teal-500"></span></span>
          Gestión inteligente
        </div>
        <h1 class="text-4xl sm:text-6xl font-extrabold leading-[1.1] text-slate-900">
          Agenda online que <span class="text-transparent bg-clip-text bg-gradient-to-r from-teal-600 to-cyan-500">potencia tu negocio.</span>
        </h1>
        <p class="mt-6 text-lg text-slate-600 max-w-lg">Centraliza sucursales, servicios y equipo. Profesionaliza la experiencia de reserva de tus clientes.</p>
        <div class="mt-10 flex flex-col sm:flex-row gap-4">
          <a href="#planes" class="bg-teal-600 text-white px-8 py-3.5 rounded-xl font-bold shadow-lg shadow-teal-600/20 hover:bg-teal-700 transition-all text-center">Ver planes</a>
          <a href="<?= htmlspecialchars($demo_url) ?>" class="border-2 border-slate-200 bg-white text-slate-700 px-8 py-3.5 rounded-xl font-bold hover:border-teal-600 hover:text-teal-700 transition-colors text-center flex items-center justify-center gap-2">
            <i data-lucide="play" class="w-4 h-4"></i> Demo en vivo
          </a>
        </div>
      </div>
      <div class="relative reveal stagger-2">
        <div class="float-soft absolute -top-10 -right-10 w-64 h-64 bg-teal-300/20 blur-3xl rounded-full"></div>
        <article class="relative rounded-[2rem] bg-white border border-slate-100 shadow-2xl overflow-hidden">
          <img src="<?= htmlspecialchars($hero_image) ?>" alt="Dashboard" class="w-full h-56 object-cover">
          <div class="p-8">
            <div class="flex justify-between mb-6">
              <h3 class="font-extrabold text-slate-900">Panel de Control</h3>
              <div class="flex gap-1"><div class="w-2 h-2 rounded-full bg-slate-200"></div><div class="w-2 h-2 rounded-full bg-teal-500"></div></div>
            </div>
            <div class="grid grid-cols-2 gap-4 text-center">
              <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Citas Hoy</p>
                <p class="text-3xl font-extrabold text-slate-900 mt-1" data-counter="46">0</p>
              </div>
              <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Conversión</p>
                <p class="text-3xl font-extrabold text-teal-600 mt-1" data-counter="84" data-suffix="%">0%</p>
              </div>
            </div>
          </div>
        </article>
      </div>
    </section>

    <section id="beneficios" class="py-24 bg-white border-y border-slate-100">
      <div class="max-w-7xl mx-auto px-4">
        <div class="text-center max-w-2xl mx-auto reveal">
          <h2 class="text-3xl font-extrabold text-slate-900">Diseñado para crecer</h2>
          <p class="mt-4 text-slate-600">Tu negocio merece una herramienta profesional que automatice las tareas repetitivas.</p>
        </div>
        <div class="mt-16 grid md:grid-cols-2 lg:grid-cols-4 gap-6">
          <?php 
          $cards = [
            ['icon' => 'building-2', 't' => 'Multisucursal', 'd' => 'Configura horarios y equipo de forma independiente por sede.'],
            ['icon' => 'zap', 't' => 'Reservas 24/7', 'd' => 'Tus clientes agendan en segundos desde cualquier dispositivo.'],
            ['icon' => 'users', 't' => 'Roles de Equipo', 'd' => 'Accesos controlados para dueños, gerentes y empleados.'],
            ['icon' => 'bar-chart-3', 't' => 'Métricas Reales', 'd' => 'Conoce tus servicios más vendidos y el rendimiento del personal.']
          ];
          foreach($cards as $i => $c): ?>
          <article class="spotlight-card reveal stagger-<?= $i+1 ?> rounded-2xl border border-slate-100 p-6 shadow-sm bg-white">
            <div class="spotlight-content">
              <div class="w-12 h-12 rounded-xl bg-teal-50 flex items-center justify-center text-teal-600 mb-5"><i data-lucide="<?= $c['icon'] ?>"></i></div>
              <h3 class="font-extrabold text-lg"><?= $c['t'] ?></h3>
              <p class="mt-2 text-sm text-slate-500"><?= $c['d'] ?></p>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section id="caracteristicas" class="py-24">
      <div class="max-w-7xl mx-auto px-4">
        <div class="reveal rounded-[2.5rem] bg-slate-900 text-white p-10 md:p-16 relative overflow-hidden shadow-2xl">
          <div class="absolute top-0 right-0 w-96 h-96 bg-teal-500/20 blur-[100px]"></div>
          <div class="relative z-10 grid lg:grid-cols-5 gap-12 items-center">
            <div class="lg:col-span-2">
              <h2 class="text-3xl font-extrabold">Control absoluto desde un solo lugar.</h2>
              <p class="mt-4 text-slate-400">Te entregamos una web lista para recibir citas, conectada a un panel administrativo potente y moderno.</p>
            </div>
            <div class="lg:col-span-3 grid sm:grid-cols-2 gap-4">
              <div class="bg-white/5 border border-white/10 p-5 rounded-2xl backdrop-blur-sm">
                <h4 class="font-bold text-teal-300">Página Web Pro</h4>
                <p class="text-xs text-slate-400 mt-1">Personalizada con tu marca, logo y link único.</p>
              </div>
              <div class="bg-white/5 border border-white/10 p-5 rounded-2xl backdrop-blur-sm">
                <h4 class="font-bold text-teal-300">Catálogo Dinámico</h4>
                <p class="text-xs text-slate-400 mt-1">Muestra servicios, precios y expertos disponibles.</p>
              </div>
              <div class="bg-white/5 border border-white/10 p-5 rounded-2xl backdrop-blur-sm">
                <h4 class="font-bold text-teal-300">Gestión de Reseñas</h4>
                <p class="text-xs text-slate-400 mt-1">Opiniones automáticas para generar confianza.</p>
              </div>
              <div class="bg-white/5 border border-white/10 p-5 rounded-2xl backdrop-blur-sm">
                <h4 class="font-bold text-teal-300">Seguridad de Datos</h4>
                <p class="text-xs text-slate-400 mt-1">Historial de clientes y citas 100% protegidos.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="planes" class="py-24 bg-slate-50 border-t border-slate-100">
      <div class="max-w-7xl mx-auto px-4">
        <div class="reveal text-center max-w-2xl mx-auto">
          <h2 class="text-3xl font-extrabold text-slate-900">Precios transparentes</h2>
          <p class="mt-4 text-slate-600">Elige el plan que mejor se adapte al tamaño de tu equipo.</p>
          <div class="mt-8 inline-flex bg-slate-200/60 rounded-full p-1 relative">
            <div id="toggle-bg" class="absolute w-1/2 h-full top-0 left-0 bg-white rounded-full shadow-sm transition-transform duration-300 border border-slate-200"></div>
            <button id="btn-mensual" class="relative z-10 px-6 py-2 text-xs font-bold text-slate-900">Mensual</button>
            <button id="btn-anual" class="relative z-10 px-6 py-2 text-xs font-bold text-slate-500">Anual <span class="bg-teal-100 text-teal-700 px-2 py-0.5 rounded-full text-[9px]">-15%</span></button>
          </div>
        </div>

        <div class="mt-14 grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
          <?php foreach ($plans as $idx => $p): 
            $pm = (float)$p['precio'];
            $pa = $pm > 0 ? ($pm * 12) * 0.85 : 0;
          ?>
          <article class="spotlight-card reveal stagger-<?= $idx+1 ?> rounded-3xl border <?= $idx==1?'border-teal-400 ring-4 ring-teal-50':'border-slate-200' ?> bg-white p-8 relative flex flex-col h-full">
            <div class="spotlight-content flex-grow">
              <?php if($idx==1): ?><span class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 text-[10px] font-extrabold bg-teal-600 text-white px-4 py-1 rounded-full">POPULAR</span><?php endif; ?>
              <h3 class="text-xl font-extrabold"><?= htmlspecialchars($p['nombre']) ?></h3>
              <div class="my-6">
                <span class="text-4xl font-extrabold text-slate-900" data-price-monthly="<?= $pm ?>" data-price-yearly="<?= $pa ?>">
                  <?= $pm<=0?'Gratis':'Q'.$pm ?>
                </span>
                <span class="text-xs font-bold text-slate-400 price-period">/mes</span>
              </div>
              <ul class="space-y-4 text-sm text-slate-600 mb-8">
                <li class="flex gap-3"><i data-lucide="check" class="w-4 h-4 text-teal-500"></i> <?= $p['max_sucursales'] ?> Sucursal</li>
                <li class="flex gap-3"><i data-lucide="check" class="w-4 h-4 text-teal-500"></i> <?= $p['max_empleados'] ?> Profesionales</li>
                <li class="flex gap-3"><i data-lucide="check" class="w-4 h-4 text-teal-500"></i> <?= $p['max_servicios'] ?> Servicios</li>
              </ul>
              <a href="<?= htmlspecialchars($login_url) ?>" class="w-full inline-block text-center py-3 rounded-xl font-bold transition-all <?= $idx==1?'bg-teal-600 text-white shadow-lg shadow-teal-600/20':'border-2 border-slate-200 text-slate-900 hover:border-teal-600' ?>">
                <?= $pm<=0?'Empezar gratis':'Contratar' ?>
              </a>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section id="faq" class="py-24 bg-white">
      <div class="max-w-3xl mx-auto px-4">
        <h2 class="reveal text-3xl font-extrabold text-center mb-10">Preguntas Frecuentes</h2>
        <div class="space-y-4">
          <?php
          $faqs = [
            ['q' => '¿Puedo usar mi propia marca?', 'a' => 'Sí, personaliza tu página con logo y colores corporativos.'],
            ['q' => '¿Necesitan descargar una App?', 'a' => 'No, funciona 100% en el navegador desde cualquier celular.'],
            ['q' => '¿Qué pasa si tengo varios locales?', 'a' => 'El sistema permite gestionar múltiples sedes desde un solo panel.'],
            ['q' => '¿La información está segura?', 'a' => 'Totalmente, usamos encriptación y roles de acceso para proteger tus datos.'],
            ['q' => '¿Hay plazos forzosos?', 'a' => 'No, puedes cancelar o cambiar de plan cuando quieras.']
          ];
          foreach($faqs as $f): ?>
          <details class="group reveal rounded-2xl border border-slate-200 bg-white open:shadow-sm">
            <summary class="flex cursor-pointer items-center justify-between p-6 font-bold text-slate-900"><?= $f['q'] ?> <i data-lucide="chevron-down" class="w-4 h-4 text-teal-600 group-open:rotate-180 transition"></i></summary>
            <div class="px-6 pb-6 text-sm text-slate-600"><?= $f['a'] ?></div>
          </details>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  </main>

  <footer class="bg-slate-900 text-slate-400 py-16">
    <div class="max-w-7xl mx-auto px-4 grid md:grid-cols-4 gap-10">
      <div class="md:col-span-2">
        <div class="flex items-center gap-2 mb-4"><div class="h-8 w-8 rounded-lg bg-teal-600 text-white flex items-center justify-center font-bold text-xs">GP</div><span class="text-white font-extrabold text-lg">GP Reservas</span></div>
        <p class="text-sm max-w-xs">Automatiza tu agenda y enfócate en lo que realmente importa: tus clientes.</p>
      </div>
      <div>
        <h4 class="text-white font-bold mb-4">Links</h4>
        <ul class="text-sm space-y-2">
          <li><a href="#beneficios" class="hover:text-teal-400">Beneficios</a></li>
          <li><a href="#planes" class="hover:text-teal-400">Precios</a></li>
          <li><a href="<?= htmlspecialchars($login_url) ?>" class="text-teal-500 font-bold">Acceso Admin</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-white font-bold mb-4">Legal</h4>
        <ul class="text-sm space-y-2">
          <li><a href="<?= htmlspecialchars($robots_url) ?>" class="hover:text-white">Robots</a></li>
          <li><a href="<?= htmlspecialchars($sitemap_url) ?>" class="hover:text-white">Sitemap</a></li>
        </ul>
      </div>
    </div>
    <div class="text-center text-[10px] text-slate-600 mt-16 pt-8 border-t border-slate-800">© <?= date('Y') ?> <?= $site_name ?></div>
  </footer>

  <script>
    // Toggle Precios
    const bM = document.getElementById('btn-mensual'), bA = document.getElementById('btn-anual'), tB = document.getElementById('toggle-bg');
    const prs = document.querySelectorAll('[data-price-monthly]'), pds = document.querySelectorAll('.price-period');

    bM.onclick = () => {
      tB.style.transform = 'translateX(0)';
      bM.classList.replace('text-slate-500', 'text-slate-900'); bA.classList.replace('text-slate-900', 'text-slate-500');
      prs.forEach(el => { const v = el.dataset.priceMonthly; el.textContent = v<=0?'Gratis':'Q'+v; });
      pds.forEach(el => el.textContent = '/mes');
    };
    bA.onclick = () => {
      tB.style.transform = 'translateX(100%)';
      bA.classList.replace('text-slate-500', 'text-slate-900'); bM.classList.replace('text-slate-900', 'text-slate-500');
      prs.forEach(el => { const v = el.dataset.priceYearly; el.textContent = v<=0?'Gratis':'Q'+v; });
      pds.forEach(el => el.textContent = '/año');
    };

    // Spotlight y Revelar
    document.querySelectorAll('.spotlight-card').forEach(c => {
      c.onmousemove = e => {
        const r = c.getBoundingClientRect();
        c.style.setProperty('--mouse-x', `${e.clientX - r.left}px`);
        c.style.setProperty('--mouse-y', `${e.clientY - r.top}px`);
      };
    });

    const obs = new IntersectionObserver(ents => {
      ents.forEach(e => {
        if(!e.isIntersecting) return;
        e.target.classList.add('show');
        if(e.target.dataset.counter && !e.target.dataset.counted){
          e.target.dataset.counted='1';
          const t = +e.target.dataset.counter, s = e.target.dataset.suffix||'', start = performance.now();
          function step(now){
            const p = Math.min((now-start)/1500,1), v = Math.round(t*(1-Math.pow(1-p,3)));
            e.target.textContent = v+s;
            if(p<1) requestAnimationFrame(step);
          }
          requestAnimationFrame(step);
        }
      });
    }, {threshold:0.1});
    document.querySelectorAll('.reveal, [data-counter]').forEach(el => obs.observe(el));
    window.onload = () => lucide.createIcons();
  </script>
</body>
</html>