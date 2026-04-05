<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'dashboard';
include __DIR__ . '/../../includes/topbar.php';

$s_stats = [];
try {
  $eid = (int) $id_e;
  $sid = (int) ($user['sucursal_id'] ?? 0);
  $scope = $sid > 0 ? " AND c.sucursal_id = {$sid}" : "";

  $s_stats['citas_hoy'] = (int) $pdo->query("SELECT COUNT(*) FROM citas c WHERE c.empresa_id = {$eid}{$scope} AND DATE(c.inicio) = CURDATE()")->fetchColumn();
  $s_stats['citas_pendientes'] = (int) $pdo->query("SELECT COUNT(*) FROM citas c WHERE c.empresa_id = {$eid}{$scope} AND c.estado='pendiente'")->fetchColumn();
  $s_stats['citas_total'] = (int) $pdo->query("SELECT COUNT(*) FROM citas c WHERE c.empresa_id = {$eid}{$scope}")->fetchColumn();
  $s_stats['citas_completadas'] = (int) $pdo->query("SELECT COUNT(*) FROM citas c WHERE c.empresa_id = {$eid}{$scope} AND c.estado='completada'")->fetchColumn();
  $s_stats['citas_mes'] = (int) $pdo->query("SELECT COUNT(*) FROM citas c WHERE c.empresa_id = {$eid}{$scope} AND YEAR(c.inicio)=YEAR(CURDATE()) AND MONTH(c.inicio)=MONTH(CURDATE())")->fetchColumn();
  $s_stats['citas_mes_prev'] = (int) $pdo->query("SELECT COUNT(*) FROM citas c WHERE c.empresa_id = {$eid}{$scope} AND YEAR(c.inicio)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(c.inicio)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))")->fetchColumn();
  $s_stats['ingresos_mes'] = (float) $pdo->query("SELECT COALESCE(SUM(s.precio_base),0) FROM citas c JOIN servicios s ON s.id=c.servicio_id WHERE c.empresa_id = {$eid}{$scope} AND c.estado IN ('confirmada','completada') AND YEAR(c.inicio)=YEAR(CURDATE()) AND MONTH(c.inicio)=MONTH(CURDATE())")->fetchColumn();
  $s_stats['ingresos_mes_prev'] = (float) $pdo->query("SELECT COALESCE(SUM(s.precio_base),0) FROM citas c JOIN servicios s ON s.id=c.servicio_id WHERE c.empresa_id = {$eid}{$scope} AND c.estado IN ('confirmada','completada') AND YEAR(c.inicio)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(c.inicio)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))")->fetchColumn();

  $sql = "SELECT c.inicio, c.estado, s.nombre as servicio, c.cliente_nombre as cliente
          FROM citas c
          LEFT JOIN servicios s ON c.servicio_id = s.id
          WHERE c.empresa_id = ? " . ($sid > 0 ? "AND c.sucursal_id = ?" : "") . "
          ORDER BY c.inicio DESC LIMIT 8";
  $stmt = $pdo->prepare($sql);
  $params = [$eid];
  if ($sid > 0) {
    $params[] = $sid;
  }
  $stmt->execute($params);
  $s_stats['ultimas_citas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $s_stats = [];
}
?>

<div class="max-w-7xl mx-auto">
  <div class="bg-white rounded-2xl shadow p-6 border">
    <div class="text-sm text-gray-500">Dashboard</div>
    <div class="mt-1 text-2xl font-extrabold text-gray-900">Panel Gerente - <?= htmlspecialchars((string) $id_e) ?></div>
    <div class="mt-2 text-gray-700">
      Usuario: <span class="font-semibold"><?= htmlspecialchars((string) ($user['nombre'] ?? '')) ?></span>
      (<?= htmlspecialchars((string) $role) ?>)
    </div>

    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Citas hoy</div>
        <div class="mt-1 text-3xl font-black text-gray-900"><?= (int) ($s_stats['citas_hoy'] ?? 0) ?></div>
      </div>
      <div class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Pendientes</div>
        <div class="mt-1 text-3xl font-black text-gray-900"><?= (int) ($s_stats['citas_pendientes'] ?? 0) ?></div>
      </div>
      <div class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Completadas</div>
        <div class="mt-1 text-3xl font-black text-gray-900"><?= (int) ($s_stats['citas_completadas'] ?? 0) ?></div>
      </div>
      <div class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Total histórico</div>
        <div class="mt-1 text-3xl font-black text-gray-900"><?= (int) ($s_stats['citas_total'] ?? 0) ?></div>
      </div>
    </div>

    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Citas del mes</div>
        <div class="mt-1 text-2xl font-extrabold text-gray-900"><?= (int) ($s_stats['citas_mes'] ?? 0) ?></div>
        <div class="text-xs text-gray-500">Mes anterior: <?= (int) ($s_stats['citas_mes_prev'] ?? 0) ?></div>
      </div>
      <div class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Ingresos estimados mes</div>
        <div class="mt-1 text-2xl font-extrabold text-gray-900">$<?= number_format((float) ($s_stats['ingresos_mes'] ?? 0), 2) ?></div>
        <div class="text-xs text-gray-500">Mes anterior: $<?= number_format((float) ($s_stats['ingresos_mes_prev'] ?? 0), 2) ?></div>
      </div>
    </div>

    <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="text-sm font-semibold text-gray-800 mb-2">Citas: mes actual vs anterior</div>
        <div class="relative h-56">
          <canvas id="chartGerenteCitas"></canvas>
        </div>
      </div>
      <div class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="text-sm font-semibold text-gray-800 mb-2">Ingresos: mes actual vs anterior</div>
        <div class="relative h-56">
          <canvas id="chartGerenteIngresos"></canvas>
        </div>
      </div>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
      <a href="<?= view_url('vistas/sucursal/admin-citas.php', $id_e) ?>" class="block rounded-xl border p-4 border-teal-100 bg-teal-50 hover:bg-teal-100 transition">
        <div class="font-semibold text-teal-900">Citas</div>
        <div class="text-sm text-teal-700">Ver y gestionar agenda de la sucursal.</div>
      </a>
      <a href="<?= view_url('vistas/sucursal/sucursales.php', $id_e) ?>" class="block rounded-xl border p-4 border-teal-100 bg-teal-50 hover:bg-teal-100 transition">
        <div class="font-semibold text-teal-900">Mi Sucursal</div>
        <div class="text-sm text-teal-700">Ver información de la sucursal.</div>
      </a>
      <a href="<?= view_url('vistas/sucursal/ajustes.php', $id_e) ?>" class="block rounded-xl border p-4 border-teal-100 bg-teal-50 hover:bg-teal-100 transition">
        <div class="font-semibold text-teal-900">Ajustes</div>
        <div class="text-sm text-teal-700">Configuración de la cuenta.</div>
      </a>
    </div>

    <div class="mt-6 rounded-2xl border bg-white p-4 shadow-sm">
      <div class="font-semibold text-gray-900">Últimas Citas registradas</div>
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
          <tbody class="divide-y">
            <?php foreach (($s_stats['ultimas_citas'] ?? []) as $c): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-3 py-2 font-mono text-xs text-gray-600"><?= htmlspecialchars((string) ($c['inicio'] ?? '')) ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars((string) ($c['cliente'] ?? 'N/A')) ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars((string) ($c['servicio'] ?? 'N/A')) ?></td>
                <td class="px-3 py-2">
                  <?php if (($c['estado'] ?? '') === 'completada'): ?>
                    <span class="inline-flex px-2 py-0.5 rounded text-xs bg-green-100 text-green-800">Completada</span>
                  <?php elseif (($c['estado'] ?? '') === 'cancelada'): ?>
                    <span class="inline-flex px-2 py-0.5 rounded text-xs bg-red-100 text-red-800">Cancelada</span>
                  <?php else: ?>
                    <span class="inline-flex px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-800"><?= htmlspecialchars(ucfirst((string) ($c['estado'] ?? 'pendiente'))) ?></span>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  (function () {
    const BRAND = <?= json_encode($color_p ?: '#0d9488') ?>;
    const labels = ['Mes anterior', 'Mes actual'];
    const citasData = [<?= (int) ($s_stats['citas_mes_prev'] ?? 0) ?>, <?= (int) ($s_stats['citas_mes'] ?? 0) ?>];
    const ingresosData = [<?= (float) ($s_stats['ingresos_mes_prev'] ?? 0) ?>, <?= (float) ($s_stats['ingresos_mes'] ?? 0) ?>];
    function hexToRgba(hex, alpha) {
      const h = String(hex || '').replace('#', '');
      const full = h.length === 3 ? h.split('').map(ch => ch + ch).join('') : h.padEnd(6, '0').slice(0, 6);
      const n = parseInt(full, 16);
      const r = (n >> 16) & 255;
      const g = (n >> 8) & 255;
      const b = n & 255;
      return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    const commonOpts = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#64748b' } },
        y: { grid: { color: 'rgba(148,163,184,.2)' }, ticks: { color: '#64748b' }, beginAtZero: true }
      }
    };

    const c1 = document.getElementById('chartGerenteCitas');
    const c2 = document.getElementById('chartGerenteIngresos');
    if (c1) {
      new Chart(c1, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            data: citasData,
            borderRadius: 10,
            backgroundColor: ['rgba(15,23,42,0.14)', hexToRgba(BRAND, 0.72)],
            borderColor: ['rgba(15,23,42,0.25)', hexToRgba(BRAND, 1)],
            borderWidth: 1.2
          }]
        },
        options: commonOpts
      });
    }
    if (c2) {
      new Chart(c2, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            data: ingresosData,
            borderColor: BRAND,
            pointBackgroundColor: BRAND,
            pointRadius: 4,
            pointHoverRadius: 5,
            borderWidth: 2.5,
            tension: 0.35,
            fill: true,
            backgroundColor: hexToRgba(BRAND, 0.14)
          }]
        },
        options: commonOpts
      });
    }
  })();
</script>
