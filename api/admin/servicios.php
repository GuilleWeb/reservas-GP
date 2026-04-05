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
$empresa_id = resolve_private_empresa_id($user);



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
        $selected = array_flip(home_page_selected_ids($empresa_id, 'servicios'));
        foreach ($data as &$row) {
            $row['show_in_home'] = isset($selected[(int) ($row['id'] ?? 0)]) ? 1 : 0;
        }
        unset($row);

        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => $total_pages]);
        break;

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM servicios WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$id, $empresa_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        if ($row) {
            $row['show_in_home'] = home_page_is_item_selected($empresa_id, 'servicios', (int) $id) ? 1 : 0;
            $stmt = $pdo->prepare('SELECT empleado_usuario_id FROM empleado_servicios WHERE servicio_id = ? AND activo = 1');
            $stmt->execute([$id]);
            $row['empleado_ids'] = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        }
        json_response(['success' => true, 'data' => $row]);
        break;

    case 'get_options':
        $stmt = $pdo->prepare("SELECT u.id, u.nombre, COALESCE(s.nombre, 'Sin sucursal') AS sucursal_nombre, COALESCE(u.sucursal_id, 0) AS sucursal_id
                               FROM usuarios u
                               LEFT JOIN sucursales s ON s.id = u.sucursal_id
                               WHERE u.empresa_id = ? AND u.rol IN ('admin','gerente','empleado') AND u.activo = 1
                               ORDER BY COALESCE(s.nombre, 'Sin sucursal') ASC, u.nombre ASC");
        $stmt->execute([$empresa_id]);
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'empleados' => $empleados]);
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
        $show_in_home = isset($_POST['show_in_home']) ? (int) $_POST['show_in_home'] : 0;
        $empleado_ids = $_POST['empleado_ids'] ?? [];
        if (!is_array($empleado_ids)) $empleado_ids = [];
        $empleado_ids = array_values(array_unique(array_filter(array_map('intval', $empleado_ids), static fn($v) => $v > 0)));

        if ($nombre === '')
            json_response(['success' => false, 'message' => 'El nombre es obligatorio.']);
        if (empty($empleado_ids))
            json_response(['success' => false, 'message' => 'Debes asignar al menos un empleado al servicio.']);

        $stmt = $pdo->prepare('SELECT id FROM servicios WHERE empresa_id = ? AND LOWER(nombre) = LOWER(?) AND id <> ? LIMIT 1');
        $stmt->execute([$empresa_id, $nombre, $id]);
        if ($stmt->fetchColumn()) {
            json_response(['success' => false, 'message' => 'Ya existe otro servicio con ese nombre.']);
        }

        try {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE servicios SET nombre=?, descripcion=?, duracion_minutos=?, precio_base=?, activo=? WHERE id=? AND empresa_id=?');
                $stmt->execute([$nombre, $descripcion, $duracion, $precio, $activo, $id, $empresa_id]);
                try {
                    audit_event('update', 'servicios', $id, "Servicio actualizado: $nombre", $empresa_id);
                } catch (Throwable $ignore) {
                }
            } else {
                $stmt = $pdo->prepare('INSERT INTO servicios (empresa_id, nombre, descripcion, duracion_minutos, precio_base, activo) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$empresa_id, $nombre, $descripcion, $duracion, $precio, $activo]);
                $id = (int) $pdo->lastInsertId();
                try {
                    audit_event('create', 'servicios', $id, "Nuevo servicio creado: $nombre", $empresa_id);
                } catch (Throwable $ignore) {
                }
            }
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => 'No se pudo guardar el servicio. Intenta nuevamente.']);
        }

        // Sin detener el flujo de guardado principal.
        try {
            $stmt = $pdo->prepare('SELECT id FROM sucursales WHERE empresa_id = ? AND activo = 1');
            $stmt->execute([$empresa_id]);
            $all_sucursales = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
            if (!empty($all_sucursales)) {
                $ins = $pdo->prepare('INSERT INTO servicio_sucursales (servicio_id, sucursal_id, activo) VALUES (?, ?, 1)
                                      ON DUPLICATE KEY UPDATE activo = VALUES(activo)');
                foreach ($all_sucursales as $sid) {
                    $ins->execute([$id, $sid]);
                }
            }
        } catch (Throwable $ignore) {
        }

        try {
            $stmt = $pdo->prepare('UPDATE empleado_servicios SET activo = 0 WHERE servicio_id = ?');
            $stmt->execute([$id]);
            $ins = $pdo->prepare('INSERT INTO empleado_servicios (empleado_usuario_id, servicio_id, precio_override, activo) VALUES (?, ?, NULL, 1)
                                  ON DUPLICATE KEY UPDATE activo = VALUES(activo)');
            foreach ($empleado_ids as $uid) {
                $ins->execute([$uid, $id]);
            }
        } catch (Throwable $ignore) {
        }
        try {
            home_page_sync_item($empresa_id, 'servicios', (int) $id, $show_in_home === 1 && $activo === 1);
        } catch (Throwable $ignore) {
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
        home_page_sync_item($empresa_id, 'servicios', (int) $id, false);
        audit_event('delete', 'servicios', $id, 'Servicio desactivado', $empresa_id);
        json_response(['success' => true]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
