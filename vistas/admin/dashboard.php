<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$module = 'dashboard';
include __DIR__ . '/../../includes/topbar.php';
?>
<?php
// vistas/dashboard.php
$user = current_user();
$id_e = request_id_e();
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
  echo '<div class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow">Empresa no definida en la URL.</div>';
  return;
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

$stats = [];

if ($role === 'superadmin' && !$id_e) {
  $stats['empresas_activas'] = (int) $pdo->query("SELECT COUNT(*) FROM empresas WHERE activo=1")->fetchColumn();
  $stats['empresas_inactivas'] = (int) $pdo->query("SELECT COUNT(*) FROM empresas WHERE activo=0")->fetchColumn();
  $stats['planes_activos'] = (int) $pdo->query("SELECT COUNT(*) FROM planes WHERE activo=1")->fetchColumn();
  $stats['sucursales_activas'] = (int) $pdo->query("SELECT COUNT(*) FROM sucursales WHERE activo=1")->fetchColumn();
  $stats['usuarios_activos'] = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo=1")->fetchColumn();
  $stats['admins_activos'] = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo=1 AND rol='admin'")->fetchColumn();
  $stats['gerentes_activos'] = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo=1 AND rol='gerente'")->fetchColumn();
  $stats['empleados_activos'] = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo=1 AND rol='empleado'")->fetchColumn();
  $stats['clientes_activos'] = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo=1 AND rol='cliente'")->fetchColumn();

  $stmt = $pdo->prepare("SELECT COUNT(*) FROM mensajes_contacto WHERE estado='nuevo'");
  $stmt->execute();
  $stats['mensajes_nuevos'] = (int) $stmt->fetchColumn();

  try {
    $stmt = $pdo->prepare("SELECT ae.id, ae.tipo, ae.entidad, ae.entidad_id, ae.descripcion, ae.actor_rol, ae.created_at
                               FROM auditoria_eventos ae
                               ORDER BY ae.id DESC
                               LIMIT 12");
    $stmt->execute();
    $stats['movimientos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    $stats['movimientos'] = [];
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
      <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl border bg-gradient-to-br from-white to-teal-50 p-4">
          <div class="text-xs text-gray-500">Empresas activas</div>
          <div class="mt-1 text-2xl font-extrabold text-gray-900"><?= (int) ($stats['empresas_activas'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border bg-gradient-to-br from-white to-teal-50 p-4">
          <div class="text-xs text-gray-500">Sucursales activas</div>
          <div class="mt-1 text-2xl font-extrabold text-gray-900"><?= (int) ($stats['sucursales_activas'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border bg-gradient-to-br from-white to-teal-50 p-4">
          <div class="text-xs text-gray-500">Usuarios activos</div>
          <div class="mt-1 text-2xl font-extrabold text-gray-900"><?= (int) ($stats['usuarios_activos'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border bg-gradient-to-br from-white to-teal-50 p-4">
          <div class="text-xs text-gray-500">Mensajes nuevos</div>
          <div class="mt-1 text-2xl font-extrabold text-gray-900"><?= (int) ($stats['mensajes_nuevos'] ?? 0) ?></div>
        </div>
      </div>

      <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl border bg-white p-4">
          <div class="text-xs text-gray-500">Planes activos</div>
          <div class="mt-1 text-xl font-bold text-gray-900"><?= (int) ($stats['planes_activos'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border bg-white p-4">
          <div class="text-xs text-gray-500">Empresas inactivas</div>
          <div class="mt-1 text-xl font-bold text-gray-900"><?= (int) ($stats['empresas_inactivas'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border bg-white p-4">
          <div class="text-xs text-gray-500">Admins activos</div>
          <div class="mt-1 text-xl font-bold text-gray-900"><?= (int) ($stats['admins_activos'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border bg-white p-4">
          <div class="text-xs text-gray-500">Empleados activos</div>
          <div class="mt-1 text-xl font-bold text-gray-900"><?= (int) ($stats['empleados_activos'] ?? 0) ?></div>
        </div>
      </div>

      <div class="mt-4 rounded-2xl border bg-white p-4">
        <div class="flex items-center justify-between gap-3">
          <div class="font-semibold text-gray-900">Últimos movimientos</div>
          <div class="text-xs text-gray-500">Estado: <span class="font-semibold text-teal-700">Operando</span></div>
        </div>
        <div class="mt-3 overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-700">
              <tr>
                <th class="text-left px-3 py-2">Fecha</th>
                <th class="text-left px-3 py-2">Actor</th>
                <th class="text-left px-3 py-2">Evento</th>
                <th class="text-left px-3 py-2">Entidad</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <?php foreach (($stats['movimientos'] ?? []) as $m): ?>
                <tr class="hover:bg-gray-50">
                  <td class="px-3 py-2 font-mono text-xs text-gray-600"><?= htmlspecialchars($m['created_at'] ?? '') ?></td>
                  <td class="px-3 py-2"><?= htmlspecialchars($m['actor_rol'] ?? '') ?></td>
                  <td class="px-3 py-2">
                    <?= htmlspecialchars($m['tipo'] ?? '') ?>
                    <?= ($m['descripcion'] ?? '') ? ' - ' . htmlspecialchars($m['descripcion']) : '' ?>
                  </td>
                  <td class="px-3 py-2"><?= htmlspecialchars($m['entidad'] ?? '') ?> #<?= (int) ($m['entidad_id'] ?? 0) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($stats['movimientos'] ?? [])): ?>
                <tr>
                  <td class="px-3 py-3 text-gray-500" colspan="4">Aún no hay movimientos registrados.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php else: ?>
<?php 
$s_stats = [];
try {
   $s_stats['citas_hoy'] = (int) $pdo->query("SELECT COUNT(*) FROM citas WHERE empresa_id = " . intval($id_e) . " AND DATE(fecha_cita) = CURDATE()")->fetchColumn();
   $s_stats['citas_total'] = (int) $pdo->query("SELECT COUNT(*) FROM citas WHERE empresa_id = " . intval($id_e))->fetchColumn();
   $s_stats['sucursales'] = (int) $pdo->query("SELECT COUNT(*) FROM sucursales WHERE empresa_id = " . intval($id_e))->fetchColumn();
   $s_stats['empleados'] = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE empresa_id = " . intval($id_e) . " AND rol='empleado'")->fetchColumn();
   $s_stats['clientes'] = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE empresa_id = " . intval($id_e) . " AND rol='cliente'")->fetchColumn();
   $s_stats['servicios'] = (int) $pdo->query("SELECT COUNT(*) FROM servicios WHERE empresa_id = " . intval($id_e))->fetchColumn();
   
   $stmt = $pdo->prepare("SELECT c.fecha_cita, c.hora_inicio, c.estado, s.nombre as servicio, u.nombre as cliente 
                          FROM citas c 
                          LEFT JOIN servicios s ON c.servicio_id = s.id 
                          LEFT JOIN usuarios u ON c.cliente_id = u.id
                          WHERE c.empresa_id = ? 
                          ORDER BY c.fecha_cita DESC, c.hora_inicio DESC LIMIT 5");
   $stmt->execute([$id_e]);
   $s_stats['ultimas_citas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { }
?>
      <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl border bg-gradient-to-br from-white to-teal-50 p-4">
          <div class="text-xs text-gray-500">Citas Hoy</div>
          <div class="mt-1 text-2xl font-extrabold text-gray-900"><?= (int)($s_stats['citas_hoy'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border bg-gradient-to-br from-white to-teal-50 p-4">
          <div class="text-xs text-gray-500">Citas Totales</div>
          <div class="mt-1 text-2xl font-extrabold text-gray-900"><?= (int)($s_stats['citas_total'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border bg-gradient-to-br from-white to-teal-50 p-4">
          <div class="text-xs text-gray-500">Sucursales</div>
          <div class="mt-1 text-2xl font-extrabold text-gray-900"><?= (int)($s_stats['sucursales'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border bg-gradient-to-br from-white to-teal-50 p-4">
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
                  <td class="px-3 py-2 font-mono text-xs text-gray-600"><?= htmlspecialchars($c['fecha_cita']) ?> <?= substr($c['hora_inicio'], 0, 5) ?></td>
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
