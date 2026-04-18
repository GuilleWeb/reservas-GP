<?php
$site_name = 'Reservas GP';
$title = $site_name.' | Acceso denegado';
?>
<!doctype html>
<html lang="es-GT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <!-- SEO primario -->
  <title><?= htmlspecialchars($title) ?></title>

  <!-- Preconnects -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <!-- Fuentes: DM Sans (body) + Clash Display (display) vía Google -->
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,700;0,9..40,800;1,9..40,400&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Lucide icons -->
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>


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
      background: #eee;
      overflow-x: hidden;
    }
    h1, h2, h3, h4, h5 {
      font-family: 'Space Grotesk', sans-serif;
      letter-spacing: -.025em;
      line-height: 1.15;
    }
  </style>
</head>
<body class="mesh-bg">

  <!-- Progress bar -->
  <div id="progress-bar" role="progressbar" aria-hidden="true"></div>

  <!-- ── HEADER ──────────────────────────────────────────────────────────── -->
  <header id="site-header" class="glass bg-white">
    <div class="max-w-7xl mx-auto px-5 h-16 flex items-center justify-between">
      <a href="<?= htmlspecialchars($site_name) ?>" class="flex items-center gap-2.5 group" aria-label="<?= htmlspecialchars($site_name) ?>">
        <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-teal-500 to-cyan-500 flex items-center justify-center text-white font-black text-sm shadow-md shadow-teal-500/30 transition-transform group-hover:rotate-6 group-hover:scale-105">GP</div>
        <span class="font-extrabold text-base text-slate-900 tracking-tight" style="font-family:'Space Grotesk',sans-serif"><?= htmlspecialchars($site_name) ?></span>
      </a>
      <div class="flex items-center gap-3">
        <a href="/prueba/inicio" class="hidden sm:inline-flex items-center gap-1.5 text-sm font-semibold text-teal-700 hover:text-teal-900 transition-colors">
          <i data-lucide="play-circle" class="w-4 h-4"></i> Demo
        </a>
        <a href="/vistas/public/login.php" class="btn-primary py-2 px-5 text-sm">Acceder</a>
      </div>
    </div>
  </header>

  <main>
    <div class="max-w-4xl mx-auto">
  <div class="bg-white rounded-2xl shadow p-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
      <div>
        <div class="text-sm text-gray-500">Error 503</div>
        <div class="mt-2 text-3xl font-extrabold text-gray-900">Acceso denegado</div>
        <div class="mt-3 text-gray-700">
          No puedes acceder a esta pagina, gracias por visitarnos, si crees que esto es un error contacta a un administrador.
        </div>
      </div>

      <div class="relative w-full md:w-56 h-28">
        <div class="absolute inset-0 flex items-center justify-center">
          <div
            class="w-24 h-24 rounded-3xl bg-teal-50 border border-teal-100 shadow-sm grid place-items-center animate-[float_3s_ease-in-out_infinite]">
            <i data-lucide="compass" class="text-teal-700 text-4xl"></i>
          </div>
        </div>
        <div
          class="absolute left-4 top-4 w-4 h-4 rounded-full bg-teal-200/70 blur-[1px] animate-[pulse_2.2s_ease-in-out_infinite]">
        </div>
        <div
          class="absolute right-6 bottom-6 w-5 h-5 rounded-full bg-teal-300/60 blur-[1px] animate-[pulse_2.8s_ease-in-out_infinite]">
        </div>
      </div>
    </div>

    <div class="mt-6 flex flex-col sm:flex-row gap-3">
      <a href="javascript:history.back()"
        class="inline-flex items-center justify-center px-4 py-2 rounded-lg border hover:bg-gray-50">
        Volver
      </a>
      <a href="/vistas/public/login.php"
        class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-teal-600 text-white hover:opacity-90">
        Iniciar sesión
      </a>
    </div>

    <style>
      @keyframes float {

        0%,
        100% {
          transform: translateY(0px);
        }

        50% {
          transform: translateY(-8px);
        }
      }
    </style>
  </div>
</div>
</body>
<script>
  if (window.lucide) lucide.createIcons();
</script>

</html>
