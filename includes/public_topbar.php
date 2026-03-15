<?php
// includes/public_topbar.php
// Usa las variables de empresa ya definidas en bootstrap.php / context.php.
// Solo agrega lo específico del topbar público (sin sidebar de roles).

if (!function_exists('get_current_empresa')) {
  require_once __DIR__ . '/context.php';
}

$module = isset($module) ? ' | ' . $module : '';
$csrf = generate_csrf();
$user = $user ?? current_user();
$id_e = get_empresa_id();
$is_public = true;
$is_public_view = true;

// Las variables $empresa_nombre, $logo_path, $color_p, etc.
// vienen de bootstrap.php. Si este topbar se usa sin bootstrap, definir fallbacks.
if (!isset($empresa_nombre)) {
  $empresa_nombre = 'Sistema de reservas GP';
}
if (!isset($logo_path)) {
  $logo_path = app_url('assets/logo.avif');
}
if (!isset($color_p)) {
  $color_p = '#46C9BB';
}
if (!isset($empresa_descripcion)) {
  $empresa_descripcion = '';
}
if (!isset($redes)) {
  $redes = [];
}
if (!isset($email_contacto)) {
  $email_contacto = 'soporte@reservasgp.com';
}
if (!isset($telefono_contacto)) {
  $telefono_contacto = '+502 5103-6244';
}
if (!isset($horaios)) {
  $horaios = 'Siempre abierto';
}
if (!isset($direccion)) {
  $direccion = 'Negocio en línea';
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
  <meta property="og:title" content="Sistema">
  <meta property="og:description" content="Agenda en línea y gestiona tu negocio desde un solo lugar.">
  <meta property="og:image" content="<?= htmlspecialchars(app_url('assets/logo.avif')) ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Sistema">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Sistema">
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
</head>

<body class="bg-gray-50 min-h-screen font-sans">

  <header class="fixed top-0 left-0 right-0 bg-white shadow z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">

        <div class="flex items-center gap-2">
          <button id="menuToggle" class="md:hidden text-gray-700 focus:outline-none">
            <i data-lucide="menu" class="text-xl"></i>
          </button>
        </div>

        <a href="<?= htmlspecialchars(view_url('vistas/public/inicio.php', $id_e)) ?>"
          class="flex items-center space-x-3">
          <img src="<?= htmlspecialchars($logo_path) ?>" alt="logo" class="h-10 w-10 object-cover rounded-full">
          <div>
            <div class="font-semibold text-lg text-teal-700"><?= $empresa_nombre ?></div>
          </div>
        </a>

        <nav class="hidden md:flex flex-1 justify-center space-x-6">
          <a href="<?= htmlspecialchars(view_url('vistas/public/inicio.php')) ?>"
            class="text-gray-700 hover:text-teal-600 font-medium font-semibold nav-link">Inicio</a>
        </nav>

      </div>
    </div>
  </header>

  <div class="pt-16 flex">
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
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-40 hidden z-20 md:hidden"></div>
    <main class="flex-1 w-full p-6 bg-gradient-to-br from-teal-100 via-white to-teal-50 overflow-x-hidden">