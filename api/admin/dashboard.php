<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? 'stats';
$user = current_user();
$id_e = request_id_e();
$role = $user['rol'] ?? null;
$empresa_id = (int) ($user['empresa_id'] ?? 0);



$is_authorized = ($user && $empresa_id > 0 && in_array($role, ['admin', 'superadmin'], true));
if (!$is_authorized)
    json_response(['error' => 'unauthorized'], 403);

switch ($action) {
    case 'stats':
        $out = [];
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM sucursales WHERE empresa_id=? AND activo=1');
        $stmt->execute([$empresa_id]);
        $out['sucursales_activas'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM servicios WHERE empresa_id=? AND activo=1');
        $stmt->execute([$empresa_id]);
        $out['servicios_activos'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE empresa_id=? AND activo=1 AND rol IN ('admin','gerente','empleado')");
        $stmt->execute([$empresa_id]);
        $out['empleados_activos'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE empresa_id=? AND estado IN ('pendiente','confirmada')");
        $stmt->execute([$empresa_id]);
        $out['citas_abiertas'] = (int) $stmt->fetchColumn();

        json_response(['success' => true, 'data' => $out]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
