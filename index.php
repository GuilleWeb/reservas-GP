<?php
// Mantenemos toda tu lógica de rutas y conexión de DB intacta
$script_name = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$base_path = trim(dirname($script_name), '/');
$base_path = ($base_path === '.' || $base_path === '') ? '' : '/' . $base_path;
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
$scheme = $is_https ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = rtrim($scheme . '://' . $host, '/');
if ($path_prefix = trim((string) $base_path, '/')) { $base .= '/' . $path_prefix; }
$join_url = static function (string $baseUrl, string $path): string { return rtrim($baseUrl, '/') . '/' . ltrim($path, '/'); };

$site_name = 'Sistema GP';
$login_url = $join_url($base, 'vistas/public/login.php');
$demo_url = $join_url($base, 'vistas/public/inicio.php') . '?empresa=prueba';
$hero_image = 'https://images.unsplash.com/photo-1666214280391-8ff5bd3c0bf0?auto=format,compress&fit=crop&w=1200&q=80';

// RECUPERANDO TUS PLANES DE DB
$plans = [];
try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST') ?: '127.0.0.1', getenv('DB_NAME') ?: 'citas_gp');
    $pdo = new PDO($dsn, getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $plans = $pdo->query('SELECT * FROM planes WHERE activo = 1 ORDER BY precio ASC')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $plans = [['nombre'=>'Básico','precio'=>0,'max_sucursales'=>1,'max_empleados'=>5],['nombre'=>'Pro','precio'=>100,'max_sucursales'=>3,'max_empleados'=>20]];
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Reservas GP | Dashboard Profesional</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #fbfcfd; color: #0f172a; scroll-behavior: smooth; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(14px); border: 1px solid rgba(255, 255, 255, 0.4); }
        .reveal { opacity: 0; transform: translateY(30px); transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1); }
        .reveal.active { opacity: 1; transform: translateY(0); }
        .spotlight { position: relative; overflow: hidden; }
        .spotlight::before { content: ""; position: absolute; inset: 0; background: radial-gradient(circle at var(--x) var(--y), rgba(20, 184, 166, 0.12), transparent 40%); opacity: 0; transition: opacity 0.3s; pointer-events: none; }
        .spotlight:hover::before { opacity: 1; }
        .bg-grid { background-image: radial-gradient(#e2e8f0 1px, transparent 1px); background-size: 30px 30px; }
    </style>
</head>
<body class="bg-grid">

    <header class="sticky top-0 z-50 h-20 glass flex items-center justify-between px-6 md:px-16 border-b border-slate-200/50">
        <div class="flex items-center gap-3">
            <div class="h-11 w-11 bg-teal-600 rounded-2xl flex items-center justify-center text-white font-black shadow-lg shadow-teal-600/30">GP</div>
            <span class="text-xl font-extrabold tracking-tighter italic">RESERVAS</span>
        </div>
        <nav class="hidden lg:flex gap-10 text-sm font-bold text-slate-500">
            <a href="#beneficios" class="hover:text-teal-600 transition-colors">Beneficios</a>
            <a href="#caracteristicas" class="hover:text-teal-600 transition-colors">Funciones</a>
            <a href="#planes" class="hover:text-teal-600 transition-colors">Planes</a>
        </nav>
        <div class="flex gap-4">
            <a href="<?= $login_url ?>" class="hidden md:block px-6 py-2.5 text-sm font-bold text-slate-700 hover:text-teal-600 transition-colors">Iniciar Sesión</a>
            <a href="<?= $login_url ?>" class="px-6 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold shadow-xl shadow-slate-900/20 hover:scale-105 active:scale-95 transition-all">Pruébalo Gratis</a>
        </div>
    </header>

    <section class="max-w-7xl mx-auto px-6 py-24 grid lg:grid-cols-2 gap-16 items-center">
        <div class="reveal active">
            <span class="px-4 py-1.5 bg-teal-50 text-teal-700 text-[10px] font-black uppercase tracking-widest rounded-full border border-teal-100">Solución Multi-empresa</span>
            <h1 class="mt-6 text-6xl md:text-7xl font-extrabold leading-none tracking-tighter">Gestiona tu negocio <span class="text-teal-600">sin límites.</span></h1>
            <p class="mt-8 text-lg text-slate-500 leading-relaxed max-w-md">La plataforma más intuitiva para controlar sucursales, servicios y profesionales desde una sola interfaz moderna.</p>
            <div class="mt-12 flex flex-wrap gap-5">
                <a href="#planes" class="bg-teal-600 text-white px-10 py-5 rounded-2xl font-bold shadow-2xl shadow-teal-600/40 hover:bg-teal-700 transition-all">Empezar ahora</a>
                <a href="<?= $demo_url ?>" class="glass px-10 py-5 rounded-2xl font-bold flex items-center gap-3 hover:bg-white transition-all"><i data-lucide="play-circle"></i> Ver Demo</a>
            </div>
        </div>
        <div class="relative reveal active" style="transition-delay: 200ms">
            <div class="absolute inset-0 bg-gradient-to-tr from-teal-400/20 to-cyan-400/20 blur-[120px] rounded-full"></div>
            <img src="<?= $hero_image ?>" class="relative rounded-[3rem] shadow-full border-[12px] border-white/50 object-cover aspect-video" alt="Dashboard">
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-6 py-10 grid grid-cols-2 md:grid-cols-4 gap-8">
        <?php foreach(['99%'=>'Uptime','+10k'=>'Reservas','24/7'=>'Soporte','5.0'=>'Rating'] as $val => $lab): ?>
        <div class="text-center reveal">
            <div class="text-3xl font-black text-slate-900"><?= $val ?></div>
            <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1"><?= $lab ?></div>
        </div>
        <?php endforeach; ?>
    </section>

    <section id="beneficios" class="py-32">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid md:grid-cols-3 gap-10">
                <?php 
                $ben = [
                    ['i'=>'zap','t'=>'Agilidad Total','d'=>'Tus clientes reservan en menos de 30 segundos.'],
                    ['i'=>'shield-check','t'=>'Seguridad','d'=>'Tus datos y los de tus clientes están 100% encriptados.'],
                    ['i'=>'layout','t'=>'Personalizable','d'=>'Ajusta colores y logos para que parezca tu propia web.']
                ];
                foreach($ben as $b): ?>
                <div class="spotlight glass p-10 rounded-[2.5rem] reveal">
                    <div class="w-14 h-14 bg-teal-600/10 text-teal-600 rounded-2xl flex items-center justify-center mb-6"><i data-lucide="<?= $b['i'] ?>"></i></div>
                    <h3 class="text-xl font-extrabold mb-4"><?= $b['t'] ?></h3>
                    <p class="text-slate-500 text-sm leading-relaxed"><?= $b['d'] ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="planes" class="py-32 bg-slate-900 rounded-[4rem] mx-4 text-white overflow-hidden relative">
        <div class="absolute top-0 right-0 w-96 h-96 bg-teal-500/10 blur-[150px]"></div>
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="text-center mb-20">
                <h2 class="text-4xl font-black">Escalabilidad real.</h2>
                <p class="text-slate-400 mt-4">Sin letras pequeñas, elige según tu equipo.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <?php foreach($plans as $p): ?>
                <div class="reveal border border-white/10 bg-white/5 p-10 rounded-[3rem] backdrop-blur-md flex flex-col">
                    <h3 class="text-2xl font-bold"><?= $p['nombre'] ?></h3>
                    <div class="my-8">
                        <span class="text-5xl font-black">Q<?= $p['precio'] ?></span>
                        <span class="text-slate-400">/mes</span>
                    </div>
                    <ul class="space-y-4 text-sm text-slate-300 mb-10 flex-grow">
                        <li class="flex gap-3"><i data-lucide="check" class="text-teal-400 w-5"></i> <?= $p['max_sucursales'] ?> Sucursal</li>
                        <li class="flex gap-3"><i data-lucide="check" class="text-teal-400 w-5"></i> <?= $p['max_empleados'] ?> Profesionales</li>
                        <li class="flex gap-3"><i data-lucide="check" class="text-teal-400 w-5"></i> Soporte Prioritario</li>
                    </ul>
                    <a href="<?= $login_url ?>" class="w-full py-4 bg-teal-600 text-center rounded-2xl font-bold hover:bg-teal-500 transition-colors">Seleccionar</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <footer class="py-20 max-w-7xl mx-auto px-6 border-t border-slate-200 mt-20">
        <div class="grid md:grid-cols-4 gap-12">
            <div class="col-span-2">
                <div class="flex items-center gap-2 mb-6">
                    <div class="h-8 w-8 bg-slate-900 rounded-lg flex items-center justify-center text-white font-bold text-xs">GP</div>
                    <span class="font-black tracking-tighter italic">RESERVAS</span>
                </div>
                <p class="text-slate-500 text-sm max-w-xs leading-relaxed">Automatizando la industria de servicios con tecnología de vanguardia y diseño intuitivo.</p>
            </div>
            <div>
                <h4 class="font-bold mb-6">Plataforma</h4>
                <ul class="text-sm text-slate-500 space-y-4">
                    <li><a href="#beneficios" class="hover:text-teal-600">Beneficios</a></li>
                    <li><a href="#planes" class="hover:text-teal-600">Precios</a></li>
                    <li><a href="<?= $demo_url ?>" class="hover:text-teal-600">Demo</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold mb-6">Soporte</h4>
                <ul class="text-sm text-slate-500 space-y-4">
                    <li><a href="#" class="hover:text-teal-600">Documentación</a></li>
                    <li><a href="#" class="hover:text-teal-600">Contacto</a></li>
                </ul>
            </div>
        </div>
        <div class="mt-20 pt-8 border-t border-slate-100 text-center text-[10px] font-bold text-slate-400 tracking-widest uppercase">
            © <?= date('Y') ?> <?= $site_name ?> · Diseñado para el éxito.
        </div>
    </footer>

    <script>
        lucide.createIcons();

        // EFECTO MOUSE SPOTLIGHT
        document.querySelectorAll('.spotlight').forEach(card => {
            card.onmousemove = e => {
                const r = card.getBoundingClientRect();
                card.style.setProperty('--x', `${e.clientX - r.left}px`);
                card.style.setProperty('--y', `${e.clientY - r.top}px`);
            };
        });

        // REVELADO AL HACER SCROLL
        const obs = new IntersectionObserver(ents => {
            ents.forEach(e => { if(e.isIntersecting) e.target.classList.add('active'); });
        }, { threshold: 0.1 });
        document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
    </script>
</body>
</html>