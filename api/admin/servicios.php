<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
if ($action === 'create')
    $action = 'save';
if ($action === 'read')
    $action = 'get';
if ($action === 'update')
    $action = 'save';

$user = current_user();
$id_e = request_id_e();
$role = $user['rol'] ?? null;
$empresa_id = (int) ($user['empresa_id'] ?? 0);



$is_authorized = ($user && $empresa_id > 0 && in_array($role, ['admin', 'superadmin'], true));
if (!$is_authorized)
    json_response(['error' => 'unauthorized'], 403);

switch ($action) {
    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = max(1, (int) ($_GET['per'] ?? 10));
        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';

        $where = ['empresa_id = ?'];
        $params = [$empresa_id];

        if ($search !== '') {
            $where[] = '(nombre LIKE ? OR descripcion LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($status !== '') {
            $where[] = 'activo = ?';
            $params[] = (int) $status;
        }

        $whereSql = implode(' AND ', $where);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM servicios WHERE $whereSql");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();
        $total_pages = (int) ceil($total / $per);

        $offset = ($page - 1) * $per;
        $sql = "SELECT * FROM servicios WHERE $whereSql ORDER BY id DESC LIMIT $per OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => $total_pages]);
        break;

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM servicios WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$id, $empresa_id]);
        json_response(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC) ?: []]);
        break;

    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $duracion = max(5, (int) ($_POST['duracion_minutos'] ?? 30));
        $precio = (float) ($_POST['precio_base'] ?? 0.0);
        $activo = isset($_POST['activo']) ? (int) ($_POST['activo']) : 1;

        if ($nombre === '')
            json_response(['success' => false, 'message' => 'El nombre es obligatorio.']);

        try {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE servicios SET nombre=?, descripcion=?, duracion_minutos=?, precio_base=?, activo=? WHERE id=? AND empresa_id=?');
                $stmt->execute([$nombre, $descripcion, $duracion, $precio, $activo, $id, $empresa_id]);
                audit_event('update', 'servicios', $id, "Servicio actualizado: $nombre", $empresa_id);
            } else {
                $stmt = $pdo->prepare('INSERT INTO servicios (empresa_id, nombre, descripcion, duracion_minutos, precio_base, activo) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$empresa_id, $nombre, $descripcion, $duracion, $precio, $activo]);
                $id = (int) $pdo->lastInsertId();
                audit_event('create', 'servicios', $id, "Nuevo servicio creado: $nombre", $empresa_id);
            }
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => 'Error, o servicio con el mismo nombre ya existe.']);
        }

        json_response(['success' => true, 'id' => $id]);
        break;

    case 'delete':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0)
            json_response(['success' => false, 'message' => 'ID inválido.']);

        // Borrado lógico
        $stmt = $pdo->prepare('UPDATE servicios SET activo = 0 WHERE id=? AND empresa_id=?');
        $stmt->execute([$id, $empresa_id]);
        audit_event('delete', 'servicios', $id, 'Servicio desactivado', $empresa_id);
        json_response(['success' => true]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
