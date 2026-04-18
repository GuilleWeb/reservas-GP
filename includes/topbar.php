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
$public_home_url = $public_empresa_ref ? view_url('vistas/public/inicio.php', $public_empresa_ref) : (rtrim(app_url(''), '/') . '/');
$id_e = $empresa_id;
$sidebar_role = get_effective_role($user, $_SERVER['REQUEST_URI'] ?? '');
$meta_title = trim((string) ($empresa_nombre . $module));
$meta_desc = trim((string) ($empresa_descripcion ?? 'Agenda en línea y gestiona tu negocio desde un solo lugar.'));
$meta_img = trim((string) ($logo_path ?? app_url('assets/logo.avif')));
$gsc_meta_tag = isset($gsc_meta_tag) ? trim((string) $gsc_meta_tag) : '';
$plan_can = static function ($mod) use ($empresa_id) {
  return plan_allows_module((int) $empresa_id, (string) $mod);
};
$show_ads = false;
$ad_sidebar = null;
$ad_footer = null;
$ad_panel = null;
if (!$is_public && $empresa_id && empresa_is_basic_plan((int) $empresa_id)) {
  $show_ads = true;
  $ads_map = anuncios_get_active_map();
  $ad_sidebar = $ads_map['sidebar'] ?? null;
  $ad_footer = $ads_map['footer'] ?? null;
  $ad_panel = $ads_map['panel'] ?? null;
}
$suscripcion_actual = null;
$suscripcion_alert = null;
if (!$is_public && $empresa_id && ($sidebar_role ?? '') !== 'cliente') {
  $suscripcion_actual = get_empresa_suscripcion_actual((int) $empresa_id);
  if ($suscripcion_actual) {
    $estadoSus = (string) ($suscripcion_actual['estado'] ?? '');
    $diasSus = $suscripcion_actual['dias_restantes'] ?? null;
    if ($estadoSus === 'vencida') {
      $suscripcion_alert = [
        'type' => 'danger',
        'text' => 'Tu suscripción está vencida. Para mantener funciones premium, contacta a soporte para renovar.',
      ];
    } elseif ($estadoSus === 'pendiente') {
      $suscripcion_alert = [
        'type' => 'warn',
        'text' => 'Tu suscripción está pendiente de pago. Algunas funciones pueden limitarse.',
      ];
    } elseif ($diasSus !== null && (int) $diasSus >= 0 && (int) $diasSus <= 7) {
      $suscripcion_alert = [
        'type' => 'warn',
        'text' => 'Tu suscripción vence en ' . (int) $diasSus . ' día(s). Te recomendamos renovarla pronto.',
      ];
    }
  }
}

// Badge de contexto: superadmin o admin actuando con rol heredado
$acting_as_other = $user && $sidebar_role !== $role && $sidebar_role !== null;
$back_panel_url = view_url('vistas/public/login.php');
if ($role === 'superadmin') {
  $back_panel_url = view_url('vistas/superadmin/dashboard.php');
} elseif ($role === 'admin') {
  $back_panel_url = view_url('vistas/admin/dashboard.php', request_id_e() ?: get_empresa_id());
} elseif ($role === 'gerente') {
  $back_panel_url = view_url('vistas/sucursal/dashboard.php', request_id_e() ?: get_empresa_id());
} elseif ($role === 'empleado') {
  $back_panel_url = view_url('vistas/empleado/dashboard.php', request_id_e() ?: get_empresa_id());
}

$notif_unread = 0;
$notif_items = [];
$sidebar_msg_unread = 0;
if ($user && !$is_public) {
  try {
    $uid = (int) ($user['id'] ?? 0);
    $eid = (int) ($id_e ?: get_empresa_id());
    if ($eid > 0 && $uid > 0) {
      $notif_items = notifications_fetch_for_user($eid, $uid, (string) $sidebar_role, 8);
      $notif_unread = notifications_unread_count($eid, $uid, (string) $sidebar_role);
      $sidebar_msg_unread = notifications_unread_count($eid, $uid, (string) $sidebar_role, ['mensaje_interno', 'mensaje_externo']);
    }
  } catch (Throwable $e) {
    $notif_unread = 0;
    $notif_items = [];
    $sidebar_msg_unread = 0;
  }
}
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
  <meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
  <meta property="og:title" content="<?= htmlspecialchars($meta_title) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($meta_desc) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($meta_img) ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= htmlspecialchars((string) $empresa_nombre) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= htmlspecialchars($meta_title) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($meta_desc) ?>">
  <meta name="twitter:image" content="<?= htmlspecialchars($meta_img) ?>">
  <?php if ($gsc_meta_tag !== ''): ?>
    <?= $gsc_meta_tag . PHP_EOL ?>
  <?php endif; ?>
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
  <script>
    window.APP_CURRENCY = {
      code: <?= json_encode((string) ($moneda_code ?? 'GTQ')) ?>,
      symbol: <?= json_encode((string) ($currency_symbol ?? 'Q')) ?>
    };
  </script>
</head>

<body class="bg-gray-50 min-h-screen font-sans <?= $is_public ? 'app-public' : 'app-private' ?>">

  <style>
    html {
      font-size: 95%;
    }

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

    body.app-private main table :where(button, a) i[data-lucide] {
      width: 0.6rem !important;
      height: 0.6rem !important;
      stroke-width: 2;
    }

    body.app-private main table :where(button, a) svg.lucide {
      width: 0.72rem !important;
      height: 0.72rem !important;
      stroke-width: 2 !important;
    }

    body.app-private main table button[class*="h-9"][class*="w-9"] {
      height: 1.7rem !important;
      width: 1.7rem !important;
    }

    body.app-private main table button[class*="h-8"][class*="w-8"] {
      height: 1.55rem !important;
      width: 1.55rem !important;
    }

    body.app-private main table td :where(button, a).action-btn {
      height: 1.45rem !important;
      width: 1.45rem !important;
      padding: 0 !important;
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
      <a href="<?= htmlspecialchars($back_panel_url) ?>"
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
          : htmlspecialchars($public_home_url) ?>" class="flex items-center space-x-3">
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
              <a href="<?= htmlspecialchars(view_url('vistas/public/servicios.php', $public_empresa_ref)) ?>"
                class="text-gray-700 hover:text-teal-600 font-medium nav-link">Servicios</a>
              <?php if ($plan_can('citas')): ?>
              <a href="<?= htmlspecialchars(view_url('vistas/public/citas.php', $public_empresa_ref)) ?>"
                class="text-gray-700 hover:text-teal-600 font-medium nav-link">Agendar Cita</a>
              <?php endif; ?>
              <?php if ($plan_can('blog')): ?>
              <a href="<?= htmlspecialchars(view_url('vistas/public/blog.php', $public_empresa_ref)) ?>"
                class="text-gray-700 hover:text-teal-600 font-medium nav-link">Blog</a>
              <?php endif; ?>
            <?php else: ?>
              <a href="<?= htmlspecialchars($public_home_url) ?>"
                class="text-gray-700 hover:text-teal-600 font-medium nav-link">Inicio</a>
            <?php endif; ?>
          </nav>
        <?php endif; ?>

        <div class="flex items-center space-x-4">
          <?php if ($user): ?>
            <?php if (!$is_public): ?>
              <div class="relative">
                <button id="notifBtn" class="relative h-9 w-9 grid place-items-center rounded-lg border hover:bg-gray-50 text-gray-700">
                  <i data-lucide="bell"></i>
                  <?php if ($notif_unread > 0): ?>
                    <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] font-bold grid place-items-center"><?= (int) $notif_unread ?></span>
                  <?php endif; ?>
                </button>
                <div id="notifMenu" class="hidden absolute right-0 mt-2 w-80 bg-white border rounded-xl shadow-xl z-50 max-h-96 overflow-auto">
                  <div class="p-3 border-b text-sm font-semibold text-gray-800">Notificaciones</div>
                  <?php if (!empty($notif_items)): ?>
                    <?php foreach ($notif_items as $n): ?>
                      <div class="px-3 py-2 border-b last:border-b-0 hover:bg-gray-50">
                        <div class="text-sm font-medium text-gray-800"><?= htmlspecialchars((string) ($n['titulo'] ?? 'Notificación')) ?></div>
                        <div class="text-xs text-gray-500"><?= htmlspecialchars((string) ($n['descripcion'] ?? '')) ?></div>
                        <div class="text-[11px] text-gray-400 mt-1"><?= htmlspecialchars((string) ($n['created_at'] ?? '')) ?></div>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="p-3 text-sm text-gray-500">No tienes notificaciones nuevas.</div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endif; ?>
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
    <?php if ($suscripcion_alert): ?>
      <div class="fixed top-[4.5rem] left-0 right-0 z-100 px-4">
        <div class="max-w-7xl mx-auto rounded-xl border px-4 py-2 text-sm <?= ($suscripcion_alert['type'] === 'danger') ? 'bg-red-50 border-red-200 text-red-700' : 'bg-amber-50 border-amber-200 text-amber-700' ?>">
          <?= htmlspecialchars((string) ($suscripcion_alert['text'] ?? '')) ?>
        </div>
      </div>
      <div class="h-10"></div>
    <?php endif; ?>

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
              <a href="<?= htmlspecialchars(view_url('vistas/superadmin/suscripciones.php')) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="badge-dollar-sign" class="w-5"></i><span class="ml-2 sidebar-label">Suscripciones</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/superadmin/anuncios.php')) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="megaphone" class="w-5"></i><span class="ml-2 sidebar-label">Anuncios</span>
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
              <a href="<?= htmlspecialchars(view_url('vistas/superadmin/cron_jobs.php')) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="timer" class="w-5"></i><span class="ml-2 sidebar-label">Cron Jobs</span>
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
                <i data-lucide="stethoscope" class="w-5"></i><span class="ml-2 sidebar-label">Servicios</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/sucursales.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="store" class="w-5"></i><span class="ml-2 sidebar-label">Sucursales</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/admin-citas.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="calendar" class="w-5"></i><span class="ml-2 sidebar-label">Citas</span>
              </a>
              <?php if ($plan_can('clientes')): ?>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/clientes.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="tag" class="w-5"></i><span class="ml-2 sidebar-label">Clientes</span>
              </a>
              <?php endif; ?>
              <?php if ($plan_can('resenas')): ?>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/resenas.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="star" class="w-5"></i><span class="ml-2 sidebar-label">Reseñas</span>
              </a>
              <?php endif; ?>
              <?php if ($plan_can('blog')): ?>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/blog.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="monitor-play" class="w-5"></i><span class="ml-2 sidebar-label">Blog</span>
              </a>
              <?php endif; ?>
              <?php if ($plan_can('home_page')): ?>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/home_page.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="paintbrush" class="w-5"></i><span class="ml-2 sidebar-label">Administrar inicio</span>
              </a>
              <?php endif; ?>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/ajustes.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="settings" class="w-5"></i><span class="ml-2 sidebar-label">Ajustes</span>
              </a>
              <?php if ($plan_can('mensajes')): ?>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/mensajes.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="inbox" class="w-5"></i><span class="ml-2 sidebar-label">Mensajes<?php if ($sidebar_msg_unread > 0): ?> <span class="ml-1 inline-flex px-1.5 py-0.5 rounded-full bg-red-600 text-white text-[10px] font-bold"><?= (int) $sidebar_msg_unread ?></span><?php endif; ?></span>
              </a>
              <?php endif; ?>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/movimientos.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="history" class="w-5"></i><span class="ml-2 sidebar-label">Movimientos</span>
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
                <i data-lucide="stethoscope" class="w-5"></i><span class="ml-2 sidebar-label">Servicios</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/sucursal/admin-citas.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="calendar" class="w-5"></i><span class="ml-2 sidebar-label">Citas</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/sucursal/ajustes.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="settings" class="w-5"></i><span class="ml-2 sidebar-label">Ajustes</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/sucursal/mensajes.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="inbox" class="w-5"></i><span class="ml-2 sidebar-label">Mensajes<?php if ($sidebar_msg_unread > 0): ?> <span class="ml-1 inline-flex px-1.5 py-0.5 rounded-full bg-red-600 text-white text-[10px] font-bold"><?= (int) $sidebar_msg_unread ?></span><?php endif; ?></span>
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
              <a href="<?= htmlspecialchars(view_url('vistas/empleado/mensajes.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i data-lucide="inbox" class="w-5"></i><span class="ml-2 sidebar-label">Mensajes<?php if ($sidebar_msg_unread > 0): ?> <span class="ml-1 inline-flex px-1.5 py-0.5 rounded-full bg-red-600 text-white text-[10px] font-bold"><?= (int) $sidebar_msg_unread ?></span><?php endif; ?></span>
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
                <a href="<?= htmlspecialchars(view_url('vistas/public/servicios.php', $public_empresa_ref)) ?>"
                  class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700">
                  <i data-lucide="stethoscope" class="w-5"></i><span class="ml-2 sidebar-label">Servicios</span>
                </a>
                <?php if ($plan_can('citas')): ?>
                <a href="<?= htmlspecialchars(view_url('vistas/public/citas.php', $public_empresa_ref)) ?>"
                  class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700">
                  <i data-lucide="calendar-check" class="w-5"></i><span class="ml-2 sidebar-label">Agendar Cita</span>
                </a>
                <?php endif; ?>
                <?php if ($plan_can('blog')): ?>
                <a href="<?= htmlspecialchars(view_url('vistas/public/blog.php', $public_empresa_ref)) ?>"
                  class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700">
                  <i data-lucide="newspaper" class="w-5"></i><span class="ml-2 sidebar-label">Blog</span>
                </a>
                <?php endif; ?>
              <?php else: ?>
                <a href="<?= htmlspecialchars($public_home_url) ?>"
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
            <a href="<?= htmlspecialchars($public_home_url) ?>"
              class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700">
              <i data-lucide="home" class="w-5"></i><span class="ml-2 sidebar-label">Inicio</span>
            </a>
            <?php if ($public_empresa_ref): ?>
              <a href="<?= htmlspecialchars(view_url('vistas/public/ver-sedes.php', $public_empresa_ref)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700">
                <i data-lucide="hospital" class="w-5"></i><span class="ml-2 sidebar-label">Sedes</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/public/servicios.php', $public_empresa_ref)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700">
                <i data-lucide="stethoscope" class="w-5"></i><span class="ml-2 sidebar-label">Servicios</span>
              </a>
              <?php if ($plan_can('citas')): ?>
              <a href="<?= htmlspecialchars(view_url('vistas/public/citas.php', $public_empresa_ref)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700">
                <i data-lucide="calendar-check" class="w-5"></i><span class="ml-2 sidebar-label">Agendar Cita</span>
              </a>
              <?php endif; ?>
              <?php if ($plan_can('blog')): ?>
              <a href="<?= htmlspecialchars(view_url('vistas/public/blog.php', $public_empresa_ref)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700">
                <i data-lucide="newspaper" class="w-5"></i><span class="ml-2 sidebar-label">Blog</span>
              </a>
              <?php endif; ?>
            <?php endif; ?>
          </nav>
          <?php if ($show_ads && $ad_sidebar && (int) ($ad_sidebar['activo'] ?? 0) === 1 && !empty($ad_sidebar['imagen_path'])): ?>
            <?php
              $adSidebarImg = app_url(ltrim((string) $ad_sidebar['imagen_path'], '/'));
              $adSidebarLink = trim((string) ($ad_sidebar['link_url'] ?? ''));
            ?>
            <div class="mt-6 flex justify-center">
              <?php if ($adSidebarLink !== ''): ?>
                <a href="<?= htmlspecialchars($adSidebarLink) ?>" target="_blank" rel="noopener" class="block">
                  <img src="<?= htmlspecialchars($adSidebarImg) ?>" alt="Anuncio" class="w-[200px] h-[200px] object-cover rounded-xl border bg-white">
                </a>
              <?php else: ?>
                <img src="<?= htmlspecialchars($adSidebarImg) ?>" alt="Anuncio" class="w-[200px] h-[200px] object-cover rounded-xl border bg-white">
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </aside>
    <?php endif; ?>

    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-40 hidden z-20 md:hidden"></div>

    <main
      class="flex-1 w-full <?= $is_public ? 'p-6 bg-gradient-to-br from-teal-100 via-white to-teal-50' : 'p-6' ?> overflow-x-hidden">
      <?php if ($show_ads && $ad_panel && (int) ($ad_panel['activo'] ?? 0) === 1 && !empty($ad_panel['imagen_path'])): ?>
        <?php
          $adPanelImg = app_url(ltrim((string) $ad_panel['imagen_path'], '/'));
          $adPanelLink = trim((string) ($ad_panel['link_url'] ?? ''));
        ?>
        <div class="w-full mb-5">
          <?php if ($adPanelLink !== ''): ?>
            <a href="<?= htmlspecialchars($adPanelLink) ?>" target="_blank" rel="noopener" class="block">
              <img src="<?= htmlspecialchars($adPanelImg) ?>" alt="Anuncio" class="w-full h-[200px] object-cover rounded-2xl border bg-white shadow-sm">
            </a>
          <?php else: ?>
            <img src="<?= htmlspecialchars($adPanelImg) ?>" alt="Anuncio" class="w-full h-[200px] object-cover rounded-2xl border bg-white shadow-sm">
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <script>
          (function () {
            function normalizePath(path) {
              try {
                const u = new URL(path, window.location.origin);
                return u.pathname.replace(/\/+$/, '');
              } catch (e) {
                return String(path || '').replace(/\/+$/, '');
              }
            }
            function markActiveLinks() {
              const current = normalizePath(window.location.pathname);
              document.querySelectorAll('.nav-link').forEach(a => {
                const href = a.getAttribute('href') || '';
                const target = normalizePath(href);
                const active = target !== '' && (current === target || current.endsWith(target));
                a.classList.toggle('bg-teal-50', active);
                a.classList.toggle('text-teal-700', active);
                a.classList.toggle('font-semibold', active);
                a.classList.toggle('border', active);
                a.classList.toggle('border-teal-200', active);
              });
            }
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
            markActiveLinks();
          })();
      </script>
