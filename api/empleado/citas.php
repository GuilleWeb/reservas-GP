<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
$id_e = request_id_e();

$roles_permitidos = ['superadmin', 'admin', 'gerente', 'empleado'];
if (!$user || !in_array($user['rol'] ?? null, $roles_permitidos)) {
    json_response(['error' => 'unauthorized'], 403);
}

$empresa_id = (int) ($user['empresa_id'] ?? 0);
$empleado_id = (int) ($user['id'] ?? 0);

switch ($action) {
    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = max(1, (int) ($_GET['per'] ?? 10));
        $offset = ($page - 1) * $per;

        $stmt = $pdo->prepare("
            SELECT c.*, s.nombre as sucursal_nombre, srv.nombre as servicio_nombre
            FROM citas c
            LEFT JOIN sucursales s ON c.sucursal_id = s.id
            LEFT JOIN servicios srv ON c.servicio_id = srv.id
            WHERE c.empresa_id = ? AND c.empleado_usuario_id = ?
            ORDER BY c.inicio DESC LIMIT ? OFFSET ?
        ");
        $stmt->execute([$empresa_id, $empleado_id, $per, $offset]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE empresa_id = ? AND empleado_usuario_id = ?");
        $stmt->execute([$empresa_id, $empleado_id]);
        $total = (int) $stmt->fetchColumn();

        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => ceil($total / $per)]);
        break;

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM citas WHERE id = ? AND empresa_id = ? AND empleado_usuario_id = ?');
        $stmt->execute([$id, $empresa_id, $empleado_id]);
        json_response(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC) ?: []]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
