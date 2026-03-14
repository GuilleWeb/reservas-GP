<?php
require_once __DIR__ . '/../helpers.php';
$empresa = null;
if(isset($_GET['id_e'])){
  $stmt = $pdo->prepare('SELECT * FROM empresas WHERE slug = ?');
  $stmt->execute([trim($_GET['id_e'])]);
  $empresa = $stmt->fetch();
  if ($empresa) {
    $id_e = $empresa['id'];
  }
}
$is_public_view = in_array($module, ['inicio', 'login', 'blog', 'sedes', 'ver-sedes', 'citas', '404']);
$csrf = generate_csrf();
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

$module = ' | '.$module ?? '';
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
  <meta property="og:title" content="Sistema">
  <meta property="og:description" content="Agenda en línea y gestiona tu negocio desde un solo lugar.">
  <meta property="og:image" content="assets/logo.avif">
  <meta property="og:url" content="">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Sistema">

  <!-- Para previsualización en Twitter -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Sistema">
  <meta name="twitter:description" content="Agenda en línea y gestiona tu negocio.">
  <meta name="twitter:image" content="assets/logo.avif">

  <!-- ========== LIBRERÍAS PRINCIPALES ========== -->
  <script src="https://cdn.tailwindcss.com"></script>
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
        </div>

        <!-- Logo -->
        <a href="vistas/public/inicio.php"
          class="flex items-center space-x-3">
          <img src="<?= htmlspecialchars($logo_path) ?>" alt="logo" class="h-10 w-10 object-cover rounded-full">
          <div>
            <div class="font-semibold text-lg text-teal-700"><?= $empresa_nombre ?></div>
            
          </div>
        </a>

          <nav class="hidden md:flex flex-1 justify-center space-x-6">
            
              <a href="vistas/public/inicio.php'"
                class="text-gray-700 hover:text-teal-600 font-medium font-semibold nav-link">Inicio</a>
          </nav>
      </div>
    </div>
  </header>

  <!-- Contenedor general -->
  <div class="pt-16 flex">
      <!-- Sidebar (Móvil) para Vistas Públicas -->
      <aside id="sidebar"
        class="fixed top-16 left-0 w-64 bg-white border-r min-h-screen transform -translate-x-full transition-all duration-300 ease-in-out z-30 md:hidden">
        <div class="p-4">
          <nav class="space-y-1" id="sidebarNav">
              <a href="<?= htmlspecialchars(view_url('vistas/public/inicio.php')) ?>"
                class="nav-link flex items-center p-2 rounded hover:bg-gray-50 text-gray-700">
                <i class="fas fa-home w-5"></i><span class="ml-2 sidebar-label">Inicio</span>
              </a>
          </nav>
        </div>
      </aside>
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