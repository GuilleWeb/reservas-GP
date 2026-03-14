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
if (!$is_authorized && $action !== 'list')
    json_response(['error' => 'unauthorized'], 403);

switch ($action) {
    case 'list':
        if ($empresa_id <= 0)
            json_response(['success' => true, 'data' => [], 'total' => 0, 'total_pages' => 1]);

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = max(1, (int) ($_GET['per'] ?? 10));
        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';
        $solo_activas = isset($_GET['activos']) ? 1 : 0;

        $where = ['empresa_id = ?'];
        $params = [$empresa_id];

        if ($search !== '') {
            $where[] = '(nombre LIKE ? OR slug LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($status !== '') {
            $where[] = 'activo = ?';
            $params[] = (int) $status;
        } elseif ($solo_activas) {
            $where[] = 'activo = 1';
        }

        $whereSql = implode(' AND ', $where);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sucursales WHERE $whereSql");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();
        $total_pages = (int) ceil($total / $per);

        $offset = ($page - 1) * $per;
        $sql = "SELECT * FROM sucursales WHERE $whereSql ORDER BY id DESC LIMIT $per OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => $total_pages]);
        break;

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM sucursales WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$id, $empresa_id]);
        json_response(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC) ?: []]);
        break;

    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $slug = trim($_POST['slug'] ?? strtolower(str_replace(' ', '-', $nombre)));
        $direccion = trim($_POST['direccion'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $horarioTexto = trim($_POST['horario'] ?? '');

        $horariosJson = json_encode(['lun-vie' => ['inicio' => '08:00', 'fin' => '18:00'], 'sab' => ['inicio' => '09:00', 'fin' => '13:00'], 'texto' => $horarioTexto], JSON_UNESCAPED_UNICODE);
        $activo = isset($_POST['activo']) ? (int) ($_POST['activo']) : 1;

        if ($nombre === '')
            json_response(['success' => false, 'message' => 'El nombre es obligatorio.']);

        try {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE sucursales SET nombre=?, slug=?, direccion=?, telefono=?, email=?, horarios_json=?, activo=? WHERE id=? AND empresa_id=?');
                $stmt->execute([$nombre, $slug, $direccion, $telefono, $email, $horariosJson, $activo, $id, $empresa_id]);
                audit_event('update', 'sucursales', $id, "Sucursal actualizada: $nombre", $empresa_id);
            } else {
                $stmt = $pdo->prepare('INSERT INTO sucursales (empresa_id, nombre, slug, direccion, telefono, email, horarios_json, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$empresa_id, $nombre, $slug, $direccion, $telefono, $email, $horariosJson, $activo]);
                $id = (int) $pdo->lastInsertId();
                audit_event('create', 'sucursales', $id, "Nueva sucursal creada: $nombre", $empresa_id);
            }
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => 'Error al guardar, verificar campos obligatorios o slug repetido.']);
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
        $stmt = $pdo->prepare('UPDATE sucursales SET activo = 0 WHERE id=? AND empresa_id=?');
        $stmt->execute([$id, $empresa_id]);
        audit_event('delete', 'sucursales', $id, 'Sucursal desactivada', $empresa_id);
        json_response(['success' => true]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
