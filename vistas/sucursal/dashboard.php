<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'dashboard';
include __DIR__ . '/../../includes/topbar.php';

$s_stats = [];
try {
   $s_stats['citas_hoy'] = (int) $pdo->query("SELECT COUNT(*) FROM citas WHERE empresa_id = " . intval($id_e) . " AND DATE(fecha_cita) = CURDATE()")->fetchColumn();
   $s_stats['citas_pendientes'] = (int) $pdo->query("SELECT COUNT(*) FROM citas WHERE empresa_id = " . intval($id_e) . " AND estado='pendiente'")->fetchColumn();
   $s_stats['citas_total'] = (int) $pdo->query("SELECT COUNT(*) FROM citas WHERE empresa_id = " . intval($id_e))->fetchColumn();
   
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

<div class="max-w-7xl mx-auto">
  <div class="bg-white rounded-2xl shadow p-6 border">
    <div class="text-sm text-gray-500">Dashboard</div>
    <div class="mt-1 text-2xl font-extrabold text-gray-900">
      Panel Gerente - <?= htmlspecialchars($id_e) ?>
    </div>
    <div class="mt-2 text-gray-700">
      Usuario: <span class="font-semibold"><?= htmlspecialchars($user['nombre'] ?? '') ?></span>
      (<?= htmlspecialchars($role) ?>)
    </div>

    <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 shadow-sm transition hover:shadow-md">
        <div class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-2">Citas hoy</div>
        <div class="text-3xl font-black text-teal-600"><?= (int)($s_stats['citas_hoy'] ?? 0) ?></div>
      </div>
      <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 shadow-sm transition hover:shadow-md">
        <div class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-2">Citas pendientes</div>
        <div class="text-3xl font-black text-teal-600"><?= (int)($s_stats['citas_pendientes'] ?? 0) ?></div>
      </div>
      <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 shadow-sm transition hover:shadow-md">
        <div class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-2">Total Histórico</div>
        <div class="text-3xl font-black text-teal-600"><?= (int)($s_stats['citas_total'] ?? 0) ?></div>
      </div>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <a href="<?= view_url('vistas/sucursal/admin-citas.php', $id_e) ?>"
        class="block rounded-xl border p-4 hover:bg-teal-100 bg-teal-50 border-teal-100 transition">
        <div class="font-semibold text-teal-900">Citas</div>
        <div class="text-sm text-teal-700">Ver y gestionar agenda de la sucursal.</div>
      </a>
      <a href="<?= view_url('vistas/sucursal/sucursales.php', $id_e) ?>"
        class="block rounded-xl border p-4 hover:bg-teal-100 bg-teal-50 border-teal-100 transition">
        <div class="font-semibold text-teal-900">Mi Sucursal</div>
        <div class="text-sm text-teal-700">Ver información de la sucursal.</div>
      </a>
      <a href="<?= view_url('vistas/sucursal/ajustes.php', $id_e) ?>"
        class="block rounded-xl border p-4 hover:bg-teal-100 bg-teal-50 border-teal-100 transition">
        <div class="font-semibold text-teal-900">Ajustes</div>
        <div class="text-sm text-teal-700">Configuración de la cuenta.</div>
      </a>
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
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>