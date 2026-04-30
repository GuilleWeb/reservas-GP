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
  if ($sid <= 0) {
    $stmtS = $pdo->prepare('SELECT id FROM sucursales WHERE empresa_id = ? AND activo = 1 ORDER BY id ASC LIMIT 1');
    $stmtS->execute([$eid]);
    $sid = (int) ($stmtS->fetchColumn() ?: 0);
  }
  if ($eid <= 0 || $sid <= 0) {
    throw new Exception('No hay contexto de sucursal.');
  }
  $s_stats['empresa_id'] = $eid;
  $s_stats['sucursal_id'] = $sid;

  $countStmt = function (string $sql, array $params): int {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) ($stmt->fetchColumn() ?: 0);
  };
  $sumStmt = function (string $sql, array $params): float {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (float) ($stmt->fetchColumn() ?: 0);
  };

  // Sección 1: Hoy vs ayer (solo esta sucursal)
  $s_stats['citas_hoy'] = $countStmt("SELECT COUNT(*) FROM citas c WHERE c.empresa_id = ? AND c.sucursal_id = ? AND c.estado != 'cancelada' AND DATE(c.inicio) = CURDATE()", [$eid, $sid]);
  $s_stats['citas_ayer'] = $countStmt("SELECT COUNT(*) FROM citas c WHERE c.empresa_id = ? AND c.sucursal_id = ? AND c.estado != 'cancelada' AND DATE(c.inicio) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)", [$eid, $sid]);

  $s_stats['pendientes_hoy'] = $countStmt("SELECT COUNT(*) FROM citas c WHERE c.empresa_id = ? AND c.sucursal_id = ? AND c.estado = 'pendiente' AND DATE(c.inicio) = CURDATE()", [$eid, $sid]);
  $s_stats['pendientes_ayer'] = $countStmt("SELECT COUNT(*) FROM citas c WHERE c.empresa_id = ? AND c.sucursal_id = ? AND c.estado = 'pendiente' AND DATE(c.inicio) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)", [$eid, $sid]);

  $s_stats['completadas_hoy'] = $countStmt("SELECT COUNT(*) FROM citas c WHERE c.empresa_id = ? AND c.sucursal_id = ? AND c.estado = 'completada' AND DATE(c.inicio) = CURDATE()", [$eid, $sid]);
  $s_stats['completadas_ayer'] = $countStmt("SELECT COUNT(*) FROM citas c WHERE c.empresa_id = ? AND c.sucursal_id = ? AND c.estado = 'completada' AND DATE(c.inicio) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)", [$eid, $sid]);

  $s_stats['canceladas_hoy'] = $countStmt("SELECT COUNT(*) FROM citas c WHERE c.empresa_id = ? AND c.sucursal_id = ? AND c.estado = 'cancelada' AND DATE(c.inicio) = CURDATE()", [$eid, $sid]);
  $s_stats['canceladas_ayer'] = $countStmt("SELECT COUNT(*) FROM citas c WHERE c.empresa_id = ? AND c.sucursal_id = ? AND c.estado = 'cancelada' AND DATE(c.inicio) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)", [$eid, $sid]);

  // Empleado más solicitado hoy: hasta la hora actual; desempate por todo el día
  $stmtTopEmp = $pdo->prepare("SELECT u.id, u.nombre, u.foto_path,
                                     SUM(CASE WHEN c.inicio <= NOW() THEN 1 ELSE 0 END) AS cnt_to_now,
                                     COUNT(*) AS cnt_full
                              FROM citas c
                              LEFT JOIN usuarios u ON u.id = c.empleado_usuario_id
                              WHERE c.empresa_id = ? AND c.sucursal_id = ?
                                AND c.estado != 'cancelada'
                                AND c.empleado_usuario_id IS NOT NULL
                                AND DATE(c.inicio) = CURDATE()
                              GROUP BY u.id, u.nombre, u.foto_path
                              ORDER BY cnt_to_now DESC, cnt_full DESC
                              LIMIT 1");
  $stmtTopEmp->execute([$eid, $sid]);
  $s_stats['top_empleado_hoy'] = $stmtTopEmp->fetch(PDO::FETCH_ASSOC) ?: null;

  // Sección 2: Totales globales
  $s_stats['total_citas'] = $countStmt("SELECT COUNT(*) FROM citas c WHERE c.empresa_id = ? AND c.sucursal_id = ? AND c.estado != 'cancelada'", [$eid, $sid]);
  $s_stats['total_canceladas'] = $countStmt("SELECT COUNT(*) FROM citas c WHERE c.empresa_id = ? AND c.sucursal_id = ? AND c.estado = 'cancelada'", [$eid, $sid]);
  $s_stats['ingresos_mes'] = $sumStmt("SELECT COALESCE(SUM(s.precio_base),0)
                                      FROM citas c
                                      JOIN servicios s ON s.id = c.servicio_id
                                      WHERE c.empresa_id = ? AND c.sucursal_id = ?
                                        AND c.estado = 'completada'
                                        AND YEAR(c.inicio)=YEAR(CURDATE()) AND MONTH(c.inicio)=MONTH(CURDATE())", [$eid, $sid]);

  $stmtNewCli = $pdo->prepare("SELECT COUNT(*)
                              FROM (
                                  SELECT c.cliente_id, MIN(DATE(c.inicio)) AS first_date
                                  FROM citas c
                                  WHERE c.empresa_id = ? AND c.sucursal_id = ?
                                    AND c.estado != 'cancelada'
                                    AND c.cliente_id IS NOT NULL
                                  GROUP BY c.cliente_id
                              ) t
                              WHERE YEAR(t.first_date)=YEAR(CURDATE()) AND MONTH(t.first_date)=MONTH(CURDATE())");
  $stmtNewCli->execute([$eid, $sid]);
  $s_stats['clientes_nuevos_mes'] = (int) ($stmtNewCli->fetchColumn() ?: 0);

  $stmtNewCliPrev = $pdo->prepare("SELECT COUNT(*)
                                  FROM (
                                      SELECT c.cliente_id, MIN(DATE(c.inicio)) AS first_date
                                      FROM citas c
                                      WHERE c.empresa_id = ? AND c.sucursal_id = ?
                                        AND c.estado != 'cancelada'
                                        AND c.cliente_id IS NOT NULL
                                      GROUP BY c.cliente_id
                                  ) t
                                  WHERE YEAR(t.first_date)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                                    AND MONTH(t.first_date)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
  $stmtNewCliPrev->execute([$eid, $sid]);
  $s_stats['clientes_nuevos_mes_prev'] = (int) ($stmtNewCliPrev->fetchColumn() ?: 0);

  // Sección 3: gráficos (últimos 3 meses incluyendo el actual)
  $start = new DateTime('first day of -2 month 00:00:00');
  $end = new DateTime('first day of next month 00:00:00');
  $startStr = $start->format('Y-m-d H:i:s');
  $endStr = $end->format('Y-m-d H:i:s');

  $stmtMonths = $pdo->prepare("SELECT DATE_FORMAT(c.inicio, '%Y-%m') ym, COUNT(*) cnt
                              FROM citas c
                              WHERE c.empresa_id = ? AND c.sucursal_id = ?
                                AND c.estado != 'cancelada'
                                AND c.inicio >= ? AND c.inicio < ?
                              GROUP BY ym");
  $stmtMonths->execute([$eid, $sid, $startStr, $endStr]);
  $rowsMonths = $stmtMonths->fetchAll(PDO::FETCH_ASSOC);
  $mapMonths = [];
  foreach ($rowsMonths as $r) {
    $mapMonths[(string) $r['ym']] = (int) ($r['cnt'] ?? 0);
  }
  $labels = [];
  $values = [];
  $cursor = clone $start;
  for ($i = 0; $i < 3; $i++) {
    $ym = $cursor->format('Y-m');
    $labels[] = $cursor->format('M Y');
    $values[] = (int) ($mapMonths[$ym] ?? 0);
    $cursor->modify('+1 month');
  }
  $s_stats['chart_citas_3m'] = ['labels' => $labels, 'values' => $values];

  $stmtTopEmps = $pdo->prepare("SELECT u.id, u.nombre, u.foto_path,
                                      COUNT(*) AS recibidas,
                                      SUM(CASE WHEN c.estado='completada' THEN 1 ELSE 0 END) AS completadas
                               FROM citas c
                               LEFT JOIN usuarios u ON u.id = c.empleado_usuario_id
                               WHERE c.empresa_id = ? AND c.sucursal_id = ?
                                 AND c.empleado_usuario_id IS NOT NULL
                                 AND c.estado != 'cancelada'
                                 AND c.inicio >= ? AND c.inicio < ?
                               GROUP BY u.id, u.nombre, u.foto_path
                               ORDER BY completadas DESC, recibidas DESC
                               LIMIT 3");
  $stmtTopEmps->execute([$eid, $sid, $startStr, $endStr]);
  $s_stats['chart_top_empleados'] = $stmtTopEmps->fetchAll(PDO::FETCH_ASSOC);

  $stmtTopSrvs = $pdo->prepare("SELECT s.id, s.nombre,
                                      COUNT(*) AS completadas
                               FROM citas c
                               JOIN servicios s ON s.id = c.servicio_id
                               WHERE c.empresa_id = ? AND c.sucursal_id = ?
                                 AND c.estado = 'completada'
                                 AND c.inicio >= ? AND c.inicio < ?
                               GROUP BY s.id, s.nombre
                               ORDER BY completadas DESC
                               LIMIT 3");
  $stmtTopSrvs->execute([$eid, $sid, $startStr, $endStr]);
  $s_stats['chart_top_servicios'] = $stmtTopSrvs->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $s_stats = [];
}
?>

<div class="max-w-7xl mx-auto">
  <div class="bg-white rounded-2xl shadow p-6 border">
    <div class="text-sm text-gray-500">Dashboard</div>
    <div class="mt-1 text-2xl font-extrabold text-teal-700 dark:text-teal-400">Panel Gerente - <?= htmlspecialchars((string) $id_e) ?></div>
    <div class="mt-2 text-gray-700">
      Usuario: <span class="font-semibold"><?= htmlspecialchars((string) ($user['nombre'] ?? '')) ?></span>
      (<?= htmlspecialchars((string) $role) ?>)
    </div>

    <?php
      $renderAyer = function (int $hoy, int $ayer): string {
        $d = $hoy - $ayer;
        $isUp = $d >= 0;
        $cls = $d === 0 ? 'text-slate-500' : ($isUp ? 'text-emerald-600' : 'text-rose-600');
        $arrow = $d === 0 ? '' : ($isUp
          ? '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5l6 6H6l6-6Z" fill="currentColor"/></svg>'
          : '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 19l-6-6h12l-6 6Z" fill="currentColor"/></svg>'
        );
        $deltaTxt = $d === 0 ? '' : (' ' . ($d > 0 ? '+' : '') . $d);
        return '<div class="mt-1 flex items-center gap-1 text-xs ' . $cls . '">' . $arrow . '<span>Ayer: ' . (int) $ayer . '</span><span class="opacity-80">' . htmlspecialchars($deltaTxt) . '</span></div>';
      };
      $topEmp = $s_stats['top_empleado_hoy'] ?? null;
      $topEmpName = $topEmp ? (string) ($topEmp['nombre'] ?? '') : '';
      $topEmpPhoto = $topEmp ? (string) ($topEmp['foto_path'] ?? '') : '';
      $topEmpCntToNow = $topEmp ? (int) ($topEmp['cnt_to_now'] ?? 0) : 0;
      $topEmpCntFull = $topEmp ? (int) ($topEmp['cnt_full'] ?? 0) : 0;
      $money = function (float $n): string {
        return (string) ($GLOBALS['currency_symbol'] ?? '$') . number_format($n, 2);
      };
    ?>

    <div class="mt-6">
      <div class="text-sm font-semibold text-teal-700">Hoy</div>
      <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
          <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Citas hoy</div>
          <div class="mt-1 text-3xl font-black text-teal-700 dark:text-teal-400"><?= (int) ($s_stats['citas_hoy'] ?? 0) ?></div>
          <?= $renderAyer((int) ($s_stats['citas_hoy'] ?? 0), (int) ($s_stats['citas_ayer'] ?? 0)) ?>
        </div>
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
          <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Pendientes hoy</div>
          <div class="mt-1 text-3xl font-black text-teal-700 dark:text-teal-400"><?= (int) ($s_stats['pendientes_hoy'] ?? 0) ?></div>
          <?= $renderAyer((int) ($s_stats['pendientes_hoy'] ?? 0), (int) ($s_stats['pendientes_ayer'] ?? 0)) ?>
        </div>
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
          <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Completadas hoy</div>
          <div class="mt-1 text-3xl font-black text-teal-700 dark:text-teal-400"><?= (int) ($s_stats['completadas_hoy'] ?? 0) ?></div>
          <?= $renderAyer((int) ($s_stats['completadas_hoy'] ?? 0), (int) ($s_stats['completadas_ayer'] ?? 0)) ?>
        </div>
        <!-- <div class="rounded-2xl border bg-white p-4 shadow-sm">
          <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Canceladas hoy</div>
          <div class="mt-1 text-3xl font-black text-teal-700 dark:text-teal-400"><?= (int) ($s_stats['canceladas_hoy'] ?? 0) ?></div>
          <?= $renderAyer((int) ($s_stats['canceladas_hoy'] ?? 0), (int) ($s_stats['canceladas_ayer'] ?? 0)) ?>
        </div> -->
        <div class="rounded-2xl border bg-white p-4 shadow-sm relative">
          <div class="absolute top-3 right-3 text-amber-400">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27Z"/>
            </svg>
          </div>
          <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Empleado destacado de hoy</div>
          <div class="mt-2 flex items-center gap-3">
            <div class="h-12 w-12 rounded-full bg-gray-100 border overflow-hidden">
              <?php if ($topEmpPhoto !== ''): ?>
                <img src="<?= htmlspecialchars(app_url(ltrim($topEmpPhoto, '/'))) ?>" class="h-full w-full object-cover" alt="empleado">
              <?php else: ?>
                <div class="h-full w-full grid place-items-center text-gray-400 text-sm font-bold">?</div>
              <?php endif; ?>
            </div>
            <div class="min-w-0">
              <div class="font-semibold text-gray-900 truncate"><?= htmlspecialchars($topEmpName !== '' ? $topEmpName : 'Sin asignación') ?></div>
              <div class="text-xs text-gray-500">Hasta ahora: <?= (int) $topEmpCntToNow ?> · Total hoy: <?= (int) $topEmpCntFull ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-2xl border bg-white p-4 shadow-sm relative">
          <div class="absolute top-3 right-3 text-amber-400">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27Z"/>
            </svg>
          </div>
          <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Empleado destacado de hoy</div>
          <div class="mt-2 flex items-center gap-3">
            <div class="h-12 w-12 rounded-full bg-gray-100 border overflow-hidden">
              <?php if ($topEmpPhoto !== ''): ?>
                <img src="<?= htmlspecialchars(app_url(ltrim($topEmpPhoto, '/'))) ?>" class="h-full w-full object-cover" alt="empleado">
              <?php else: ?>
                <div class="h-full w-full grid place-items-center text-gray-400 text-sm font-bold">?</div>
              <?php endif; ?>
            </div>
            <div class="min-w-0">
              <div class="font-semibold text-gray-900 truncate"><?= htmlspecialchars($topEmpName !== '' ? $topEmpName : 'Sin asignación') ?></div>
              <div class="text-xs text-gray-500">Hasta ahora: <?= (int) $topEmpCntToNow ?> · Total hoy: <?= (int) $topEmpCntFull ?></div>
            </div>
          </div>
        </div>
      </div> -->
    </div>

    <div class="mt-8">
      <div class="text-sm font-semibold text-gray-800">Totales</div>
      <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
          <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Total citas</div>
          <div class="mt-1 text-3xl font-black text-teal-700 dark:text-teal-400"><?= (int) ($s_stats['total_citas'] ?? 0) ?></div>
          <div class="text-xs text-gray-500 mt-1">Excepto canceladas</div>
        </div>
        <div class="rounded-2xl border bg-red-50 p-4 shadow-sm border-red-100">
          <div class="text-xs text-red-700 uppercase tracking-wide font-semibold">Cancelaciones</div>
          <div class="mt-1 text-3xl font-black text-red-900"><?= (int) ($s_stats['total_canceladas'] ?? 0) ?></div>
          <div class="text-xs text-red-700 mt-1">Total histórico</div>
        </div>
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
          <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Ingresos estimados (mes)</div>
          <div class="mt-1 text-3xl font-black text-teal-700 dark:text-teal-400"><?= htmlspecialchars($money((float) ($s_stats['ingresos_mes'] ?? 0))) ?></div>
          <div class="text-xs text-gray-500 mt-1">Solo completadas</div>
        </div>
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
          <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Clientes nuevos (mes)</div>
          <div class="mt-1 text-3xl font-black text-teal-700 dark:text-teal-400"><?= (int) ($s_stats['clientes_nuevos_mes'] ?? 0) ?></div>
          <div class="text-xs text-gray-500 mt-1">Mes anterior: <?= (int) ($s_stats['clientes_nuevos_mes_prev'] ?? 0) ?></div>
        </div>
      </div>
    </div>

    <div class="mt-8">
      <div class="text-sm font-semibold text-gray-800">Gráficos (últimos 3 meses)</div>
      <div class="mt-3 grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
          <div class="text-sm font-semibold text-gray-800 mb-2">Citas por mes</div>
          <div class="relative h-56">
            <canvas id="chartSucCitas3m"></canvas>
          </div>
        </div>
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
          <div class="text-sm font-semibold text-gray-800 mb-2">Top 3 empleados</div>
          <div class="relative h-56">
            <canvas id="chartSucTopEmps"></canvas>
          </div>
        </div>
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
          <div class="text-sm font-semibold text-gray-800 mb-2">Top 3 servicios</div>
          <div class="relative h-56">
            <canvas id="chartSucTopServicios"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
      <a href="<?= view_url('vistas/sucursal/admin-citas.php', $id_e) ?>" class="block rounded-xl border p-4 border-teal-100 bg-teal-50 hover:bg-teal-100 transition">
        <div class="font-semibold text-teal-900">Citas</div>
        <div class="text-sm text-teal-700">Ver y gestionar agenda de la sucursal.</div>
      </a>
      <a href="<?= view_url('vistas/sucursal/mi-sucursal.php', $id_e) ?>" class="block rounded-xl border p-4 border-teal-100 bg-teal-50 hover:bg-teal-100 transition">
        <div class="font-semibold text-teal-900">Mi Sucursal</div>
        <div class="text-sm text-teal-700">Ver información de la sucursal.</div>
      </a>
      <a href="<?= view_url('vistas/sucursal/ajustes.php', $id_e) ?>" class="block rounded-xl border p-4 border-teal-100 bg-teal-50 hover:bg-teal-100 transition">
        <div class="font-semibold text-teal-900">Ajustes</div>
        <div class="text-sm text-teal-700">Configuración de la cuenta.</div>
      </a>
    </div>

  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  (function () {
    const BRAND = <?= json_encode($color_p ?: '#0d9488') ?>;
    const citas3m = <?= json_encode($s_stats['chart_citas_3m'] ?? ['labels' => [], 'values' => []]) ?>;
    const topEmps = <?= json_encode($s_stats['chart_top_empleados'] ?? []) ?>;
    const topServicios = <?= json_encode($s_stats['chart_top_servicios'] ?? []) ?>;
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

    const c1 = document.getElementById('chartSucCitas3m');
    const c2 = document.getElementById('chartSucTopEmps');
    const c3 = document.getElementById('chartSucTopServicios');

    if (c1) {
      new Chart(c1, {
        type: 'line',
        data: {
          labels: citas3m.labels || [],
          datasets: [{
            data: citas3m.values || [],
            backgroundColor: hexToRgba(BRAND, 0.12),
            borderColor: hexToRgba(BRAND, 1),
            borderWidth: 2,
            pointRadius: 3,
            tension: 0.35,
            fill: true
          }]
        },
        options: commonOpts
      });
    }

    if (c2) {
      const labels = (topEmps || []).map(r => String(r.nombre || 'Empleado'));
      const recibidas = (topEmps || []).map(r => Number(r.recibidas || 0));
      const completadas = (topEmps || []).map(r => Number(r.completadas || 0));
      new Chart(c2, {
        type: 'bar',
        data: {
          labels,
          datasets: [
            {
              label: 'Recibidas',
              data: recibidas,
              borderRadius: 8,
              backgroundColor: 'rgba(15,23,42,0.14)',
              borderColor: 'rgba(15,23,42,0.25)',
              borderWidth: 1.2
            },
            {
              label: 'Completadas',
              data: completadas,
              borderRadius: 8,
              backgroundColor: hexToRgba(BRAND, 0.72),
              borderColor: hexToRgba(BRAND, 1),
              borderWidth: 1.2
            }
          ]
        },
        options: {
          ...commonOpts,
          plugins: {
            ...commonOpts.plugins,
            legend: { display: true, labels: { color: '#64748b', boxWidth: 12, boxHeight: 12 } }
          }
        }
      });
    }

    if (c3) {
      const labels = (topServicios || []).map(r => String(r.nombre || 'Servicio'));
      const data = (topServicios || []).map(r => Number(r.completadas || 0));
      new Chart(c3, {
        type: 'doughnut',
        data: {
          labels,
          datasets: [{
            data,
            backgroundColor: [hexToRgba(BRAND, 0.88), hexToRgba(BRAND, 0.55), hexToRgba(BRAND, 0.28)],
            borderColor: ['rgba(255,255,255,0.9)','rgba(255,255,255,0.9)','rgba(255,255,255,0.9)'],
            borderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: true, position: 'bottom', labels: { color: '#64748b', boxWidth: 12, boxHeight: 12 } },
            tooltip: { mode: 'index', intersect: false }
          }
        }
      });
    }
  })();
</script>
