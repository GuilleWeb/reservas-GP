<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$user = current_user();
$id_e = request_id_e();
$roles_permitidos = ['superadmin', 'admin', 'gerente', 'empleado'];
if (!$user || !in_array($user['rol'] ?? null, $roles_permitidos, true)) {
    json_response(['error' => 'unauthorized'], 403);
}

$empresa_id = resolve_private_empresa_id($user);
$empleado_id = (int) ($user['id'] ?? 0);
if ($empresa_id <= 0 || $empleado_id <= 0) {
    json_response(['error' => 'unauthorized'], 403);
}

// 1) Citas hoy
$stmt = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE empresa_id = ? AND empleado_usuario_id = ? AND DATE(inicio) = CURDATE()");
$stmt->execute([$empresa_id, $empleado_id]);
$citas_hoy = (int) $stmt->fetchColumn();

// 2) Pendientes próximas
$stmt = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE empresa_id = ? AND empleado_usuario_id = ? AND estado = 'pendiente' AND inicio >= NOW()");
$stmt->execute([$empresa_id, $empleado_id]);
$pendientes = (int) $stmt->fetchColumn();

// 3) Totales comparativos
$stmt = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE empresa_id = ? AND empleado_usuario_id = ?");
$stmt->execute([$empresa_id, $empleado_id]);
$citas_total = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE empresa_id = ? AND empleado_usuario_id = ? AND estado = 'completada'");
$stmt->execute([$empresa_id, $empleado_id]);
$citas_completadas = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE empresa_id = ? AND empleado_usuario_id = ? AND estado = 'cancelada'");
$stmt->execute([$empresa_id, $empleado_id]);
$citas_canceladas = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*)
                       FROM citas
                       WHERE empresa_id = ? AND empleado_usuario_id = ?
                         AND YEAR(inicio)=YEAR(CURDATE()) AND MONTH(inicio)=MONTH(CURDATE())");
$stmt->execute([$empresa_id, $empleado_id]);
$citas_mes = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*)
                       FROM citas
                       WHERE empresa_id = ? AND empleado_usuario_id = ?
                         AND YEAR(inicio)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                         AND MONTH(inicio)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
$stmt->execute([$empresa_id, $empleado_id]);
$citas_mes_prev = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(s.precio_base),0)
                       FROM citas c
                       JOIN servicios s ON s.id = c.servicio_id
                       WHERE c.empresa_id = ? AND c.empleado_usuario_id = ?
                         AND c.estado IN ('confirmada','completada')
                         AND YEAR(c.inicio)=YEAR(CURDATE()) AND MONTH(c.inicio)=MONTH(CURDATE())");
$stmt->execute([$empresa_id, $empleado_id]);
$ingresos_mes = (float) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(s.precio_base),0)
                       FROM citas c
                       JOIN servicios s ON s.id = c.servicio_id
                       WHERE c.empresa_id = ? AND c.empleado_usuario_id = ?
                         AND c.estado IN ('confirmada','completada')
                         AND YEAR(c.inicio)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                         AND MONTH(c.inicio)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
$stmt->execute([$empresa_id, $empleado_id]);
$ingresos_mes_prev = (float) $stmt->fetchColumn();

// 4) Próximas citas
$stmt = $pdo->prepare("SELECT c.*, s.nombre as sucursal_nombre, srv.nombre as servicio_nombre
                       FROM citas c
                       LEFT JOIN sucursales s ON c.sucursal_id = s.id
                       LEFT JOIN servicios srv ON c.servicio_id = srv.id
                       WHERE c.empresa_id = ? AND c.empleado_usuario_id = ? AND c.inicio >= NOW()
                       ORDER BY c.inicio ASC LIMIT 5");
$stmt->execute([$empresa_id, $empleado_id]);
$proximas = $stmt->fetchAll(PDO::FETCH_ASSOC);

json_response([
    'success' => true,
    'stats' => [
        'citas_hoy' => $citas_hoy,
        'pendientes' => $pendientes,
        'citas_total' => $citas_total,
        'citas_completadas' => $citas_completadas,
        'citas_canceladas' => $citas_canceladas,
        'citas_mes' => $citas_mes,
        'citas_mes_prev' => $citas_mes_prev,
        'ingresos_mes' => $ingresos_mes,
        'ingresos_mes_prev' => $ingresos_mes_prev
    ],
    'proximas' => $proximas
]);
