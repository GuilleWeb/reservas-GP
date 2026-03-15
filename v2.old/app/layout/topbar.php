<?php
require_once __DIR__ . '/../bootstrap.php';
$user = current_user();

$empresa_info = $GLOBALS['empresa_info'] ?? null;
$id_e = $empresa_info['slug'] ?? (request_id_e() ?: ($user['id_e'] ?? null));
$logo_path = ($empresa_info && !empty($empresa_info['logo_path'])) ? $empresa_info['logo_path'] : '../assets/logo.avif';
$empresa_nombre = $empresa_info ? (string) $empresa_info['nombre'] : 'Sistema';
$colores = ($empresa_info && !empty($empresa_info['colores_json'])) ? json_decode((string) $empresa_info['colores_json'], true) : [];
$color_p = is_array($colores) ? ($colores['principal'] ?? null) : null;

$script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$is_public_view = (strpos($script_name, '/v2/public/') !== false) || (strpos($script_name, '/v2/login.php') !== false);

function v2_base_url()
{
  $sn = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
  $pos = strpos($sn, '/v2/');
  if ($pos === false) {
    // fallback: strip filename
    return rtrim(dirname($sn), '/');
  }
  return substr($sn, 0, $pos) . '/v2';
}

function v2_url($path)
{
  $base = v2_base_url();
  $path = ltrim((string) $path, '/');
  return $base . '/' . $path;
}

function v2_link_with_slug($path, $id_e)
{
  $url = v2_url($path);
  if ($id_e) {
    $url .= (strpos($url, '?') === false ? '?' : '&') . 'id_e=' . rawurlencode((string) $id_e);
  }
  return $url;
}

$home_href = $is_public_view
  ? ($id_e ? v2_link_with_slug('public/inicio.php', $id_e) : v2_url('login.php'))
  : ($user ? (($user['rol'] ?? '') === 'superadmin' ? v2_url('sadmin/dashboard.php') : v2_url('admin/dashboard.php')) : v2_url('login.php'));
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Sistema</title>
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
                900: '<?= $color_p ?>b3'
              }
            }
          }
        }
      }
    </script>
  <?php endif; ?>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-50 min-h-screen font-sans">

  <header class="fixed top-0 left-0 right-0 bg-white shadow z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">

        <div class="flex items-center gap-2">
          <?php if (!$is_public_view): ?>
            <button
              class="hidden md:inline-flex text-gray-700 focus:outline-none h-10 w-10 items-center justify-center rounded-lg hover:bg-gray-50 border"
              type="button">
              <i data-lucide="chevrons-left"></i>
            </button>
          <?php endif; ?>
        </div>

        <a href="<?= htmlspecialchars($home_href) ?>" class="flex items-center space-x-3">
          <img src="<?= htmlspecialchars($logo_path) ?>" alt="logo" class="h-10 w-10 object-cover rounded-full">
          <div>
            <div class="font-semibold text-lg text-teal-700"><?= htmlspecialchars($empresa_nombre) ?></div>
            <?php if (!$is_public_view): ?>
              <small class="text-xs text-gray-500">Panel</small>
            <?php endif; ?>
          </div>
        </a>

        <?php if ($is_public_view): ?>
          <nav class="hidden md:flex flex-1 justify-center space-x-6">
              <?php if ($id_e): ?>
              <a href="<?= htmlspecialchars(v2_link_with_slug('public/inicio.php', $id_e)) ?>"
                class="text-gray-700 hover:text-teal-600 font-medium font-semibold nav-link">Inicio</a>
              <a href="<?= htmlspecialchars(v2_link_with_slug('public/sedes.php', $id_e)) ?>"
                class="text-gray-700 hover:text-teal-600 font-medium font-semibold nav-link">Sedes</a>
              <a href="<?= htmlspecialchars(v2_link_with_slug('public/agendar_cita.php', $id_e)) ?>"
                class="text-gray-700 hover:text-teal-600 font-medium font-semibold nav-link">Agendar Cita</a>
              <a href="<?= htmlspecialchars(v2_link_with_slug('public/blog.php', $id_e)) ?>"
                class="text-gray-700 hover:text-teal-600 font-medium font-semibold nav-link">Blog</a>
              <?php else: ?>
              <a href="<?= htmlspecialchars(v2_url('login.php')) ?>"
                class="text-gray-700 hover:text-teal-600 font-medium font-semibold nav-link">Inicio</a>
              <?php endif; ?>
          </nav>
        <?php endif; ?>

        <div class="flex items-center space-x-4">
          <?php if ($user): ?>
            <div class="text-sm text-gray-700">
              Hola, <span class="font-medium"><?= htmlspecialchars($user['nombre'] ?? '') ?></span>
            </div>
            <button id="logoutBtn" class="px-3 py-1 rounded-md bg-red-500 text-white hover:opacity-90" type="button">
              <i data-lucide="log-out" class="mr-1"></i> Salir
            </button>
          <?php else: ?>
            <a href="<?= htmlspecialchars(v2_link_with_slug('login.php', $id_e)) ?>"
              class="px-3 py-1 rounded-md bg-teal-600 text-white">Iniciar sesión</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <div class="pt-16">
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

            <?php if ($user): ?>
        <script>
            (function () {
              const btn = document.getElementById('logoutBtn');
              if (!btn) return;
              btn.addEventListener('click', async function () {
                try {
                  await fetch(<?= json_encode(v2_url('api/auth.php?action=logout')) ?>, { method: 'POST' });
                } catch (e) { }
                window.location.href = <?= json_encode(v2_link_with_slug('login.php', $id_e)) ?>;
              });
            })();
        </script>
            <?php endif; ?>