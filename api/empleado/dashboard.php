<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$user = current_user();
$id_e = request_id_e();
$roles_permitidos = ['superadmin', 'admin', 'gerente', 'empleado'];
if (!$user || !in_array($user['rol'] ?? null, $roles_permitidos)) {
    json_response(['error' => 'unauthorized'], 403);
}

$empresa_id = (int) ($user['empresa_id'] ?? 0);
$empleado_id = (int) ($user['id'] ?? 0);

// Stats
// 1. Citas de hoy
$stmt = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE empresa_id = ? AND empleado_usuario_id = ? AND DATE(inicio) = CURDATE()");
$stmt->execute([$empresa_id, $empleado_id]);
$citas_hoy = (int) $stmt->fetchColumn();

// 2. Citas pendientes próximas
$stmt = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE empresa_id = ? AND empleado_usuario_id = ? AND estado = 'pendiente' AND inicio >= NOW()");
$stmt->execute([$empresa_id, $empleado_id]);
$pendientes = (int) $stmt->fetchColumn();

// 3. Resumen de próximas 5 citas
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
        'pendientes' => $pendientes
    ],
    'proximas' => $proximas
]);
