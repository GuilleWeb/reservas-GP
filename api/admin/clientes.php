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
$empresa_id = ($role === 'superadmin' && $id_e) ? (int) $id_e : resolve_private_empresa_id($user);



$is_authorized = ($user && $empresa_id > 0 && in_array($role, ['superadmin', 'admin', 'gerente'], true));
if (!$is_authorized)
    json_response(['error' => 'unauthorized'], 403);

switch ($action) {
    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = max(1, (int) ($_GET['per'] ?? 10));
        $search = trim($_GET['search'] ?? '');

        $where = ['c.empresa_id = ?'];
        $params = [$empresa_id];
        if ($search !== '') {
            $where[] = '(c.nombre LIKE ? OR c.email LIKE ? OR c.telefono LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $whereSql = implode(' AND ', $where);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM clientes c WHERE $whereSql");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();
        $total_pages = (int) ceil($total / $per);

        $offset = ($page - 1) * $per;
        $sql = "SELECT c.*
                FROM clientes c
                WHERE $whereSql
                ORDER BY c.id DESC
                LIMIT $per OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => $total_pages]);
        break;

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT c.* FROM clientes c WHERE c.id=? AND c.empresa_id=? LIMIT 1');
        $stmt->execute([$id, $empresa_id]);
        json_response(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC) ?: []]);
        break;

    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
        $fecha_nacimiento = $fecha_nacimiento === '' ? null : $fecha_nacimiento;
        $notas = trim($_POST['notas'] ?? '');
        $activo = isset($_POST['activo']) ? (int) ($_POST['activo']) : 1;

        if ($nombre === '')
            json_response(['success' => false, 'message' => 'Nombre obligatorio.'], 200);
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
            json_response(['success' => false, 'message' => 'Email inválido.'], 200);

        try {
            $pdo->beginTransaction();

            if ($id > 0) {
                $stmt = $pdo->prepare('SELECT id FROM clientes WHERE id=? AND empresa_id=? LIMIT 1');
                $stmt->execute([$id, $empresa_id]);
                if (!$stmt->fetchColumn()) {
                    $pdo->rollBack();
                    json_response(['success' => false, 'message' => 'No autorizado.'], 403);
                }

                $stmt = $pdo->prepare('UPDATE clientes SET nombre=?, email=?, telefono=?, direccion=?, fecha_nacimiento=?, notas=?, activo=? WHERE id=? AND empresa_id=?');
                $stmt->execute([$nombre, $email ?: null, $telefono ?: null, $direccion !== '' ? $direccion : null, $fecha_nacimiento, $notas ?: null, $activo, $id, $empresa_id]);
            } else {
                if (!in_array($role, ['admin', 'superadmin'], true)) {
                    $pdo->rollBack();
                    json_response(['success' => false, 'message' => 'No autorizado.'], 403);
                }

                $stmt = $pdo->prepare('INSERT INTO clientes (empresa_id, nombre, email, telefono, direccion, fecha_nacimiento, notas, activo, created_at, updated_at) VALUES (?,?,?,?,?,?,?, ?,NOW(),NOW())');
                $stmt->execute([$empresa_id, $nombre, $email !== '' ? $email : null, $telefono !== '' ? $telefono : null, $direccion !== '' ? $direccion : null, $fecha_nacimiento, $notas !== '' ? $notas : null, $activo]);
                $id = (int) $pdo->lastInsertId();
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            $msg = $e->getMessage();
            // Mensajes amigables para el operador.
            if (stripos($msg, 'uq_clientes_empresa_email') !== false || stripos($msg, 'Duplicate entry') !== false) {
                json_response([
                    'success' => false,
                    'message' => 'Ya existe un cliente con ese correo en esta empresa. Usa otro correo o edita el cliente existente.'
                ], 200);
            }
            json_response(['success' => false, 'message' => 'No se pudo guardar el cliente. Verifica los datos e inténtalo de nuevo.'], 200);
        }

        json_response(['success' => true, 'id' => $id]);
        break;

    case 'delete':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0)
            json_response(['success' => false, 'message' => 'ID inválido.'], 200);

        $stmt = $pdo->prepare('SELECT id FROM clientes WHERE id=? AND empresa_id=? LIMIT 1');
        $stmt->execute([$id, $empresa_id]);
        if (!$stmt->fetchColumn())
            json_response(['success' => false, 'message' => 'No autorizado.'], 403);

        // Borrado lógico
        $stmt = $pdo->prepare('UPDATE clientes SET activo = 0 WHERE id=? AND empresa_id=?');
        $stmt->execute([$id, $empresa_id]);
        json_response(['success' => true]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
