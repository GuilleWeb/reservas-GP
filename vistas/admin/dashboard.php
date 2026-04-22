<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$module = 'dashboard';
?>
<?php
// vistas/dashboard.php
$user = current_user();
$requested_id_e = request_id_e();
$resolved_id_e = resolve_private_empresa_id($user);
$id_e = $role === 'superadmin' ? $requested_id_e : $resolved_id_e;
$sucursal_slug = request_sucursal_slug();
$target_user_id = isset($_GET['_user_id']) ? intval($_GET['_user_id']) : null;

if (!$user) {
  echo '<div class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow">Debes iniciar sesión.</div>';
  return;
}

$role = $user['rol'] ?? null;
if (!$role) {
  $role = 'usuario';
}

$effective_role = ($role === 'superadmin' && $id_e) ? 'admin' : $role;

if (!$id_e && $role !== 'superadmin') {
  http_response_code(403);
  include __DIR__ . '/../../includes/errors/403.php';
  exit;
}

$can_view = true;
if (in_array($effective_role, ['empleado', 'cliente'], true)) {
  if ($target_user_id === null || intval($user['id']) !== $target_user_id) {
    $can_view = false;
  }
}

if (!$can_view) {
  echo '<div class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow">No autorizado.</div>';
  return;
}

include __DIR__ . '/../../includes/topbar.php';

$stats = [];

// Stats para superadmin
if ($role === 'superadmin' && !$id_e) {
  $stats['empresas_activas'] = (int) $pdo->query("SELECT COUNT(*) FROM empresas WHERE activo=1")->fetchColumn();
  $stats['empresas_inactivas'] = (int) $pdo->query("SELECT COUNT(*) FROM empresas WHERE activo=0")->fetchColumn();
  $stats['planes_activos'] = (int) $pdo->query("SELECT COUNT(*) FROM planes WHERE activo=1")->fetchColumn();
  $stats['sucursales_activas'] = (int) $pdo->query("SELECT COUNT(*) FROM sucursales WHERE activo=1")->fetchColumn();
  $stats['usuarios_activos'] = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo=1")->fetchColumn();
  $stats['citas_hoy'] = (int) $pdo->query("SELECT COUNT(*) FROM citas WHERE DATE(inicio) = CURDATE()")->fetchColumn();
  $stats['citas_mes'] = (int) $pdo->query("SELECT COUNT(*) FROM citas WHERE YEAR(inicio)=YEAR(CURDATE()) AND MONTH(inicio)=MONTH(CURDATE())")->fetchColumn();

  $stmt = $pdo->prepare("SELECT COUNT(*) FROM mensajes_contacto WHERE estado='nuevo'");
  $stmt->execute();
  $stats['mensajes_nuevos'] = (int) $stmt->fetchColumn();

  try {
    $stmt = $pdo->prepare("SELECT ae.id, ae.tipo, ae.entidad, ae.entidad_id, ae.descripcion, ae.actor_rol, ae.created_at
                               FROM auditoria_eventos ae
                               ORDER BY ae.id DESC
                               LIMIT 10");
    $stmt->execute();
    $stats['movimientos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    $stats['movimientos'] = [];
  }

  // Top empresas por citas
  try {
    $stmt = $pdo->prepare("SELECT e.nombre, COUNT(c.id) as total_citas
                           FROM empresas e
                           LEFT JOIN citas c ON c.empresa_id = e.id
                           WHERE e.activo = 1
                           GROUP BY e.id
                           ORDER BY total_citas DESC
                           LIMIT 5");
    $stmt->execute();
    $topEmpresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    $topEmpresas = [];
  }
}
?>

<div class="max-w-7xl mx-auto">
  <div class="bg-white rounded-2xl shadow p-6">
    <div class="text-sm text-gray-500">Dashboard</div>
    <div class="mt-1 text-2xl font-extrabold text-gray-900">
      <?php if ($role === 'superadmin'): ?>
        Panel Empresa
      <?php else: ?>
        <?= htmlspecialchars($id_e) ?>
        <?php if ($sucursal_slug): ?> / <?= htmlspecialchars($sucursal_slug) ?><?php endif; ?>
      <?php endif; ?>
    </div>
    <div class="mt-2 text-gray-700">
      Usuario: <span class="font-semibold"><?= htmlspecialchars($user['nombre'] ?? '') ?></span>
      (<?= htmlspecialchars($role) ?>)
    </div>

    <?php if ($role === 'superadmin' && !$id_e): ?>
      <!-- Dashboard Superadmin Moderno - Dark Mode -->
      <div id="dashboard-root" class="mt-6 -mx-6 -mb-6 min-h-screen bg-slate-900 text-slate-100 transition-colors duration-300">

        <!-- Header -->
        <div class="border-b border-slate-800 bg-slate-900/50 backdrop-blur px-6 py-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-cyan-600 flex items-center justify-center shadow-lg shadow-teal-500/20">
                <i data-lucide="layout-dashboard" class="w-5 h-5 text-white"></i>
              </div>
              <div>
                <h1 class="text-xl font-bold text-white">Panel Superadmin</h1>
                <p class="text-xs text-slate-400">Control total del sistema</p>
              </div>
            </div>

            <div class="flex items-center gap-4">
              <!-- Theme Toggle -->
              <button id="theme-toggle" class="relative w-14 h-8 rounded-full bg-slate-800 border border-slate-700 flex items-center px-1 transition-colors hover:border-slate-600">
                <span class="sr-only">Cambiar tema</span>
                <i data-lucide="sun" class="w-4 h-4 text-amber-400 absolute left-2"></i>
                <i data-lucide="moon" class="w-4 h-4 text-cyan-400 absolute right-2"></i>
                <div id="theme-indicator" class="w-6 h-6 rounded-full bg-slate-600 shadow-md transform transition-transform translate-x-6"></div>
              </button>

              <div class="flex items-center gap-3 pl-4 border-l border-slate-800">
                <div class="text-right hidden sm:block">
                  <p class="text-sm font-medium text-white"><?= htmlspecialchars($user['nombre'] ?? '') ?></p>
                  <p class="text-xs text-teal-400">Superadmin</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-slate-700 to-slate-600 border border-slate-500 flex items-center justify-center">
                  <i data-lucide="user" class="w-5 h-5 text-slate-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="p-6">
          <!-- Stats Principales -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="group relative overflow-hidden rounded-2xl bg-slate-800/50 border border-slate-700/50 p-5 hover:border-teal-500/30 transition-all">
              <div class="absolute inset-0 bg-gradient-to-br from-teal-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
              <div class="relative flex items-start justify-between">
                <div>
                  <p class="text-slate-400 text-sm font-medium">Empresas Activas</p>
                  <p class="text-3xl font-bold text-white mt-1"><?= number_format($stats['empresas_activas']) ?></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-teal-500/10 flex items-center justify-center">
                  <i data-lucide="building-2" class="w-6 h-6 text-teal-400"></i>
                </div>
              </div>
            </div>

            <div class="group relative overflow-hidden rounded-2xl bg-slate-800/50 border border-slate-700/50 p-5 hover:border-cyan-500/30 transition-all">
              <div class="absolute inset-0 bg-gradient-to-br from-cyan-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
              <div class="relative flex items-start justify-between">
                <div>
                  <p class="text-slate-400 text-sm font-medium">Usuarios Totales</p>
                  <p class="text-3xl font-bold text-white mt-1"><?= number_format($stats['usuarios_activos']) ?></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-cyan-500/10 flex items-center justify-center">
                  <i data-lucide="users" class="w-6 h-6 text-cyan-400"></i>
                </div>
              </div>
            </div>

            <div class="group relative overflow-hidden rounded-2xl bg-slate-800/50 border border-slate-700/50 p-5 hover:border-emerald-500/30 transition-all">
              <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
              <div class="relative flex items-start justify-between">
                <div>
                  <p class="text-slate-400 text-sm font-medium">Citas Hoy</p>
                  <p class="text-3xl font-bold text-white mt-1"><?= number_format($stats['citas_hoy']) ?></p>
                  <p class="text-xs text-emerald-400 mt-2"><?= number_format($stats['citas_mes']) ?> este mes</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                  <i data-lucide="calendar-check" class="w-6 h-6 text-emerald-400"></i>
                </div>
              </div>
            </div>

            <div class="group relative overflow-hidden rounded-2xl bg-slate-800/50 border border-slate-700/50 p-5 hover:border-amber-500/30 transition-all">
              <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
              <div class="relative flex items-start justify-between">
                <div>
                  <p class="text-slate-400 text-sm font-medium">Mensajes Nuevos</p>
                  <p class="text-3xl font-bold text-white mt-1"><?= number_format($stats['mensajes_nuevos']) ?></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center">
                  <i data-lucide="message-square" class="w-6 h-6 text-amber-400"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Stats Secundarias -->
          <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3 mb-6">
            <div class="rounded-xl bg-slate-800/30 border border-slate-700/30 p-3 text-center">
              <p class="text-2xl font-bold text-white"><?= number_format($stats['sucursales_activas']) ?></p>
              <p class="text-xs text-slate-400">Sucursales</p>
            </div>
            <div class="rounded-xl bg-slate-800/30 border border-slate-700/30 p-3 text-center">
              <p class="text-2xl font-bold text-white"><?= number_format($stats['planes_activos']) ?></p>
              <p class="text-xs text-slate-400">Planes</p>
            </div>
            <div class="rounded-xl bg-slate-800/30 border border-slate-700/30 p-3 text-center">
              <p class="text-2xl font-bold text-slate-400"><?= number_format($stats['empresas_inactivas']) ?></p>
              <p class="text-xs text-slate-500">Inactivas</p>
            </div>
            <div class="rounded-xl bg-slate-800/30 border border-slate-700/30 p-3 text-center">
              <p class="text-2xl font-bold text-teal-400"><?= number_format($stats['citas_mes']) ?></p>
              <p class="text-xs text-slate-400">Citas/mes</p>
            </div>
            <div class="rounded-xl bg-slate-800/30 border border-slate-700/30 p-3 text-center col-span-2">
              <p class="text-xs text-slate-400 mb-1">Estado del Sistema</p>
              <div class="flex items-center justify-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-sm font-medium text-emerald-400">Operando</span>
              </div>
            </div>
          </div>

          <!-- Grid Principal -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Movimientos -->
            <div class="lg:col-span-2 rounded-2xl bg-slate-800/50 border border-slate-700/50 overflow-hidden">
              <div class="px-6 py-4 border-b border-slate-700/50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <i data-lucide="activity" class="w-5 h-5 text-teal-400"></i>
                  <h3 class="font-semibold text-white">Movimientos Recientes</h3>
                </div>
                <a href="<?= view_url('vistas/admin/movimientos.php') ?>" class="text-sm text-teal-400 hover:text-teal-300">Ver todo</a>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead class="bg-slate-900/50 text-slate-400">
                    <tr>
                      <th class="text-left px-4 py-3 font-medium">Fecha</th>
                      <th class="text-left px-4 py-3 font-medium">Actor</th>
                      <th class="text-left px-4 py-3 font-medium">Evento</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-700/50">
                    <?php foreach ($stats['movimientos'] as $m): ?>
                      <tr class="hover:bg-slate-700/30 transition-colors">
                        <td class="px-4 py-3 text-slate-400 font-mono text-xs"><?= htmlspecialchars(substr($m['created_at'], 0, 16)) ?></td>
                        <td class="px-4 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-700 text-slate-300"><?= htmlspecialchars($m['actor_rol'] ?? 'system') ?></span></td>
                        <td class="px-4 py-3 text-slate-200"><?= htmlspecialchars($m['tipo'] ?? '') ?> <span class="text-slate-500">- <?= htmlspecialchars(substr($m['descripcion'] ?? '', 0, 40)) ?></span></td>
                      </tr>
                    <?php endforeach; ?>
                    <?php if (empty($stats['movimientos'])): ?>
                      <tr><td colspan="3" class="px-4 py-8 text-center text-slate-500"><i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-50"></i><p>No hay movimientos</p></td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Top Empresas -->
            <div class="rounded-2xl bg-slate-800/50 border border-slate-700/50 overflow-hidden">
              <div class="px-6 py-4 border-b border-slate-700/50">
                <div class="flex items-center gap-2">
                  <i data-lucide="trophy" class="w-5 h-5 text-amber-400"></i>
                  <h3 class="font-semibold text-white">Top Empresas</h3>
                </div>
                <p class="text-xs text-slate-400 mt-1">Por número de citas</p>
              </div>
              <div class="p-4 space-y-3">
                <?php foreach ($topEmpresas as $idx => $emp):
                  $maxCitas = !empty($topEmpresas) && isset($topEmpresas[0]['total_citas']) ? max(1, (int)$topEmpresas[0]['total_citas']) : 1;
                  $pct = min(100, ((int)$emp['total_citas'] / $maxCitas) * 100);
                ?>
                  <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-900/50 border border-slate-700/30 hover:border-teal-500/30 transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-500 to-cyan-600 flex items-center justify-center text-white font-bold text-sm"><?= $idx + 1 ?></div>
                    <div class="flex-1 min-w-0">
                      <p class="font-medium text-white truncate"><?= htmlspecialchars($emp['nombre']) ?></p>
                      <p class="text-xs text-slate-400"><?= number_format($emp['total_citas']) ?> citas</p>
                    </div>
                    <div class="w-16 bg-slate-700 rounded-full h-1.5 overflow-hidden">
                      <div class="h-full bg-gradient-to-r from-teal-500 to-cyan-500 rounded-full" style="width: <?= $pct ?>%"></div>
                    </div>
                  </div>
                <?php endforeach; ?>
                <?php if (empty($topEmpresas)): ?>
                  <div class="text-center py-8 text-slate-500"><i data-lucide="bar-chart-2" class="w-8 h-8 mx-auto mb-2 opacity-50"></i><p class="text-sm">Sin datos</p></div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Accesos Rápidos -->
          <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="<?= view_url('vistas/admin/empresas.php') ?>" class="group flex items-center gap-4 p-4 rounded-xl bg-gradient-to-br from-teal-500/10 to-teal-600/5 border border-teal-500/20 hover:border-teal-500/40 transition-all">
              <div class="w-12 h-12 rounded-xl bg-teal-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                <i data-lucide="building-2" class="w-6 h-6 text-teal-400"></i>
              </div>
              <div><p class="font-semibold text-white">Empresas</p><p class="text-xs text-slate-400">Gestionar</p></div>
              <i data-lucide="arrow-right" class="w-5 h-5 text-slate-500 ml-auto group-hover:text-teal-400 group-hover:translate-x-1 transition-all"></i>
            </a>

            <a href="<?= view_url('vistas/admin/planes.php') ?>" class="group flex items-center gap-4 p-4 rounded-xl bg-gradient-to-br from-cyan-500/10 to-cyan-600/5 border border-cyan-500/20 hover:border-cyan-500/40 transition-all">
              <div class="w-12 h-12 rounded-xl bg-cyan-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                <i data-lucide="credit-card" class="w-6 h-6 text-cyan-400"></i>
              </div>
              <div><p class="font-semibold text-white">Planes</p><p class="text-xs text-slate-400">Configurar</p></div>
              <i data-lucide="arrow-right" class="w-5 h-5 text-slate-500 ml-auto group-hover:text-cyan-400 group-hover:translate-x-1 transition-all"></i>
            </a>

            <a href="<?= view_url('vistas/admin/mensajes.php') ?>" class="group flex items-center gap-4 p-4 rounded-xl bg-gradient-to-br from-amber-500/10 to-amber-600/5 border border-amber-500/20 hover:border-amber-500/40 transition-all">
              <div class="w-12 h-12 rounded-xl bg-amber-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                <i data-lucide="message-square" class="w-6 h-6 text-amber-400"></i>
              </div>
              <div><p class="font-semibold text-white">Mensajes</p><p class="text-xs text-slate-400"><?= $stats['mensajes_nuevos'] ?> nuevos</p></div>
              <i data-lucide="arrow-right" class="w-5 h-5 text-slate-500 ml-auto group-hover:text-amber-400 group-hover:translate-x-1 transition-all"></i>
            </a>

            <a href="<?= view_url('vistas/admin/ajustes.php') ?>" class="group flex items-center gap-4 p-4 rounded-xl bg-gradient-to-br from-purple-500/10 to-purple-600/5 border border-purple-500/20 hover:border-purple-500/40 transition-all">
              <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                <i data-lucide="settings" class="w-6 h-6 text-purple-400"></i>
              </div>
              <div><p class="font-semibold text-white">Ajustes</p><p class="text-xs text-slate-400">Configuración</p></div>
              <i data-lucide="arrow-right" class="w-5 h-5 text-slate-500 ml-auto group-hover:text-purple-400 group-hover:translate-x-1 transition-all"></i>
            </a>
          </div>

          <!-- Theme Toggle Script -->
          <script>
            (function() {
              const root = document.getElementById('dashboard-root');
              const toggle = document.getElementById('theme-toggle');
              const indicator = document.getElementById('theme-indicator');
              const STORAGE_KEY = 'reservasgp-theme';
              let isDark = localStorage.getItem(STORAGE_KEY) !== 'light';

              function updateTheme() {
                if (isDark) {
                  indicator.style.transform = 'translateX(24px)';
                  indicator.classList.remove('bg-slate-200');
                  indicator.classList.add('bg-slate-600');
                } else {
                  indicator.style.transform = 'translateX(0)';
                  indicator.classList.remove('bg-slate-600');
                  indicator.classList.add('bg-slate-200');
                }
              }
              updateTheme();

              toggle.addEventListener('click', function() {
                isDark = !isDark;
                localStorage.setItem(STORAGE_KEY, isDark ? 'dark' : 'light');
                updateTheme();
              });
            })();
          </script>
        </div>
      </div>
    <?php else: ?>
<?php 
$s_stats = [];
try {
   $eid = (int) $id_e;
   $s_stats['citas_hoy'] = (int) $pdo->query("SELECT COUNT(*) FROM citas WHERE empresa_id = {$eid} AND DATE(inicio) = CURDATE()")->fetchColumn();
   $s_stats['sucursales'] = (int) $pdo->query("SELECT COUNT(*) FROM sucursales WHERE empresa_id = {$eid} AND activo = 1")->fetchColumn();
   $s_stats['empleados'] = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE empresa_id = {$eid} AND rol IN ('admin','gerente','empleado') AND activo = 1")->fetchColumn();
   $s_stats['clientes'] = (int) $pdo->query("SELECT COUNT(*) FROM clientes c WHERE c.empresa_id = {$eid} AND c.activo = 1")->fetchColumn();
   $s_stats['servicios'] = (int) $pdo->query("SELECT COUNT(*) FROM servicios WHERE empresa_id = {$eid} AND activo = 1")->fetchColumn();
   $s_stats['citas_mes'] = (int) $pdo->query("SELECT COUNT(*) FROM citas WHERE empresa_id = {$eid} AND YEAR(inicio)=YEAR(CURDATE()) AND MONTH(inicio)=MONTH(CURDATE())")->fetchColumn();
   $s_stats['citas_mes_prev'] = (int) $pdo->query("SELECT COUNT(*) FROM citas WHERE empresa_id = {$eid} AND YEAR(inicio)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(inicio)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))")->fetchColumn();
   $s_stats['ingresos_mes'] = (float) $pdo->query("SELECT COALESCE(SUM(s.precio_base),0) FROM citas c JOIN servicios s ON s.id=c.servicio_id WHERE c.empresa_id = {$eid} AND c.estado IN ('confirmada','completada') AND YEAR(c.inicio)=YEAR(CURDATE()) AND MONTH(c.inicio)=MONTH(CURDATE())")->fetchColumn();
   $s_stats['ingresos_mes_prev'] = (float) $pdo->query("SELECT COALESCE(SUM(s.precio_base),0) FROM citas c JOIN servicios s ON s.id=c.servicio_id WHERE c.empresa_id = {$eid} AND c.estado IN ('confirmada','completada') AND YEAR(c.inicio)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(c.inicio)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))")->fetchColumn();

   $stmt = $pdo->prepare("SELECT c.inicio, c.estado, s.nombre as servicio, c.cliente_nombre as cliente
                          FROM citas c
                          LEFT JOIN servicios s ON c.servicio_id = s.id
                          WHERE c.empresa_id = ?
                          ORDER BY c.inicio DESC LIMIT 8");
   $stmt->execute([$eid]);
   $s_stats['ultimas_citas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { }
?>
      <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl border bg-white p-4">
          <div class="text-xs text-gray-500">Citas Hoy</div>
          <div class="mt-1 text-2xl font-extrabold text-gray-900"><?= (int)($s_stats['citas_hoy'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border bg-white p-4">
          <div class="text-xs text-gray-500">Sucursales</div>
          <div class="mt-1 text-2xl font-extrabold text-gray-900"><?= (int)($s_stats['sucursales'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border bg-white p-4">
          <div class="text-xs text-gray-500">Empleados</div>
          <div class="mt-1 text-2xl font-extrabold text-gray-900"><?= (int)($s_stats['empleados'] ?? 0) ?></div>
        </div>
      </div>

      <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl border bg-white p-4 w-full">
          <div class="text-xs text-gray-500">Clientes</div>
          <div class="mt-1 text-xl font-bold text-gray-900"><?= (int)($s_stats['clientes'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border bg-white p-4 w-full">
          <div class="text-xs text-gray-500">Servicios</div>
          <div class="mt-1 text-xl font-bold text-gray-900"><?= (int)($s_stats['servicios'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border bg-white p-4 w-full">
          <div class="text-xs text-gray-500">Citas este mes</div>
          <div class="mt-1 text-xl font-bold text-gray-900"><?= (int)($s_stats['citas_mes'] ?? 0) ?></div>
          <div class="text-[11px] text-gray-500">Mes anterior: <?= (int)($s_stats['citas_mes_prev'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border bg-white p-4 w-full">
          <div class="text-xs text-gray-500">Ingresos estimados del mes</div>
          <div class="mt-1 text-xl font-bold text-gray-900">$<?= number_format((float)($s_stats['ingresos_mes'] ?? 0), 2) ?></div>
          <div class="text-[11px] text-gray-500">Mes anterior: $<?= number_format((float)($s_stats['ingresos_mes_prev'] ?? 0), 2) ?></div>
        </div>
      </div>

      <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-2xl border bg-white p-4">
          <div class="text-sm font-semibold text-gray-800 mb-2">Comparativa de Citas</div>
          <div class="relative h-56">
            <canvas id="chartCitasCompare"></canvas>
          </div>
        </div>
        <div class="rounded-2xl border bg-white p-4">
          <div class="text-sm font-semibold text-gray-800 mb-2">Comparativa de Ingresos</div>
          <div class="relative h-56">
            <canvas id="chartIngresosCompare"></canvas>
          </div>
        </div>
      </div>

      <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="<?= view_url('vistas/admin/admin-citas.php', $id_e) ?>" class="block rounded-xl border p-4 border-teal-100 bg-teal-50 hover:bg-teal-100 transition">
          <div class="font-semibold text-teal-900">Citas</div>
          <div class="text-sm text-teal-700">Ver y gestionar agenda.</div>
        </a>
        <a href="<?= view_url('vistas/admin/sucursales.php', $id_e) ?>" class="block rounded-xl border p-4 border-teal-100 bg-teal-50 hover:bg-teal-100 transition">
          <div class="font-semibold text-teal-900">Sedes</div>
          <div class="text-sm text-teal-700">Ver sucursales.</div>
        </a>
        <?php if (has_permission('permiso_leer')): ?>
          <a href="<?= view_url('vistas/admin/ajustes.php', $id_e) ?>" class="block rounded-xl border p-4 border-teal-100 bg-teal-50 hover:bg-teal-100 transition">
            <div class="font-semibold text-teal-900">Administración</div>
            <div class="text-sm text-teal-700">Panel administrativo.</div>
          </a>
        <?php endif; ?>
      </div>

      <div class="mt-6 rounded-2xl border bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between gap-3">
          <div class="font-semibold text-gray-900">Últimas Citas registradas</div>
        </div>
        <div class="mt-3 overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-700">
              <tr>
                <th class="text-left px-3 py-2">Fecha/Hora</th>
                <th class="text-left px-3 py-2">Cliente</th>
                <th class="text-left px-3 py-2">Servicio</th>
                <th class="text-left px-3 py-2">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y relative">
              <?php foreach (($s_stats['ultimas_citas'] ?? []) as $c): ?>
                <tr class="hover:bg-gray-50">
                  <td class="px-3 py-2 font-mono text-xs text-gray-600"><?= htmlspecialchars($c['inicio'] ?? '') ?></td>
                  <td class="px-3 py-2"><?= htmlspecialchars($c['cliente'] ?? 'N/A') ?></td>
                  <td class="px-3 py-2"><?= htmlspecialchars($c['servicio'] ?? 'N/A') ?></td>
                  <td class="px-3 py-2">
                    <?php if($c['estado'] === 'completada'): ?>
                        <span class="inline-flex px-2 py-0.5 rounded text-xs bg-green-100 text-green-800">Completada</span>
                    <?php elseif($c['estado'] === 'cancelada'): ?>
                        <span class="inline-flex px-2 py-0.5 rounded text-xs bg-red-100 text-red-800">Cancelada</span>
                    <?php else: ?>
                        <span class="inline-flex px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-800"><?= ucfirst($c['estado']) ?></span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($s_stats['ultimas_citas'] ?? [])): ?>
                <tr>
                  <td class="px-3 py-3 text-gray-500 text-center" colspan="4">No hay citas recientes registradas.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php
include __DIR__ . '/../../includes/footer.php';

if (!($role === 'superadmin' && !$id_e)):
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  (function(){
    const teal = <?= json_encode($color_p ?: '#0d9488') ?>;
    const gray = '#94a3b8';
    const labels = ['Mes anterior', 'Mes actual'];
    const citasData = [<?= (int)($s_stats['citas_mes_prev'] ?? 0) ?>, <?= (int)($s_stats['citas_mes'] ?? 0) ?>];
    const ingresosData = [<?= (float)($s_stats['ingresos_mes_prev'] ?? 0) ?>, <?= (float)($s_stats['ingresos_mes'] ?? 0) ?>];
    function hexToRgba(hex, alpha) {
      const h = String(hex || '').replace('#', '');
      const full = h.length === 3 ? h.split('').map(ch => ch + ch).join('') : h.padEnd(6, '0').slice(0, 6);
      const n = parseInt(full, 16);
      const r = (n >> 16) & 255;
      const g = (n >> 8) & 255;
      const b = n & 255;
      return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }
    const opts = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#64748b' } },
        y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,.2)' }, ticks: { color: '#64748b' } }
      }
    };
    const el1 = document.getElementById('chartCitasCompare');
    const el2 = document.getElementById('chartIngresosCompare');
    if (el1) {
      new Chart(el1, {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Citas', data: citasData, backgroundColor: [gray, hexToRgba(teal, 0.72)], borderColor: [gray, teal], borderWidth: 1.2, borderRadius: 8 }] },
        options: opts
      });
    }
    if (el2) {
      new Chart(el2, {
        type: 'line',
        data: { labels, datasets: [{ label: 'Ingresos', data: ingresosData, borderColor: teal, backgroundColor: hexToRgba(teal, 0.12), tension: 0.35, fill: true, pointBackgroundColor: teal }] },
        options: opts
      });
    }
  })();
</script>
<?php endif; ?>
