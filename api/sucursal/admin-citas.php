<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
$id_e = request_id_e();

$roles_permitidos = ['superadmin', 'admin', 'gerente'];
if (!$user || !in_array($user['rol'] ?? null, $roles_permitidos)) {
    json_response(['error' => 'unauthorized'], 403);
}

$empresa_id = (int) ($user['empresa_id'] ?? 0);
$sucursal_id_filtro = (int) ($user['sucursal_id'] ?? 0);

// Mapeo de acciones para compatibilidad
if ($action === 'create')
    $action = 'save';
if ($action === 'read')
    $action = 'get';
if ($action === 'update')
    $action = 'save';

switch ($action) {
    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = max(1, (int) ($_GET['per'] ?? 10));
        $search = trim($_GET['search'] ?? '');
        $estado = $_GET['estado'] ?? '';

        $where = ['c.empresa_id = ?'];
        $params = [$empresa_id];

        if ($sucursal_id_filtro > 0) {
            $where[] = 'c.sucursal_id = ?';
            $params[] = $sucursal_id_filtro;
        }

        if ($search !== '') {
            $where[] = '(c.cliente_nombre LIKE ? OR c.cliente_email LIKE ? OR s.nombre LIKE ? OR u.nombre LIKE ?)';
            $term = "%$search%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }
        if ($estado !== '') {
            $where[] = 'c.estado = ?';
            $params[] = $estado;
        }

        $whereSql = implode(' AND ', $where);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM citas c LEFT JOIN sucursales s ON c.sucursal_id = s.id LEFT JOIN usuarios u ON c.empleado_usuario_id = u.id WHERE $whereSql");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $offset = ($page - 1) * $per;
        $sql = "
            SELECT c.*, s.nombre as sucursal_nombre, srv.nombre as servicio_nombre, u.nombre as empleado_nombre
            FROM citas c
            LEFT JOIN sucursales s ON c.sucursal_id = s.id
            LEFT JOIN servicios srv ON c.servicio_id = srv.id
            LEFT JOIN usuarios u ON c.empleado_usuario_id = u.id
            WHERE $whereSql
            ORDER BY c.inicio DESC LIMIT $per OFFSET $offset
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => ceil($total / $per)]);
        break;

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $sql = 'SELECT * FROM citas WHERE id = ? AND empresa_id = ?';
        $params = [$id, $empresa_id];
        if ($sucursal_id_filtro > 0) {
            $sql .= ' AND sucursal_id = ?';
            $params[] = $sucursal_id_filtro;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC) ?: []]);
        break;

    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $id = (int) ($_POST['id'] ?? 0);
        $suc_id = (int) ($_POST['sucursal_id'] ?? 0);
        $srv_id = (int) ($_POST['servicio_id'] ?? 0);
        $emp_id = (int) ($_POST['empleado_usuario_id'] ?? 0);

        // Si el gerente está atado a una sucursal, forzamos esa sucursal
        if ($sucursal_id_filtro > 0)
            $suc_id = $sucursal_id_filtro;

        $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
        $inicio = trim($_POST['inicio'] ?? '');
        $estado = trim($_POST['estado'] ?? 'pendiente');

        if (!$cliente_nombre || !$suc_id || !$srv_id || !$emp_id || !$inicio) {
            json_response(['success' => false, 'message' => 'Faltan campos obligatorios.']);
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE citas SET sucursal_id=?, servicio_id=?, empleado_usuario_id=?, cliente_nombre=?, inicio=?, estado=? WHERE id=? AND empresa_id=?');
            $stmt->execute([$suc_id, $srv_id, $emp_id, $cliente_nombre, $inicio, $estado, $id, $empresa_id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO citas (empresa_id, sucursal_id, servicio_id, empleado_usuario_id, cliente_nombre, inicio, estado, creado_por_usuario_id) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([$empresa_id, $suc_id, $srv_id, $emp_id, $cliente_nombre, $inicio, $estado, $user['id']]);
            $id = (int) $pdo->lastInsertId();
        }
        json_response(['success' => true, 'id' => $id]);
        break;

    case 'delete':
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE citas SET estado='cancelada' WHERE id=? AND empresa_id=?");
        $stmt->execute([$id, $empresa_id]);
        json_response(['success' => true]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
