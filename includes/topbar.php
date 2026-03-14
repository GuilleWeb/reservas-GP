<?php
$csrf = generate_csrf();
$user = current_user();
$id_e = request_id_e();
$role = $user['rol'] ?? null;
$module = ' | '.$module ?? '';
$is_public_view = in_array($module, ['inicio', 'login', 'blog', 'sedes', 'ver-sedes', 'citas', '404']);
$page404 = false;
if ($user && $role === 'superadmin' && isset($_GET['id_e']) && !empty(trim($_GET['id_e']))) {
  $stmt = $pdo->prepare('SELECT * FROM empresas WHERE id = ?');
  $stmt->execute([(int) $_GET['id_e']]);
  $empresa = $stmt->fetch();
  if ($empresa) {
    $_SESSION['user']['empresa_id'] = (int) $empresa['id'];
    $_SESSION['user']['id_e'] = $empresa['slug'];
    $page404 = false;
  } else {
    $page404 = true;

  }
  //$empresa_id = $_GET['id_e'];
} else if ($user && $role !== 'superadmin') {
  $stmt = $pdo->prepare('SELECT * FROM empresas WHERE id = ?');
  $stmt->execute([$user['empresa_id']]);
  $empresa = $stmt->fetch();
  if ($empresa) {
    $_SESSION['user']['empresa_id'] = (int) $empresa['id'];
    $_SESSION['user']['id_e'] = $empresa['slug'];
    $page404 = false;
  } else {
    $page404 = true;

  }
} else if (isset($_GET['id_e']) && !empty($_GET['id_e'])) {
  $stmt = $pdo->prepare('SELECT * FROM empresas WHERE slug = ?');
  $stmt->execute([trim($_GET['id_e'])]);
  $empresa = $stmt->fetch();
  if ($empresa) {
    $id_e = $empresa['slug'];
    $page404 = false;
  } else {
    $page404 = false;

  }
}else{
  $page404 = false;
}
/*$empresa_info = $empresa ?? null;
$logo_path = $empresa_info && !empty($empresa_info['logo_path']) ? $empresa_info['logo_path'] : '../../assets/logo.avif';
$empresa_nombre = $empresa_info ? htmlspecialchars($empresa_info['nombre']) : 'Sistema de reservas GP';
$colores = $empresa_info && $empresa_info['colores_json'] ? json_decode($empresa_info['colores_json'], true) : [];
$color_p = $colores['principal'] ?? '#46C9BB';*/
// Se espera que $empresa_info esté definida en las vistas públicas
$empresa_info = $empresa ?? null;
$logo_path = $empresa_info && !empty($empresa_info['logo_path']) ? '../../'.$empresa_info['logo_path'] : '../../assets/logo.avif';
$empresa_nombre = $empresa_info ? htmlspecialchars($empresa_info['nombre']) : 'Sistema de reservas GP';
$empresa_slogan = $empresa_info && !empty($empresa_info['slogan']) ? htmlspecialchars($empresa_info['slogan']) : 'Agendá fácil, viví mejor';
$empresa_descripcion = $empresa_info && !empty($empresa_info['descripcion']) ? htmlspecialchars($empresa_info['descripcion']) : 'Sistema de gestion de citas con multiples fucnionalidades y modulos separados por roles, multi empresa y multisucursal.';

// Leer config_json
$config = [];
if ($empresa_info && !empty($empresa_info['config_json'])) {
    $config = json_decode($empresa_info['config_json'], true) ?: [];
}
$email_contacto = $config['email_contacto'] ?? 'soporte@reservasgp.com';
$telefono_contacto = $config['telefono_contacto'] ?? '+502 5103-6244';
$horaios = $config['horario_general'] ?? 'Siempre abierto';
$direccion = $config['direccion_general'] ?? 'Negocio en linea';
// Redes sociales
$redes = ['facebook'=>'https://google.com','instagram'=>'https://google.com','whatsapp'=>'https://google.com',
'tiktok'=>'https://google.com','x'=>'https://google.com','otro'=>'https://google.com'];
if ($empresa_info && !empty($empresa_info['redes_json'])) {
    $redes = json_decode($empresa_info['redes_json'], true) ?: [];
}

// Colores
$colores = [];
if ($empresa_info && !empty($empresa_info['colores_json'])) {
    $colores = json_decode($empresa_info['colores_json'], true) ?: [];
}
$color_p = $colores['principal'] ?? '#46C9BB'; // usado para algún detalle si se desea
// ========== NUEVA LÓGICA: DETERMINAR EL ROL EFECTIVO PARA EL SIDEBAR ==========
// Por defecto, el rol del sidebar es el rol real del usuario
$sidebar_role = $role;

// Solo aplicamos la lógica de herencia si NO es vista pública y hay id_e
if (!$is_public_view && $id_e) {
  // Obtener la ruta actual para determinar qué tipo de panel se está visitando
  $current_url = $_SERVER['REQUEST_URI'];

  if (strpos($current_url, '/admin/') !== false) {
    // Visitando panel de admin
    $sidebar_role = 'admin';
  } elseif (strpos($current_url, '/sucursal/') !== false) {
    // Visitando panel de gerente
    $sidebar_role = 'gerente';
  } elseif (strpos($current_url, '/empleado/') !== false) {
    // Visitando panel de empleado
    $sidebar_role = 'empleado';
  } elseif (strpos($current_url, '/cliente/') !== false) {
    // Visitando panel de cliente
    $sidebar_role = 'cliente';
  }
  // Si no coincide con ningún panel administrativo, mantiene el rol original
}
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $empresa_nombre ?> <?= $module ?></title>

  <!-- ========== ICONOS / LOGOS ========== -->
  <!-- Favicon general -->
  <link rel="icon" type="image/png" href="assets/logo.avif">
  <!-- Icono Apple / iOS -->
  <link rel="apple-touch-icon" sizes="180x180" href="assets/logo.avif">
  <!-- Icono Android -->
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#0d9488">

  <!-- ========== META PARA COMPARTIR (Open Graph y Twitter Cards) ========== -->
  <meta property="og:title" content="<?= $empresa_nombre ?> <?= $module ?>">
  <meta property="og:description" content="Agenda en línea y gestiona tu negocio desde un solo lugar.">
  <meta property="og:image" content="assets/logo.avif">
  <meta property="og:url" content="">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= $empresa_nombre ?> <?= $module ?>">

  <!-- Para previsualización en Twitter -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= $empresa_nombre ?> <?= $module ?>">
  <meta name="twitter:description" content="Agenda en línea y gestiona tu negocio.">
  <meta name="twitter:image" content="assets/logo.avif">
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- ========== LIBRERÍAS PRINCIPALES ========== -->
  <?php if ($color_p): ?>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              teal: {
                50: '<?= $color_p ?>10', // 10% opacity
                100: '<?= $color_p ?>20',
                200: '<?= $color_p ?>30',
                300: '<?= $color_p ?>40',
                400: '<?= $color_p ?>60',
                500: '<?= $color_p ?>80',
                600: '<?= $color_p ?>',
                700: '<?= $color_p ?>e6', // slightly dark
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

  <!-- CSRF -->
  <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
</head>

<body class="bg-gray-50 min-h-screen font-sans">

  <style>
    body.sidebar-collapsed .max-w-7xl {
      max-width: 100% !important;
    }
  </style>

  <!-- Topbar -->
  <header class="fixed top-0 left-0 right-0 bg-white shadow z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <!-- Botón menú móvil -->
        <div class="flex items-center gap-2">
          <button id="menuToggle" class="md:hidden text-gray-700 focus:outline-none">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <?php if (!$is_public_view): ?>
            <button id="sidebarCollapse"
              class="hidden md:inline-flex text-gray-700 focus:outline-none h-10 w-10 items-center justify-center rounded-lg hover:bg-gray-50 border"
              title="Contraer sidebar">
              <i class="fas fa-angles-left"></i>
            </button>
          <?php endif; ?>
        </div>

        <!-- Logo -->
        <a href="<?= ($user && $role === 'superadmin') ? htmlspecialchars(view_url('vistas/superadmin/dashboard.php')) : htmlspecialchars(view_url($id_e ? 'vistas/public/inicio.php' : 'vistas/public/inicio.php', $id_e)) ?>"
          class="flex items-center space-x-3">
          <img src="<?= htmlspecialchars($logo_path) ?>" alt="logo" class="h-10 w-10 object-cover rounded-full">
          <div>
            <div class="font-semibold text-lg text-teal-700"><?= $empresa_nombre ?></div>
            <?php if (!$is_public_view): ?>
              <small class="text-xs text-gray-500">Panel</small>
            <?php endif; ?>
          </div>
        </a>

        <?php if ($is_public_view): ?>
          <nav class="hidden md:flex flex-1 justify-center space-x-6">
            <?php if ($id_e): ?>
              <a href="<?= htmlspecialchars(view_url('vistas/public/inicio.php', $id_e)) ?>"
                class="text-gray-700 hover:text-teal-600 font-medium font-semibold nav-link">Inicio</a>
              <a href="<?= htmlspecialchars(view_url('vistas/public/sedes.php', $id_e)) ?>"
                class="text-gray-700 hover:text-teal-600 font-medium font-semibold nav-link">Sedes</a>
              <a href="<?= htmlspecialchars(view_url('vistas/public/citas.php', $id_e)) ?>"
                class="text-gray-700 hover:text-teal-600 font-medium font-semibold nav-link">Agendar Cita</a>
              <a href="<?= htmlspecialchars(view_url('vistas/public/blog.php', $id_e)) ?>"
                class="text-gray-700 hover:text-teal-600 font-medium font-semibold nav-link">Blog</a>
            <?php else: ?>
              <a href="<?= htmlspecialchars(view_url('vistas/public/inicio.php')) ?>"
                class="text-gray-700 hover:text-teal-600 font-medium font-semibold nav-link">Inicio
                <?php echo $is_public_view; ?></a>
            <?php endif; ?>
          </nav>
        <?php endif; ?>

        <!-- Usuario -->
        <div class="flex items-center space-x-4">
          <?php if ($user): ?>
            <div class="text-sm text-gray-700">
              Hola, <span class="font-medium"><?= htmlspecialchars($user['nombre']) ?></span>
              <?php if ($sidebar_role !== $role): ?>
                <span class="text-xs text-teal-600 ml-1">(actuando como <?= $sidebar_role ?>)</span>
              <?php endif; ?>
            </div>
            <button id="logoutBtn" class="px-3 py-1 rounded-md bg-red-500 text-white hover:opacity-90">
              <i class="fas fa-sign-out-alt mr-1"></i> Salir
            </button>
          <?php else: ?>
            <a href="<?= htmlspecialchars(view_url('vistas/public/login.php', $id_e)) ?>"
              class="px-3 py-1 rounded-md bg-teal-600 text-white">Iniciar sesión</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <!-- Contenedor general -->
  <div class="pt-16 flex">

    <?php if (!$is_public_view): ?>
      <!-- Sidebar -->
      <aside id="sidebar"
        class="fixed md:static top-16 left-0 w-64 md:w-64 bg-white border-r min-h-screen transform -translate-x-full md:translate-x-0 transition-all duration-300 ease-in-out z-30">

        <div class="p-4">
          <nav class="space-y-1" id="sidebarNav">
            <?php if ($user): ?>
              <div class="w-100 flex justify-center mb-5">
                <img src="../../<?= !empty(htmlspecialchars($user['foto_path'])) ? $user['foto_path'] :  'assets/logo.avif' ?>" alt="Logo" class="h-16 w-16 rounded-full object-cover">
              </div>
            <?php endif; ?> 
            <?php
            // ========== SIDEBAR CON ROL EFECTIVO ==========
            if ($sidebar_role === 'superadmin' && !$id_e):
              ?>
              <!-- Superadmin - Solo cuando NO hay empresa -->
              <a href="<?= htmlspecialchars(view_url('vistas/superadmin/dashboard.php')) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-chart-line w-5"></i><span class="ml-2 sidebar-label">Dashboard</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/superadmin/empresas.php')) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-building w-5"></i><span class="ml-2 sidebar-label">Empresas</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/superadmin/planes.php')) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-layer-group w-5"></i><span class="ml-2 sidebar-label">Planes</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/superadmin/mensajes.php')) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-inbox w-5"></i><span class="ml-2 sidebar-label">Mensajes</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/superadmin/usuarios.php')) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-users w-5"></i><span class="ml-2 sidebar-label">Usuarios</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/superadmin/ajustes.php')) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-sliders-h w-5"></i><span class="ml-2 sidebar-label">Ajustes</span>
              </a>
            <?php elseif ($sidebar_role === 'admin'): ?>
              <!-- Admin (real o heredado) -->
              <a href="<?= htmlspecialchars(view_url('vistas/admin/dashboard.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-chart-pie w-5"></i><span class="ml-2 sidebar-label">Dashboard</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/usuarios.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-users w-5"></i><span class="ml-2 sidebar-label">Usuarios</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/servicios.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-concierge-bell w-5"></i><span class="ml-2 sidebar-label">Servicios</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/sucursales.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-store w-5"></i><span class="ml-2 sidebar-label">Sucursales</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/admin-citas.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-calendar-alt w-5"></i><span class="ml-2 sidebar-label">Citas</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/clientes.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-user-tag w-5"></i><span class="ml-2 sidebar-label">Clientes</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/equipo.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-user-md w-5"></i><span class="ml-2 sidebar-label">Equipo</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/resenas.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-star w-5"></i><span class="ml-2 sidebar-label">Reseñas</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/blog.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-blog w-5"></i><span class="ml-2 sidebar-label">Blog</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/home_page.php', $id_e)) ?>"
                 class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                 <i class="fas fa-paint-brush w-5"></i>
                 <span class="ml-2 sidebar-label">Administrar inicio</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/admin/ajustes.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-cog w-5"></i><span class="ml-2 sidebar-label">Ajustes</span>
              </a>            <?php elseif ($sidebar_role === 'gerente'): ?>
              <!-- Gerente (real o heredado) -->
              <a href="<?= htmlspecialchars(view_url('vistas/sucursal/dashboard.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-chart-pie w-5"></i><span class="ml-2 sidebar-label">Dashboard</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/sucursal/usuarios.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-users w-5"></i><span class="ml-2 sidebar-label">Usuarios</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/sucursal/servicios.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-concierge-bell w-5"></i><span class="ml-2 sidebar-label">Servicios</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/sucursal/admin-citas.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-calendar-alt w-5"></i><span class="ml-2 sidebar-label">Citas</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/sucursal/ajustes.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-cog w-5"></i><span class="ml-2 sidebar-label">Ajustes</span>
              </a>
            <?php elseif ($sidebar_role === 'empleado'): ?>
              <!-- Empleado (real o heredado) -->
              <a href="<?= htmlspecialchars(view_url('vistas/empleado/dashboard.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-chart-pie w-5"></i><span class="ml-2 sidebar-label">Dashboard</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/empleado/citas.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-calendar-alt w-5"></i><span class="ml-2 sidebar-label">Citas</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/empleado/ajustes.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-cog w-5"></i><span class="ml-2 sidebar-label">Ajustes</span>
              </a>
            <?php elseif ($sidebar_role === 'cliente'): ?>
              <!-- Cliente (real o heredado) -->
              <a href="<?= htmlspecialchars(view_url('vistas/cliente/citas.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-calendar-check w-5"></i><span class="ml-2 sidebar-label">Citas</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/cliente/ajustes.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50">
                <i class="fas fa-cog w-5"></i><span class="ml-2 sidebar-label">Ajustes</span>
              </a>
            <?php endif; ?>
          </nav>
        </div>
      </aside>
    <?php else: ?>
      <!-- Sidebar (Móvil) para Vistas Públicas -->
      <aside id="sidebar"
        class="fixed top-16 left-0 w-64 bg-white border-r min-h-screen transform -translate-x-full transition-all duration-300 ease-in-out z-30 md:hidden">
        <div class="p-4">
          <nav class="space-y-1" id="sidebarNav">
            <?php if ($id_e): ?>
              <a href="<?= htmlspecialchars(view_url('vistas/public/inicio.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700">
                <i class="fas fa-home w-5"></i><span class="ml-2 sidebar-label">Inicio</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/public/sedes.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700">
                <i class="fas fa-hospital w-5"></i><span class="ml-2 sidebar-label">Sedes</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/public/citas.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700">
                <i class="fas fa-calendar-check w-5"></i><span class="ml-2 sidebar-label">Agendar Cita</span>
              </a>
              <a href="<?= htmlspecialchars(view_url('vistas/public/blog.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700">
                <i class="fas fa-newspaper w-5"></i><span class="ml-2 sidebar-label">Blog</span>
              </a>
            <?php else: ?>
              <a href="<?= htmlspecialchars(view_url('vistas/public/inicio.php')) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700">
                <i class="fas fa-home w-5"></i><span class="ml-2 sidebar-label">Inicio</span>
              </a>
            <?php endif; ?>
            <?php if (!$user): ?>
              <a href="<?= htmlspecialchars(view_url('vistas/public/login.php', $id_e)) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700 mt-4 border-t pt-4">
                <i class="fas fa-sign-in-alt w-5"></i><span class="ml-2 sidebar-label">Ingresar</span>
              </a>
            <?php endif; ?>
          </nav>
        </div>
      </aside>
    <?php endif; ?>
    <!-- Overlay (solo móvil) -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-40 hidden z-20 md:hidden"></div>

    <!-- Contenido principal -->
    <main
      class="flex-1 w-full <?= $is_public_view ? '' : 'p-6 bg-gradient-to-br from-teal-100 via-white to-teal-50' ?> overflow-x-hidden">

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
                btn.innerHTML = '<i class="fas fa-angles-right"></i>';
                btn.title = 'Expandir sidebar';
              } else {
                document.body.classList.remove('sidebar-collapsed');
                sidebar.classList.add('md:w-64');
                sidebar.classList.remove('md:w-16');
                labels.forEach(el => el.classList.remove('hidden'));
                nav.querySelectorAll('a').forEach(a => a.classList.remove('justify-center'));
                btn.innerHTML = '<i class="fas fa-angles-left"></i>';
                btn.title = 'Contraer sidebar';
              }
            }

            const saved = localStorage.getItem('sidebar_collapsed') === '1';
            applyCollapse(saved);

            const btn = document.getElementById('sidebarCollapse');
            if (btn) {
              btn.addEventListener('click', function () {
                const current = localStorage.getItem('sidebar_collapsed') === '1';
                const next = !current;
                localStorage.setItem('sidebar_collapsed', next ? '1' : '0');
                applyCollapse(next);
              });
            }
          })();
      </script>
      <?php
      if ($page404) {
        include '../vistas/404.php';
        include '../../includes/footer.php';
        exit;
      }
      ?>