<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
$role = $user['rol'] ?? null;
$empresa_id = resolve_private_empresa_id($user);
$sucursal_id_filtro = (int) ($user['sucursal_id'] ?? 0);

if (!$user || $empresa_id <= 0 || !in_array($role, ['superadmin', 'admin', 'gerente'], true)) {
    json_response(['error' => 'unauthorized'], 403);
}

switch ($action) {
    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = max(1, min(100, (int) ($_GET['per'] ?? 15)));
        $search = trim((string) ($_GET['search'] ?? ''));
        $activo = (string) ($_GET['activo'] ?? '');

        $where = ['empresa_id = :eid'];
        $params = [':eid' => $empresa_id];
        if ($search !== '') {
            $where[] = '(nombre LIKE :s OR descripcion LIKE :s)';
            $params[':s'] = "%$search%";
        }
        if ($activo !== '' && ($activo === '0' || $activo === '1')) {
            $where[] = 'activo = :a';
            $params[':a'] = (int) $activo;
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM servicios $whereSql");
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $total = (int) ($stmt->fetchColumn() ?: 0);
        $total_pages = (int) ceil($total / $per);

        $offset = ($page - 1) * $per;
        $stmt = $pdo->prepare("SELECT id, empresa_id, nombre, precio_base, duracion_minutos, descripcion, activo
                               FROM servicios
                               $whereSql
                               ORDER BY nombre ASC
                               LIMIT :o,:p");
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':o', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':p', $per, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => max(1, $total_pages)]);
        break;

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM servicios WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$id, $empresa_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        if ($row) {
            if ($sucursal_id_filtro > 0) {
                $stmt = $pdo->prepare("SELECT es.empleado_usuario_id
                                       FROM empleado_servicios es
                                       JOIN usuarios u ON u.id = es.empleado_usuario_id
                                       WHERE es.servicio_id = ? AND es.activo = 1 AND u.empresa_id = ? AND u.sucursal_id = ? AND u.activo = 1");
                $stmt->execute([$id, $empresa_id, $sucursal_id_filtro]);
            } else {
                $stmt = $pdo->prepare("SELECT empleado_usuario_id FROM empleado_servicios WHERE servicio_id = ? AND activo = 1");
                $stmt->execute([$id]);
            }
            $row['empleado_ids'] = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        }
        json_response(['success' => true, 'data' => $row]);
        break;

    case 'get_options':
        $sql = "SELECT id, nombre, rol FROM usuarios
                WHERE empresa_id = ?
                  AND activo = 1
                  AND rol IN ('empleado')";
        $params = [$empresa_id];
        if ($sucursal_id_filtro > 0) {
            $sql .= " AND sucursal_id = ?";
            $params[] = $sucursal_id_filtro;
        }
        $sql .= " ORDER BY rol ASC, nombre ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response(['success' => true, 'empleados' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'delete':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);
        if ($role === 'gerente')
            json_response(['success' => false, 'message' => 'No autorizado.'], 403);

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0)
            json_response(['success' => false, 'message' => 'ID inválido.'], 200);

        $stmt = $pdo->prepare('SELECT id FROM servicios WHERE id = ? AND empresa_id = ? LIMIT 1');
        $stmt->execute([$id, $empresa_id]);
        if (!$stmt->fetchColumn())
            json_response(['success' => false, 'message' => 'Servicio no encontrado.'], 404);

        $stmt = $pdo->prepare('UPDATE servicios SET activo = 0 WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$id, $empresa_id]);
        json_response(['success' => true]);
        break;

    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_response(['error' => 'invalid_method'], 405);

        $id = (int) ($_POST['id'] ?? 0);
        if ($role === 'gerente' && $id <= 0) {
            json_response(['success' => false, 'message' => 'El gerente no puede crear servicios; solo editar los existentes.'], 200);
        }
        $nombre = trim($_POST['nombre'] ?? '');
        $precio = (float) ($_POST['precio_base'] ?? 0);
        $duracion = (int) ($_POST['duracion_minutos'] ?? 30);
        $desc = trim($_POST['descripcion'] ?? '');
        $activo = isset($_POST['activo']) ? (int) $_POST['activo'] : 1;
        $empleado_ids = $_POST['empleado_ids'] ?? [];
        if (!is_array($empleado_ids)) $empleado_ids = [];
        $empleado_ids = array_values(array_unique(array_filter(array_map('intval', $empleado_ids), static fn($v) => $v > 0)));

        if ($nombre === '') json_response(['success' => false, 'message' => 'Nombre obligatorio.']);
        if (empty($empleado_ids)) json_response(['success' => false, 'message' => 'Asigna al menos un empleado.']);

        $stmt = $pdo->prepare('SELECT id FROM servicios WHERE empresa_id = ? AND LOWER(nombre)=LOWER(?) AND id<>? LIMIT 1');
        $stmt->execute([$empresa_id, $nombre, $id]);
        if ($stmt->fetchColumn()) json_response(['success' => false, 'message' => 'Ya existe otro servicio con ese nombre.']);

        $pdo->beginTransaction();
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE servicios SET nombre=?, precio_base=?, duracion_minutos=?, descripcion=?, activo=? WHERE id=? AND empresa_id=?');
                $stmt->execute([$nombre, $precio, $duracion, $desc, $activo, $id, $empresa_id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO servicios (empresa_id, nombre, precio_base, duracion_minutos, descripcion, activo) VALUES (?,?,?,?,?,?)');
                $stmt->execute([$empresa_id, $nombre, $precio, $duracion, $desc, $activo]);
                $id = (int) $pdo->lastInsertId();
            }

            $sqlUsers = "SELECT id FROM usuarios WHERE empresa_id = ? AND activo = 1 AND rol IN ('empleado')";
            $paramsUsers = [$empresa_id];
            if ($sucursal_id_filtro > 0) {
                $sqlUsers .= " AND sucursal_id = ?";
                $paramsUsers[] = $sucursal_id_filtro;
            }
            $stmt = $pdo->prepare($sqlUsers);
            $stmt->execute($paramsUsers);
            $scope_users = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
            if (!empty($scope_users)) {
                $ph = implode(',', array_fill(0, count($scope_users), '?'));
                $stmt = $pdo->prepare("UPDATE empleado_servicios SET activo = 0 WHERE servicio_id = ? AND empleado_usuario_id IN ($ph)");
                $stmt->execute(array_merge([(int) $id], $scope_users));
            }

            $ins = $pdo->prepare('INSERT INTO empleado_servicios (empleado_usuario_id, servicio_id, precio_override, activo) VALUES (?, ?, NULL, 1)
                                  ON DUPLICATE KEY UPDATE activo = VALUES(activo)');
            foreach ($empleado_ids as $uid) {
                if ($sucursal_id_filtro > 0 && !in_array($uid, $scope_users, true)) continue;
                $ins->execute([$uid, $id]);
            }

            $pdo->commit();
            json_response(['success' => true, 'id' => $id]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            json_response(['success' => false, 'message' => 'No se pudo guardar el servicio.']);
        }
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
