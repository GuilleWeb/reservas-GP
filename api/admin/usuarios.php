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

// Un usuario de empresa tiene su propia empresa_id en la sesión. 
// Un superadmin puede actuar sobre cualquier empresa si se pasa el slug.
$empresa_id = resolve_private_empresa_id($user);



$is_authorized = ($user && $empresa_id > 0 && in_array($role, ['admin', 'superadmin'], true));
if (!$is_authorized)
    json_response(['error' => 'unauthorized'], 403);

function admin_manageable_roles($actor_role)
{
    if ($actor_role === 'superadmin') {
        return ['admin', 'gerente', 'empleado', 'cliente'];
    }
    if ($actor_role === 'admin') {
        return ['gerente', 'empleado', 'cliente'];
    }
    return [];
}

$manageable_roles = admin_manageable_roles($role);

switch ($action) {
    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = max(1, (int) ($_GET['per'] ?? 10));
        $search = trim($_GET['search'] ?? '');
        $rol = trim($_GET['rol'] ?? '');
        $activo = $_GET['activo'] ?? '';
        $sort = trim($_GET['sort'] ?? 'id');
        $dir = strtolower(trim($_GET['dir'] ?? 'desc'));
        $dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc';

        $sortMap = [
            'id' => 'u.id',
            'nombre' => 'u.nombre',
            'email' => 'u.email',
            'rol' => 'u.rol',
            'activo' => 'u.activo',
            'sucursal' => 's.nombre',
        ];
        $orderBy = $sortMap[$sort] ?? 'u.id';

        $where = ['u.empresa_id = :eid'];
        $params = [':eid' => $empresa_id];
        if ($search !== '') {
            $where[] = '(u.nombre LIKE :s OR u.email LIKE :s)';
            $params[':s'] = "%$search%";
        }
        $placeholders_roles = "'" . implode("','", array_map(static fn($r) => str_replace("'", '', $r), $manageable_roles)) . "'";
        $where[] = "u.rol IN ($placeholders_roles)";
        if ($rol !== '' && in_array($rol, $manageable_roles, true)) {
            $where[] = 'u.rol = :rol';
            $params[':rol'] = $rol;
        }
        if ($activo !== '' && ($activo === '0' || $activo === '1')) {
            $where[] = 'u.activo = :a';
            $params[':a'] = (int) $activo;
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios u $whereSql");
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $total = (int) $stmt->fetchColumn();
        $total_pages = (int) ceil($total / $per);

        $offset = ($page - 1) * $per;
        $sql = "
            SELECT u.id, u.nombre, u.email, u.telefono, u.rol, u.activo, u.empresa_id, u.sucursal_id,
                   s.nombre AS sucursal_nombre
            FROM usuarios u
            LEFT JOIN sucursales s ON s.id = u.sucursal_id
            $whereSql
            ORDER BY $orderBy $dir
            LIMIT :o,:p
        ";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':o', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':p', $per, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $selected = array_flip(home_page_selected_ids($empresa_id, 'usuarios'));
        foreach ($data as &$row) {
            $row['show_in_home'] = isset($selected[(int) ($row['id'] ?? 0)]) ? 1 : 0;
        }
        unset($row);

        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => $total_pages]);
        break;

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT id, empresa_id, sucursal_id, rol, nombre, email, telefono, activo FROM usuarios WHERE id=? LIMIT 1');
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        if ($data && (int) $data['empresa_id'] !== $empresa_id)
            $data = [];
        if ($data && !in_array((string) ($data['rol'] ?? ''), $manageable_roles, true))
            $data = [];
        if ($data) {
            $data['show_in_home'] = home_page_is_item_selected($empresa_id, 'usuarios', (int) $id) ? 1 : 0;
        }
        json_response(['success' => true, 'data' => $data]);
        break;

    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $rol = trim($_POST['rol'] ?? '');
        $sucursal_id = $_POST['sucursal_id'] ?? null;
        $sucursal_id = ($sucursal_id === '' || $sucursal_id === null) ? null : (int) $sucursal_id;
        $activoRaw = $_POST['activo'] ?? '';
        $activo = ($activoRaw === '0' || $activoRaw === 0) ? 0 : 1;
        $show_in_home = isset($_POST['show_in_home']) ? (int) $_POST['show_in_home'] : 0;
        $password = trim($_POST['password'] ?? '');

        if ($nombre === '' || $email === '')
            json_response(['success' => false, 'message' => 'Nombre y email son obligatorios.'], 200);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            json_response(['success' => false, 'message' => 'Email inválido.'], 200);
        if (!in_array($rol, $manageable_roles, true))
            json_response(['success' => false, 'message' => 'Rol inválido.'], 200);

        if ($sucursal_id !== null && $sucursal_id > 0) {
            $stmt = $pdo->prepare('SELECT id, empresa_id FROM sucursales WHERE id=? LIMIT 1');
            $stmt->execute([$sucursal_id]);
            $s = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$s)
                json_response(['success' => false, 'message' => 'Sucursal inválida.'], 200);
            if ((int) $s['empresa_id'] !== $empresa_id)
                json_response(['success' => false, 'message' => 'La sucursal no pertenece a tu empresa.'], 200);
        }

        try {
            if ($id > 0) {
                $stmt = $pdo->prepare('SELECT id, empresa_id, rol FROM usuarios WHERE id=? LIMIT 1');
                $stmt->execute([$id]);
                $before = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$before || (int) $before['empresa_id'] !== $empresa_id)
                    json_response(['success' => false, 'message' => 'No autorizado.'], 403);
                if (!in_array((string) ($before['rol'] ?? ''), $manageable_roles, true))
                    json_response(['success' => false, 'message' => 'No puedes editar un rol igual o superior al tuyo.'], 200);

                $stmt = $pdo->prepare('UPDATE usuarios SET sucursal_id=?, rol=?, nombre=?, email=?, telefono=?, activo=? WHERE id=? AND empresa_id=?');
                $stmt->execute([$sucursal_id, $rol, $nombre, $email, $telefono ?: null, $activo, $id, $empresa_id]);

                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare('UPDATE usuarios SET password_hash=? WHERE id=? AND empresa_id=?');
                    $stmt->execute([$hash, $id, $empresa_id]);
                }
            } else {
                if ($password === '') {
                    $password = bin2hex(random_bytes(4));
                }
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('INSERT INTO usuarios (empresa_id, sucursal_id, rol, nombre, email, telefono, password_hash, activo) VALUES (?,?,?,?,?,?,?,?)');
                $stmt->execute([$empresa_id, $sucursal_id, $rol, $nombre, $email, $telefono ?: null, $hash, $activo]);
                $id = (int) $pdo->lastInsertId();
            }
            home_page_sync_item($empresa_id, 'usuarios', (int) $id, $show_in_home === 1);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 200);
        }

        json_response(['success' => true, 'id' => $id]);
        break;

    case 'delete':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0)
            json_response(['success' => false, 'message' => 'ID inválido.'], 200);

        $stmt = $pdo->prepare('SELECT rol FROM usuarios WHERE id = ? AND empresa_id = ? LIMIT 1');
        $stmt->execute([$id, $empresa_id]);
        $target_role = (string) ($stmt->fetchColumn() ?: '');
        if (!in_array($target_role, $manageable_roles, true)) {
            json_response(['success' => false, 'message' => 'No puedes eliminar un rol igual o superior al tuyo.'], 200);
        }

        $stmt = $pdo->prepare('UPDATE usuarios SET activo = 0 WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$id, $empresa_id]);
        home_page_sync_item($empresa_id, 'usuarios', (int) $id, false);
        json_response(['success' => true]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
