<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'dashboard';
include __DIR__ . '/../../includes/topbar.php';

$s_stats = [];
if ($user && isset($user['id'])) {
   try {
      $s_stats['citas_proximas'] = (int) $pdo->query("SELECT COUNT(*) FROM citas WHERE cliente_id = " . intval($user['id']) . " AND fecha_cita >= CURDATE() AND estado='pendiente'")->fetchColumn();
      $s_stats['citas_pasadas'] = (int) $pdo->query("SELECT COUNT(*) FROM citas WHERE cliente_id = " . intval($user['id']) . " AND fecha_cita < CURDATE()")->fetchColumn();
      $s_stats['citas_completadas'] = (int) $pdo->query("SELECT COUNT(*) FROM citas WHERE cliente_id = " . intval($user['id']) . " AND estado='completada'")->fetchColumn();
      
      $stmt = $pdo->prepare("SELECT c.fecha_cita, c.hora_inicio, c.estado, s.nombre as servicio, suc.nombre as sucursal 
                             FROM citas c 
                             LEFT JOIN servicios s ON c.servicio_id = s.id 
                             LEFT JOIN sucursales suc ON c.sucursal_id = suc.id
                             WHERE c.cliente_id = ? 
                             ORDER BY c.fecha_cita DESC, c.hora_inicio DESC LIMIT 5");
      $stmt->execute([$user['id']]);
      $s_stats['ultimas_citas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
   } catch (Throwable $e) { }
}
?>

<div class="max-w-7xl mx-auto">
  <div class="bg-white rounded-2xl shadow p-6 border">
    <div class="text-sm text-gray-500">Mi Portal</div>
    <div class="mt-1 text-2xl font-extrabold text-gray-900">
      Bienvenido, <?= htmlspecialchars($user['nombre'] ?? '') ?>
    </div>
    <div class="mt-2 text-gray-700">
      Desde aquí puedes gestionar tus citas y tu perfil.
    </div>

    <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 shadow-sm transition hover:shadow-md">
        <div class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-2">Próximas Citas</div>
        <div class="text-3xl font-black text-teal-600"><?= (int)($s_stats['citas_proximas'] ?? 0) ?></div>
      </div>
      <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 shadow-sm transition hover:shadow-md">
        <div class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-2">Citas Pasadas</div>
        <div class="text-3xl font-black text-teal-600"><?= (int)($s_stats['citas_pasadas'] ?? 0) ?></div>
      </div>
      <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 shadow-sm transition hover:shadow-md">
        <div class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-2">Citas Completadas</div>
        <div class="text-3xl font-black text-teal-600"><?= (int)($s_stats['citas_completadas'] ?? 0) ?></div>
      </div>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
      <a href="<?= view_url('vistas/cliente/citas.php', $id_e) ?>" class="block rounded-xl border p-4 hover:bg-teal-100 bg-teal-50 border-teal-100 transition">
        <div class="font-semibold text-teal-900">Mis Citas</div>
        <div class="text-sm text-teal-700">Ver y programar nuevas citas.</div>
      </a>
      <a href="<?= view_url('vistas/cliente/ajustes.php', $id_e) ?>" class="block rounded-xl border p-4 hover:bg-teal-100 bg-teal-50 border-teal-100 transition">
        <div class="font-semibold text-teal-900">Mi Perfil</div>
        <div class="text-sm text-teal-700">Actualizar mis datos de contacto.</div>
      </a>
    </div>

    <div class="mt-6 rounded-2xl border bg-white p-4 shadow-sm">
      <div class="flex items-center justify-between gap-3">
        <div class="font-semibold text-gray-900">Mi historial reciente</div>
      </div>
      <div class="mt-3 overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-700">
            <tr>
              <th class="text-left px-3 py-2">Fecha/Hora</th>
              <th class="text-left px-3 py-2">Servicio</th>
              <th class="text-left px-3 py-2">Sede</th>
              <th class="text-left px-3 py-2">Estado</th>
            </tr>
          </thead>
          <tbody class="divide-y relative">
            <?php foreach (($s_stats['ultimas_citas'] ?? []) as $c): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-3 py-2 font-mono text-xs text-gray-600"><?= htmlspecialchars($c['fecha_cita']) ?> <?= substr($c['hora_inicio'], 0, 5) ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars($c['servicio'] ?? 'N/A') ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars($c['sucursal'] ?? 'N/A') ?></td>
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
                <td class="px-3 py-3 text-gray-500 text-center" colspan="4">No haz agendado citas aún.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>