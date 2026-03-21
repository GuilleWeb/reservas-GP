<?php
// includes/topbar.php
// Las variables de empresa ($empresa_info, $empresa_nombre, $logo_path, etc.)
// ya vienen definidas desde bootstrap.php con fallbacks seguros.
// Este archivo NO escribe en $_SESSION ni resuelve empresa por sí mismo.

$module = isset($module) ? ' | ' . $module : '';
$csrf = generate_csrf();
$user = $user ?? current_user();
$role = $user['rol'] ?? null;
$is_public = is_public_view();
$is_public_view = $is_public;
$empresa_id = get_empresa_id();
$empresa_slug = get_empresa_slug();
$public_empresa_ref = $empresa_slug ?: $empresa_id;
$id_e = $empresa_id;
$sidebar_role = get_effective_role($user, $_SERVER['REQUEST_URI'] ?? '');

// Badge de contexto: superadmin o admin actuando con rol heredado
$acting_as_other = $user && $sidebar_role !== $role && $sidebar_role !== null;
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $empresa_nombre ?><?= $module ?></title>
  <link rel="icon" type="image/png" href="<?= htmlspecialchars(app_url('assets/logo.avif')) ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars(app_url('assets/logo.avif')) ?>">
  <link rel="manifest" href="<?= htmlspecialchars(app_url('manifest.json')) ?>">
  <meta name="theme-color" content="#0d9488">
  <meta property="og:title" content="<?= $empresa_nombre ?><?= $module ?>">
  <meta property="og:description" content="Agenda en línea y gestiona tu negocio desde un solo lugar.">
  <meta property="og:image" content="<?= htmlspecialchars(app_url('assets/logo.avif')) ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= $empresa_nombre ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= $empresa_nombre ?><?= $module ?>">
  <meta name="twitter:description" content="Agenda en línea y gestiona tu negocio.">
  <meta name="twitter:image" content="<?= htmlspecialchars(app_url('assets/logo.avif')) ?>">
  <script src="https://cdn.tailwindcss.com"></script>
  <?php if ($color_p): ?>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              teal: {
                50: '<?= $color_p ?>10',
                100: '<?= $color_p ?>20',
                200: '<?= $color_p ?>30',
                300: '<?= $color_p ?>40',
                400: '<?= $color_p ?>60',
                500: '<?= $color_p ?>80',
                600: '<?= $color_p ?>',
                700: '<?= $color_p ?>e6',
                800: '<?= $color_p ?>cc',
                900: '<?= $color_p ?>b3',
              }
            }
          }
        }
      }
    </script>
  <?php endif; ?>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
</head>

<body class="bg-gray-50 min-h-screen font-sans <?= $is_public ? 'app-public' : 'app-private' ?>">

  <style>
    body.sidebar-collapsed .max-w-7xl {
      max-width: 100% !important;
    }

    /* Base visual cohesion for all private modules (forms, tables, controls). */
    body.app-private main :where(input, select, textarea):not([type="checkbox"]):not([type="radio"]):not([type="file"]):not([type="color"]) {
      width: 100%;
      border: 1px solid #d1d5db;
      border-radius: 0.75rem;
      background-color: #ffffff;
      color: #111827;
      padding: 0.55rem 0.75rem;
      line-height: 1.3rem;
      transition: box-shadow .2s ease, border-color .2s ease, background-color .2s ease;
    }

    body.app-private main :where(input, select, textarea):focus {
      outline: none;
      border-color: #0d9488;
      box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
    }

    body.app-private main textarea {
      min-height: 2.75rem;
    }

    body.app-private main button {
      border-radius: 0.75rem;
      font-weight: 600;
      transition: all .2s ease;
    }

    body.app-private main button:hover {
      transform: translateY(-1px);
    }

    body.app-private main table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }

    body.app-private main table thead th {
      background-color: #f9fafb;
      color: #374151;
      font-weight: 700;
      border-bottom: 1px solid #e5e7eb;
    }

    body.app-private main table tbody td {
      border-bottom: 1px solid #f1f5f9;
      color: #111827;
      vertical-align: middle;
    }

    body.app-private main table thead th[data-sort] {
      cursor: pointer;
      user-select: none;
    }
  </style>

  <?php if ($acting_as_other): ?>
    <div
      class="fixed top-0 left-0 right-0 z-50 text-xs flex items-center justify-between bg-teal-700 text-white m-2 rounded-lg shadow h-16 px-4">
      <span class="flex">
        <i data-lucide="user-cog" class="mr-1"></i>
        Actuando como <strong>&nbsp;<?= htmlspecialchars($sidebar_role) ?>&nbsp;</strong>
        <?php if ($empresa_info): ?>
          en <strong>&nbsp;<?= htmlspecialchars($empresa_info['nombre']) ?>&nbsp;</strong>
        <?php endif; ?>
      </span>
      <a href="<?= htmlspecialchars(view_url('vistas/superadmin/dashboard.php')) ?>"
        class="underline hover:text-amber-700 ml-4 flex">
        <i data-lucide="arrow-left" class="mr-1"></i> Volver a mi panel
      </a>
    </div>
    <div class="h-6 fixed top-0"></div>
  <?php endif; ?>

  <header class="fixed top-0 left-0 right-0 bg-white shadow z-40 m-2 rounded-lg shadow h-16 px-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">

        <div class="flex items-center gap-2">
          <button id="menuToggle" class="md:hidden text-gray-700 focus:outline-none">
            <i data-lucide="menu" class="text-xl"></i>
          </button>
          <?php if (!$is_public): ?>
            <button id="sidebarCollapse"
              class="hidden md:inline-flex text-gray-700 focus:outline-none h-10 w-10 items-center justify-center rounded-lg hover:bg-gray-50 border"
              title="Contraer sidebar">
              <i data-lucide="chevrons-left"></i>
            </button>
          <?php endif; ?>
        </div>

        <a href="<?= ($user && $role === 'superadmin' && !$id_e)
          ? htmlspecialchars(view_url('vistas/superadmin/dashboard.php'))
          : htmlspecialchars(view_url('vistas/public/inicio.php', $public_empresa_ref)) ?>" class="flex items-center space-x-3">
          <img src="<?= htmlspecialchars($logo_path) ?>" alt="logo" class="h-10 w-10 object-cover rounded-full">
          <div>
            <div class="font-semibold text-lg text-teal-700"><?= $empresa_nombre ?></div>
            <?php if (!$is_public): ?>
              <small class="text-xs text-gray-500">Ver inicio</small>
            <?php endif; ?>
          </div>
        </a>

        <?php if ($is_public): ?>
          <nav class="hidden md:flex flex-1 justify-center space-x-6">
            <?php if ($public_empresa_ref): ?>
              <a href="<?= htmlspecialchars(view_url('vistas/public/inicio.php', $public_empresa_ref)) ?>"
                class="text-gray-700 hover:text-teal-600 font-medium nav-link">Inicio</a>
              <a href="<?= htmlspecialchars(view_url('vistas/public/ver-sedes.php', $public_empresa_ref)) ?>"
                class="text-gray-700 hover:text-teal-600 font-medium nav-link">Sedes</a>
              <a href="<?= htmlspecialchars(view_url('vistas/public/citas.php', $public_empresa_ref)) ?>"
                class="text-gray-700 hover:text-teal-600 font-medium nav-link">Agendar Cita</a>
              <a href="<?= htmlspecialchars(view_url('vistas/public/blog.php', $public_empresa_ref)) ?>"
                class="text-gray-700 hover:text-teal-600 font-medium nav-link">Blog</a>
            <?php else: ?>
              <a href="<?= htmlspecialchars(view_url('vistas/public/inicio.php')) ?>"
                class="text-gray-700 hover:text-teal-600 font-medium nav-link">Inicio</a>
            <?php endif; ?>
          </nav>
        <?php endif; ?>

        <div class="flex items-center space-x-4">
          <?php if ($user): ?>
            <div class="text-sm text-gray-700 hidden sm:block">
              Hola, <span class="font-medium"><?= htmlspecialchars($user['nombre']) ?></span>
            </div>
            <button id="logoutBtn" class="px-2 py-1 flex rounded-md bg-red-500 text-white hover:opacity-90">
              <i data-lucide="log-out" class="mr-1"></i> Salir
            </button>
          <?php else: ?>
            <a href="<?= htmlspecialchars(view_url('vistas/public/login.php', $public_empresa_ref)) ?>"
              class="px-3 py-1 rounded-md bg-teal-600 text-white">Iniciar sesión</a>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </header>

  <div class="pt-16 flex">

    <?php if (!$is_public): ?>
      <aside id="sidebar"
        class="fixed md:static top-16 left-0 w-64 md:w-64 bg-white border-r min-h-screen transform -translate-x-full md:translate-x-0 transition-all duration-300 ease-in-out z-30 m-2 rounded-lg shadow">
        <div class="p-4">
          <nav class="space-y-1" id="sidebarNav">
            <?php if ($user): ?>
              <div class="w-100 flex justify-center mb-5">
                <?php
                  $user_photo = !empty($user['foto_path'])
                    ? app_url(ltrim((string) $user['foto_path'], '/'))
                    : app_url('assets/logo.avif');
                ?>
                <img
                  src="<?= htmlspecialchars($user_photo) ?>"
                  alt="Logo" class="h-16 w-16 rounded-full object-cover">
              </div>
            <?php endif; ?>

            <?php if ($sidebar_role === 'superadmin' && !$id_e): ?>
              <a href="<?= htmlspecialchars(view_url('vistas/superadmin/dashboard.php')) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="trending-up" class="w-5"></i><span class="ml-2 sidebar-label">Dashboard</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/superadmin/empresas.php')) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="building" class="w-5"></i><span class="ml-2 sidebar-label">Empresas</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/superadmin/planes.php')) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="layers" class="w-5"></i><span class="ml-2 sidebar-label">Planes</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/superadmin/mensajes.php')) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="inbox" class="w-5"></i><span class="ml-2 sidebar-label">Mensajes</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/superadmin/usuarios.php')) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="users" class="w-5"></i><span class="ml-2 sidebar-label">Usuarios</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/superadmin/ajustes.php')) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="sliders-horizontal" class="w-5"></i><span class="ml-2 sidebar-label">Ajustes</span>
              </a>

            <?php elseif ($sidebar_role === 'admin'): ?>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/dashboard.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="pie-chart" class="w-5"></i><span class="ml-2 sidebar-label">Dashboard</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/usuarios.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="users" class="w-5"></i><span class="ml-2 sidebar-label">Usuarios</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/servicios.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="bell-ring" class="w-5"></i><span class="ml-2 sidebar-label">Servicios</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/sucursales.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="store" class="w-5"></i><span class="ml-2 sidebar-label">Sucursales</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/admin-citas.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="calendar" class="w-5"></i><span class="ml-2 sidebar-label">Citas</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/clientes.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="tag" class="w-5"></i><span class="ml-2 sidebar-label">Clientes</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/equipo.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="user" class="w-5"></i><span class="ml-2 sidebar-label">Equipo</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/resenas.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="star" class="w-5"></i><span class="ml-2 sidebar-label">Reseñas</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/blog.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="monitor-play" class="w-5"></i><span class="ml-2 sidebar-label">Blog</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/home_page.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="paintbrush" class="w-5"></i><span class="ml-2 sidebar-label">Administrar inicio</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/ajustes.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="settings" class="w-5"></i><span class="ml-2 sidebar-label">Ajustes</span>
              </a>

            <?php elseif ($sidebar_role === 'gerente'): ?>
              <a href="<?= htmlspecialchars(view_url('vistas/sucursal/dashboard.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="pie-chart" class="w-5"></i><span class="ml-2 sidebar-label">Dashboard</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/sucursal/usuarios.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="users" class="w-5"></i><span class="ml-2 sidebar-label">Usuarios</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/sucursal/servicios.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="bell-ring" class="w-5"></i><span class="ml-2 sidebar-label">Servicios</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/sucursal/admin-citas.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="calendar" class="w-5"></i><span class="ml-2 sidebar-label">Citas</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/sucursal/ajustes.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="settings" class="w-5"></i><span class="ml-2 sidebar-label">Ajustes</span>
              </a>

            <?php elseif ($sidebar_role === 'empleado'): ?>
              <a href="<?= htmlspecialchars(view_url('vistas/empleado/dashboard.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="pie-chart" class="w-5"></i><span class="ml-2 sidebar-label">Dashboard</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/empleado/citas.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="calendar" class="w-5"></i><span class="ml-2 sidebar-label">Citas</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/empleado/ajustes.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="settings" class="w-5"></i><span class="ml-2 sidebar-label">Ajustes</span>
              </a>

            <?php elseif ($sidebar_role === 'cliente'): ?>
              <a href="<?= htmlspecialchars(view_url('vistas/cliente/citas.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="calendar-check" class="w-5"></i><span class="ml-2 sidebar-label">Citas</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/cliente/ajustes.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="settings" class="w-5"></i><span class="ml-2 sidebar-label">Ajustes</span>
              </a>

            <?php else: ?>
              <?php if ($public_empresa_ref): ?>
                <a href="<?= htmlspecialchars(view_url('vistas/public/inicio.php', $public_empresa_ref)) ?>"
                  class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700">
                  <i data-lucide="home" class="w-5"></i><span class="ml-2 sidebar-label">Inicio</span>
                </a>
                <a href="<?= htmlspecialchars(view_url('vistas/public/ver-sedes.php', $public_empresa_ref)) ?>"
                  class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700">
                  <i data-lucide="hospital" class="w-5"></i><span class="ml-2 sidebar-label">Sedes</span>
                </a>
                <a href="<?= htmlspecialchars(view_url('vistas/public/citas.php', $public_empresa_ref)) ?>"
                  class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700">
                  <i data-lucide="calendar-check" class="w-5"></i><span class="ml-2 sidebar-label">Agendar Cita</span>
                </a>
                <a href="<?= htmlspecialchars(view_url('vistas/public/blog.php', $public_empresa_ref)) ?>"
                  class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700">
                  <i data-lucide="newspaper" class="w-5"></i><span class="ml-2 sidebar-label">Blog</span>
                </a>
              <?php else: ?>
                <a href="<?= htmlspecialchars(view_url('vistas/public/inicio.php')) ?>"
                  class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700">
                  <i data-lucide="home" class="w-5"></i><span class="ml-2 sidebar-label">Inicio</span>
                </a>
              <?php endif; ?>
              <?php if (!$user): ?>
                <a href="<?= htmlspecialchars(view_url('vistas/public/login.php', $public_empresa_ref)) ?>"
                  class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700 mt-4 border-t pt-4">
                  <i data-lucide="log-in" class="w-5"></i><span class="ml-2 sidebar-label">Ingresar</span>
                </a>
              <?php endif; ?>
            <?php endif; ?>
          </nav>
        </div>
      </aside>

    <?php else: ?>
      <aside id="sidebar"
        class="fixed top-16 left-0 w-64 bg-white border-r min-h-screen transform -translate-x-full transition-all duration-300 ease-in-out z-30 md:hidden">
        <div class="p-4">
          <nav class="space-y-1" id="sidebarNav">
            <a href="<?= htmlspecialchars(view_url('vistas/public/inicio.php')) ?>"
              class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700">
              <i data-lucide="home" class="w-5"></i><span class="ml-2 sidebar-label">Inicio</span>
            </a>
          </nav>
        </div>
      </aside>
    <?php endif; ?>

    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-40 hidden z-20 md:hidden"></div>

    <main
      class="flex-1 w-full <?= $is_public ? 'p-6 bg-gradient-to-br from-teal-100 via-white to-teal-50' : 'p-6' ?> overflow-x-hidden">

      <script>
          (function () {
            function applyCollapse(collapsed) {
              const sidebar = document.getElementById('sidebar');
              const labels = document.querySelectorAll('.sidebar-label');
              const nav = document.getElementById('sidebarNav');
              const btn = document.getElementById('sidebarCollapse');
              if (!sidebar || !btn || !nav) return;
              if (collapsed) {
                document.body.classList.add('sidebar-collapsed');
                sidebar.classList.add('md:w-16');
                sidebar.classList.remove('md:w-64');
                labels.forEach(el => el.classList.add('hidden'));
                nav.querySelectorAll('a').forEach(a => a.classList.add('justify-center'));
                btn.innerHTML = '<i data-lucide="chevrons-right"></i>';
                btn.title = 'Expandir sidebar';
              } else {
                document.body.classList.remove('sidebar-collapsed');
                sidebar.classList.add('md:w-64');
                sidebar.classList.remove('md:w-16');
                labels.forEach(el => el.classList.remove('hidden'));
                nav.querySelectorAll('a').forEach(a => a.classList.remove('justify-center'));
                btn.innerHTML = '<i data-lucide="chevrons-left"></i>';
                btn.title = 'Contraer sidebar';
              }
            }
            const saved = localStorage.getItem('sidebar_collapsed') === '1';
            applyCollapse(saved);
            const btn = document.getElementById('sidebarCollapse');
            if (btn) {
              btn.addEventListener('click', function () {
                const next = !(localStorage.getItem('sidebar_collapsed') === '1');
                localStorage.setItem('sidebar_collapsed', next ? '1' : '0');
                applyCollapse(next);
              });
            }
          })();
      </script>
