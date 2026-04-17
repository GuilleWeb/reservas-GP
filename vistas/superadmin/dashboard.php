<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$module = 'dashboard';
include __DIR__ . '/../../includes/topbar.php';
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
  $stats['planes_activos'] = (int) $pdo->query("SELECT COUNT(*) FROM planes WHERE activo=1")->fetchColumn();
  $stats['suscripciones_activas'] = (int) $pdo->query("SELECT COUNT(*) FROM suscripciones WHERE estado='activa'")->fetchColumn();
  $stats['suscripciones_por_vencer'] = (int) $pdo->query("SELECT COUNT(*) FROM suscripciones WHERE estado='activa' AND fecha_fin IS NOT NULL AND fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
  $stats['suscripciones_vencidas'] = (int) $pdo->query("SELECT COUNT(*) FROM suscripciones WHERE estado='vencida'")->fetchColumn();

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

  $stats['email'] = email_delivery_stats(30);
  ensure_suscripciones_historial_table();
  try {
    $mStart = date('Y-m-01');
    $mEnd = date('Y-m-t');
    $pStart = date('Y-m-01', strtotime('-1 month'));
    $pEnd = date('Y-m-t', strtotime('-1 month'));
    $qCurrent = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM (
        SELECT s.ultimo_pago_monto AS monto FROM suscripciones s
        WHERE s.ultimo_pago_fecha BETWEEN ? AND ?
        UNION ALL
        SELECT h.ultimo_pago_monto AS monto FROM suscripciones_historial h
        WHERE h.ultimo_pago_fecha BETWEEN ? AND ?
    ) z");
    $qCurrent->execute([$mStart, $mEnd, $mStart, $mEnd]);
    $stats['sus_ingreso_mes'] = (float) ($qCurrent->fetchColumn() ?: 0);
    $qPrev = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM (
        SELECT s.ultimo_pago_monto AS monto FROM suscripciones s
        WHERE s.ultimo_pago_fecha BETWEEN ? AND ?
        UNION ALL
        SELECT h.ultimo_pago_monto AS monto FROM suscripciones_historial h
        WHERE h.ultimo_pago_fecha BETWEEN ? AND ?
    ) z");
    $qPrev->execute([$pStart, $pEnd, $pStart, $pEnd]);
    $stats['sus_ingreso_mes_prev'] = (float) ($qPrev->fetchColumn() ?: 0);

    $trend = [];
    for ($i = 5; $i >= 0; $i--) {
      $start = date('Y-m-01', strtotime("-{$i} month"));
      $end = date('Y-m-t', strtotime("-{$i} month"));
      $qT = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM (
          SELECT s.ultimo_pago_monto AS monto FROM suscripciones s
          WHERE s.ultimo_pago_fecha BETWEEN ? AND ?
          UNION ALL
          SELECT h.ultimo_pago_monto AS monto FROM suscripciones_historial h
          WHERE h.ultimo_pago_fecha BETWEEN ? AND ?
      ) z");
      $qT->execute([$start, $end, $start, $end]);
      $trend[] = [
        'label' => date('M y', strtotime($start)),
        'monto' => (float) ($qT->fetchColumn() ?: 0),
      ];
    }
    $stats['sus_trend'] = $trend;
  } catch (Throwable $e) {
    $stats['sus_ingreso_mes'] = 0;
    $stats['sus_ingreso_mes_prev'] = 0;
    $stats['sus_trend'] = [];
  }
  $basePath = project_path('');
  $diskTotal = @disk_total_space($basePath);
  $diskFree = @disk_free_space($basePath);
  $stats['disk_total_gb'] = $diskTotal ? round($diskTotal / 1024 / 1024 / 1024, 2) : 0;
  $stats['disk_free_gb'] = $diskFree ? round($diskFree / 1024 / 1024 / 1024, 2) : 0;
  $stats['disk_used_gb'] = max(0, round($stats['disk_total_gb'] - $stats['disk_free_gb'], 2));
  $stats['php_version'] = PHP_VERSION;
  $stats['memory_limit'] = (string) ini_get('memory_limit');
  $stats['upload_max'] = (string) ini_get('upload_max_filesize');
  $stats['post_max'] = (string) ini_get('post_max_size');
  $stats['max_execution_time'] = (string) ini_get('max_execution_time');
  $stats['memory_usage_mb'] = round(memory_get_usage(true) / 1024 / 1024, 2);
  $stats['memory_peak_mb'] = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
  try {
    $stmtDb = $pdo->query("SELECT ROUND(SUM(data_length+index_length)/1024/1024,2) AS mb
                           FROM information_schema.tables
                           WHERE table_schema = DATABASE()");
    $stats['db_size_mb'] = (float) ($stmtDb->fetchColumn() ?: 0);
  } catch (Throwable $e) {
    $stats['db_size_mb'] = 0;
  }
}
?>

<div class="max-w-7xl mx-auto">
  <div class="bg-white rounded-2xl shadow p-6">
    <div class="text-sm text-gray-500">Dashboard</div>
    <div class="mt-1 text-2xl font-extrabold text-gray-900">
      <?php if ($role === 'superadmin'): ?>
        Panel SuperAdmin
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
        <div class="rounded-2xl border bg-white p-4">
          <div class="text-xs text-gray-500">Empresas activas</div>
          <div class="mt-1 text-2xl font-extrabold text-gray-900"><?= (int) ($stats['empresas_activas'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border bg-white p-4">
          <div class="text-xs text-gray-500">Suscripciones activas</div>
          <div class="mt-1 text-2xl font-extrabold text-gray-900"><?= (int) ($stats['suscripciones_activas'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border bg-white p-4">
          <div class="text-xs text-gray-500">Próximas a vencer (7 días)</div>
          <div class="mt-1 text-2xl font-extrabold text-gray-900"><?= (int) ($stats['suscripciones_por_vencer'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border bg-white p-4">
          <div class="text-xs text-gray-500">Vencidas</div>
          <div class="mt-1 text-2xl font-extrabold text-gray-900"><?= (int) ($stats['suscripciones_vencidas'] ?? 0) ?></div>
        </div>
      </div>

      <?php
        $ingMes = (float) ($stats['sus_ingreso_mes'] ?? 0);
        $ingPrev = (float) ($stats['sus_ingreso_mes_prev'] ?? 0);
        $deltaAbs = $ingMes - $ingPrev;
        $deltaPct = $ingPrev > 0 ? (($deltaAbs / $ingPrev) * 100) : ($ingMes > 0 ? 100 : 0);
      ?>
      <div class="mt-4 rounded-2xl border bg-white p-4">
        <div class="font-semibold text-gray-900">Ingresos por Suscripciones</div>
        <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="rounded-xl border p-3">
            <div class="text-xs text-gray-500">Mes actual</div>
            <div class="font-extrabold text-gray-900 text-2xl">$<?= number_format($ingMes, 2) ?></div>
          </div>
          <div class="rounded-xl border p-3">
            <div class="text-xs text-gray-500">Mes anterior</div>
            <div class="font-extrabold text-gray-900 text-2xl">$<?= number_format($ingPrev, 2) ?></div>
          </div>
          <div class="rounded-xl border p-3">
            <div class="text-xs text-gray-500">Variación</div>
            <div class="font-extrabold <?= $deltaAbs >= 0 ? 'text-teal-700' : 'text-red-700' ?> text-2xl"><?= $deltaAbs >= 0 ? '+' : '' ?>$<?= number_format($deltaAbs, 2) ?></div>
            <div class="text-xs <?= $deltaAbs >= 0 ? 'text-teal-700' : 'text-red-700' ?>"><?= $deltaAbs >= 0 ? '+' : '' ?><?= number_format($deltaPct, 1) ?>%</div>
          </div>
        </div>
        <div class="mt-4 grid grid-cols-6 gap-2 items-end">
          <?php
            $trend = $stats['sus_trend'] ?? [];
            $maxT = 0;
            foreach ($trend as $t) { $maxT = max($maxT, (float) ($t['monto'] ?? 0)); }
            $maxT = $maxT > 0 ? $maxT : 1;
          ?>
          <?php foreach ($trend as $t): ?>
            <?php $h = max(8, (int) round(((float) ($t['monto'] ?? 0) / $maxT) * 120)); ?>
            <div class="text-center">
              <div class="mx-auto w-8 rounded-t-md bg-teal-500" style="height: <?= $h ?>px"></div>
              <div class="text-[10px] text-gray-500 mt-1"><?= htmlspecialchars((string) ($t['label'] ?? '')) ?></div>
              <div class="text-[10px] font-semibold text-gray-700">$<?= number_format((float) ($t['monto'] ?? 0), 0) ?></div>
            </div>
          <?php endforeach; ?>
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
                    <?= htmlspecialchars($m['tipo'] ?? '') ?>    <?= ($m['descripcion'] ?? '') ? ' - ' . htmlspecialchars($m['descripcion']) : '' ?>
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

      <div class="mt-4 rounded-2xl border bg-white p-4">
        <div class="font-semibold text-gray-900">Estado del Hosting / Runtime</div>
        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
          <div class="rounded-xl border p-3">
            <div class="text-xs text-gray-500">PHP</div>
            <div class="font-bold text-gray-900"><?= htmlspecialchars((string) ($stats['php_version'] ?? '-')) ?></div>
          </div>
          <div class="rounded-xl border p-3">
            <div class="text-xs text-gray-500">Memoria proceso</div>
            <div class="font-bold text-gray-900"><?= number_format((float) ($stats['memory_usage_mb'] ?? 0), 2) ?> MB</div>
            <div class="text-xs text-gray-500">Pico: <?= number_format((float) ($stats['memory_peak_mb'] ?? 0), 2) ?> MB</div>
          </div>
          <div class="rounded-xl border p-3">
            <div class="text-xs text-gray-500">Límite memoria PHP</div>
            <div class="font-bold text-gray-900"><?= htmlspecialchars((string) ($stats['memory_limit'] ?? '-')) ?></div>
            <div class="text-xs text-gray-500">Max exec: <?= htmlspecialchars((string) ($stats['max_execution_time'] ?? '-')) ?>s</div>
          </div>
          <div class="rounded-xl border p-3">
            <div class="text-xs text-gray-500">DB size</div>
            <div class="font-bold text-gray-900"><?= number_format((float) ($stats['db_size_mb'] ?? 0), 2) ?> MB</div>
          </div>
          <div class="rounded-xl border p-3">
            <div class="text-xs text-gray-500">Disco usado</div>
            <div class="font-bold text-gray-900"><?= number_format((float) ($stats['disk_used_gb'] ?? 0), 2) ?> GB</div>
          </div>
          <div class="rounded-xl border p-3">
            <div class="text-xs text-gray-500">Disco libre</div>
            <div class="font-bold text-gray-900"><?= number_format((float) ($stats['disk_free_gb'] ?? 0), 2) ?> GB</div>
          </div>
          <div class="rounded-xl border p-3">
            <div class="text-xs text-gray-500">Upload/Post max</div>
            <div class="font-bold text-gray-900"><?= htmlspecialchars((string) ($stats['upload_max'] ?? '-')) ?> / <?= htmlspecialchars((string) ($stats['post_max'] ?? '-')) ?></div>
          </div>
          <div class="rounded-xl border p-3">
            <div class="text-xs text-gray-500">Estado App</div>
            <div class="font-bold text-teal-700">Operando</div>
          </div>
        </div>
      </div>

      <div class="mt-4 rounded-2xl border bg-white p-4">
        <div class="font-semibold text-gray-900">Métricas de Correo (30 días)</div>
        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 text-sm">
          <div class="rounded-xl border p-3">
            <div class="text-xs text-gray-500">Enviados</div>
            <div class="font-bold text-teal-700"><?= (int) (($stats['email']['sent'] ?? 0)) ?></div>
          </div>
          <div class="rounded-xl border p-3">
            <div class="text-xs text-gray-500">Fallidos</div>
            <div class="font-bold text-red-600"><?= (int) (($stats['email']['failed'] ?? 0)) ?></div>
          </div>
          <div class="rounded-xl border p-3">
            <div class="text-xs text-gray-500">Confirmaciones cita</div>
            <div class="font-bold text-gray-900"><?= (int) (($stats['email']['booking_sent'] ?? 0)) ?></div>
          </div>
          <div class="rounded-xl border p-3">
            <div class="text-xs text-gray-500">Invitaciones reseña</div>
            <div class="font-bold text-gray-900"><?= (int) (($stats['email']['review_sent'] ?? 0)) ?></div>
          </div>
          <div class="rounded-xl border p-3">
            <div class="text-xs text-gray-500">Recuperación de contraseña</div>
            <div class="font-bold text-gray-900"><?= (int) (($stats['email']['password_reset_sent'] ?? 0)) ?></div>
          </div>
          <div class="rounded-xl border p-3">
            <div class="text-xs text-gray-500">Verificación de correo</div>
            <div class="font-bold text-gray-900"><?= (int) (($stats['email']['email_verification_sent'] ?? 0)) ?></div>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <a href="<?= view_url('vistas/admin/admin-citas.php', $id_e) ?>"
          class="block rounded-xl border p-4 hover:bg-gray-50">
          <div class="font-semibold text-gray-900">Citas</div>
          <div class="text-sm text-gray-600">Ver y gestionar agenda.</div>
        </a>
        <a href="<?= view_url('vistas/admin/sucursales.php', $id_e) ?>"
          class="block rounded-xl border p-4 hover:bg-gray-50">
          <div class="font-semibold text-gray-900">Sedes</div>
          <div class="text-sm text-gray-600">Ver sucursales.</div>
        </a>
        <?php if (has_permission('permiso_leer')): ?>
          <a href="<?= view_url('vistas/admin/ajustes.php', $id_e) ?>" class="block rounded-xl border p-4 hover:bg-gray-50">
            <div class="font-semibold text-gray-900">Administración</div>
            <div class="text-sm text-gray-600">Panel administrativo.</div>
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php
include __DIR__ . '/../../includes/footer.php';
