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
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $site_name ?> | Gestión Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; overflow-x: hidden; }
        
        /* Efecto Figma: Glassmorphism */
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.3); }
        
        /* Animación de entrada */
        .reveal { opacity: 0; transform: translateY(20px); transition: all 0.6s cubic-bezier(0.22, 1, 0.36, 1); }
        .reveal.active { opacity: 1; transform: translateY(0); }

        /* Spotlight Card */
        .spotlight { position: relative; transition: transform 0.3s ease; }
        .spotlight:hover { transform: translateY(-5px); }
        .spotlight::after {
            content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at var(--x, 50%) var(--y, 50%), rgba(20, 184, 166, 0.1) 0%, transparent 80%);
            opacity: 0; transition: opacity 0.3s; pointer-events: none; border-radius: inherit;
        }
        .spotlight:hover::after { opacity: 1; }

        /* Background animado sutil */
        .bg-animate {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;
            background: radial-gradient(circle at 0% 0%, #f0fdfa 0%, transparent 50%), radial-gradient(circle at 100% 100%, #ecfeff 0%, transparent 50%);
        }
    </style>
</head>
<body>
    <div class="bg-animate"></div>

    <header class="sticky top-0 z-50 h-16 glass flex items-center justify-between px-6 md:px-12">
        <div class="flex items-center gap-2">
            <div class="bg-teal-600 text-white p-2 rounded-lg font-bold">GP</div>
            <span class="font-extrabold text-slate-900 tracking-tight">Reservas GP</span>
        </div>
        <a href="<?= $login_url ?>" class="bg-slate-900 text-white px-5 py-2 rounded-full font-bold text-sm hover:scale-105 transition-transform">Entrar</a>
    </header>

    <main class="max-w-7xl mx-auto px-6">
        <section class="py-20 grid lg:grid-cols-2 gap-12 items-center">
            <div class="reveal active">
                <h1 class="text-5xl md:text-7xl font-extrabold text-slate-900 leading-tight">
                    Tu agenda <br><span class="text-teal-600 italic">automatizada.</span>
                </h1>
                <p class="mt-6 text-lg text-slate-600 max-w-md">El motor de reservas para empresas modernas. Controla sucursales, empleados y citas en un solo lugar.</p>
                <div class="mt-10 flex gap-4">
                    <a href="#planes" class="bg-teal-600 text-white px-8 py-4 rounded-2xl font-bold shadow-xl shadow-teal-600/20 hover:bg-teal-700 transition-all">Ver Planes</a>
                    <a href="<?= $demo_url ?>" class="bg-white border border-slate-200 px-8 py-4 rounded-2xl font-bold hover:bg-slate-50 transition-all flex items-center gap-2"><i data-lucide="external-link" class="w-4 h-4"></i> Demo</a>
                </div>
            </div>
            <div class="relative reveal active" style="transition-delay: 0.2s">
                <div class="absolute -z-10 bg-teal-200/50 w-full h-full blur-3xl rounded-full"></div>
                <img src="<?= $hero_image ?>" class="rounded-[2.5rem] shadow-2xl border-4 border-white object-cover h-96 w-full" alt="Dashboard">
            </div>
        </section>

        <section id="planes" class="py-20">
            <div class="text-center reveal">
                <h2 class="text-3xl font-extrabold">Elige tu crecimiento</h2>
                <div class="mt-6 inline-flex p-1 bg-slate-200 rounded-xl relative w-64">
                    <div id="toggle-bg" class="absolute inset-y-1 left-1 w-[calc(50%-4px)] bg-white rounded-lg shadow transition-all duration-300"></div>
                    <button id="btn-m" class="relative z-10 flex-1 py-2 font-bold text-sm">Mensual</button>
                    <button id="btn-a" class="relative z-10 flex-1 py-2 font-bold text-sm text-slate-500">Anual</button>
                </div>
            </div>

            <div class="mt-12 grid md:grid-cols-3 gap-8">
                <?php foreach($plans as $i => $p): ?>
                <div class="spotlight reveal glass p-8 rounded-[2rem] flex flex-col h-full" style="transition-delay: <?= $i * 0.1 ?>s">
                    <h3 class="text-xl font-bold text-slate-900"><?= $p['nombre'] ?></h3>
                    <div class="mt-4 flex items-baseline gap-1">
                        <span class="text-4xl font-extrabold" data-p-m="<?= $p['precio'] ?>" data-p-a="<?= $p['precio']*12*0.85 ?>">Q<?= $p['precio'] ?></span>
                        <span class="text-slate-500 text-sm" id="period">/mes</span>
                    </div>
                    <ul class="mt-8 space-y-4 flex-grow text-slate-600 text-sm">
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-teal-600"></i> <?= $p['max_sucursales'] ?> Sucursal</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-teal-600"></i> <?= $p['max_empleados'] ?> Empleados</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-teal-600"></i> Pagina Web Pública</li>
                    </ul>
                    <a href="<?= $login_url ?>" class="mt-10 block text-center py-4 bg-slate-900 text-white rounded-2xl font-bold hover:bg-teal-600 transition-colors">Empezar</a>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <script>
        // Inicializar Iconos
        lucide.createIcons();

        // Spotlight Mouse Effect
        document.querySelectorAll('.spotlight').forEach(card => {
            card.addEventListener('mousemove', e => {
                const rect = card.getBoundingClientRect();
                card.style.setProperty('--x', `${e.clientX - rect.left}px`);
                card.style.setProperty('--y', `${e.clientY - rect.top}px`);
            });
        });

        // Toggle Lógica
        const btnM = document.getElementById('btn-m'), btnA = document.getElementById('btn-a'), tBg = document.getElementById('toggle-bg');
        const prices = document.querySelectorAll('[data-p-m]');

        btnA.onclick = () => {
            tBg.style.transform = 'translateX(100%)';
            btnA.classList.remove('text-slate-500'); btnM.classList.add('text-slate-500');
            prices.forEach(p => p.innerText = 'Q' + Math.floor(p.dataset.pA));
            document.querySelectorAll('#period').forEach(p => p.innerText = '/año');
        };
        btnM.onclick = () => {
            tBg.style.transform = 'translateX(0)';
            btnM.classList.remove('text-slate-500'); btnA.classList.add('text-slate-500');
            prices.forEach(p => p.innerText = 'Q' + p.dataset.pM);
            document.querySelectorAll('#period').forEach(p => p.innerText = '/mes');
        };

        // Reveal on Scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(e => { if(e.isIntersecting) e.target.classList.add('active'); });
        }, { threshold: 0.1 });
        document.querySelectorAll('.reveal').forEach(r => observer.observe(r));
    </script>
</body>
</html>