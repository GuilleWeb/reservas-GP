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
if (!$is_authorized && $action !== 'list_public')
    json_response(['error' => 'unauthorized'], 403);

switch ($action) {
    case 'list_public':
        if (!$id_e)
            json_response(['error' => 'No empresa specified'], 400);

        $stmt_emp = $pdo->prepare('SELECT id FROM empresas WHERE slug = ? LIMIT 1');
        $stmt_emp->execute([$id_e]);
        $emp_id = (int) ($stmt_emp->fetchColumn() ?: 0);
        if ($emp_id <= 0)
            json_response(['success' => false, 'data' => []]);

        $stmt = $pdo->prepare('SELECT nombre, especialidad, descripcion, imagen_path FROM equipo WHERE empresa_id = ? AND visible_en_home = 1 AND activo = 1 ORDER BY orden ASC, created_at DESC');
        $stmt->execute([$emp_id]);
        json_response(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'list':
        if (!$is_tenant_admin)
            json_response(['error' => 'unauthorized'], 403);
        $empresa_id = (int) ($user['empresa_id'] ?? 0);
        if ($empresa_id <= 0)
            json_response(['error' => 'unauthorized'], 403);

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = max(1, (int) ($_GET['per'] ?? 10));

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM equipo WHERE empresa_id = ?');
        $stmt->execute([$empresa_id]);
        $total = (int) $stmt->fetchColumn();
        $total_pages = (int) ceil($total / $per);

        $offset = ($page - 1) * $per;
        $sql = "SELECT * FROM equipo WHERE empresa_id = ? ORDER BY orden ASC, id DESC LIMIT $per OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$empresa_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => $total_pages]);
        break;

    case 'get':
        if (!$is_tenant_admin)
            json_response(['error' => 'unauthorized'], 403);
        $empresa_id = (int) ($user['empresa_id'] ?? 0);

        $id = (int) ($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM equipo WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$id, $empresa_id]);
        json_response(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC) ?: []]);
        break;

    case 'save':
        if (!$is_tenant_admin)
            json_response(['error' => 'unauthorized'], 403);
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $empresa_id = (int) ($user['empresa_id'] ?? 0);
        if ($empresa_id <= 0)
            json_response(['error' => 'unauthorized'], 403);

        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $especialidad = trim($_POST['especialidad'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $orden = (int) ($_POST['orden'] ?? 0);
        $visible_en_home = isset($_POST['visible_en_home']) ? (int) ($_POST['visible_en_home']) : 0;
        $activo = isset($_POST['activo']) ? (int) ($_POST['activo']) : 1;

        if ($nombre === '')
            json_response(['success' => false, 'message' => 'El nombre del especialista es requerido.']);

        $rutaImg = null;
        if (!empty($_FILES['imagen']['name'])) {
            $dir = __DIR__ . '/../../assets/equipo/';
            if (!is_dir($dir))
                mkdir($dir, 0777, true);
            $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $nombreArchivo = 'eq_' . $empresa_id . '_' . time() . '.' . strtolower($ext);
            $ruta = $dir . $nombreArchivo;
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta)) {
                $rutaImg = 'assets/equipo/' . $nombreArchivo;
            }
        }

        try {
            if ($id > 0) {
                if ($rutaImg) {
                    $stmt = $pdo->prepare('UPDATE equipo SET nombre=?, especialidad=?, descripcion=?, imagen_path=?, orden=?, visible_en_home=?, activo=? WHERE id=? AND empresa_id=?');
                    $stmt->execute([$nombre, $especialidad, $descripcion, $rutaImg, $orden, $visible_en_home, $activo, $id, $empresa_id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE equipo SET nombre=?, especialidad=?, descripcion=?, orden=?, visible_en_home=?, activo=? WHERE id=? AND empresa_id=?');
                    $stmt->execute([$nombre, $especialidad, $descripcion, $orden, $visible_en_home, $activo, $id, $empresa_id]);
                }
            } else {
                $stmt = $pdo->prepare('INSERT INTO equipo (empresa_id, nombre, especialidad, descripcion, imagen_path, orden, visible_en_home, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$empresa_id, $nombre, $especialidad, $descripcion, $rutaImg, $orden, $visible_en_home, $activo]);
                $id = (int) $pdo->lastInsertId();
            }
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => 'Error al guardar.']);
        }

        json_response(['success' => true, 'id' => $id]);
        break;

    case 'delete':
        if (!$is_tenant_admin)
            json_response(['error' => 'unauthorized'], 403);
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $empresa_id = (int) ($user['empresa_id'] ?? 0);
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0)
            json_response(['success' => false, 'message' => 'ID inválido.']);

        // Borrado lógico
        $stmt = $pdo->prepare('UPDATE equipo SET activo = 0 WHERE id=? AND empresa_id=?');
        $stmt->execute([$id, $empresa_id]);
        json_response(['success' => true]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
