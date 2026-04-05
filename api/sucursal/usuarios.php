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
$roles_permitidos = ['superadmin', 'admin', 'gerente'];
if (!$user || !in_array($user['rol'] ?? null, $roles_permitidos)) {
    json_response(['error' => 'unauthorized'], 403);
}
$role = $user['rol'] ?? null;
if (!can_act_as_role($role, 'gerente'))
    json_response(['error' => 'unauthorized'], 403);

$empresa_id = resolve_private_empresa_id($user);
if ($empresa_id <= 0)
    json_response(['error' => 'unauthorized'], 403);
$sucursal_id_filtro = (int) ($user['sucursal_id'] ?? 0);

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
        ];
        $orderBy = $sortMap[$sort] ?? 'u.id';

        $where = ['u.empresa_id = :eid', "u.rol = 'empleado'"];
        $params = [':eid' => $empresa_id];
        if ($sucursal_id_filtro > 0) {
            $where[] = 'u.sucursal_id = :sid';
            $params[':sid'] = $sucursal_id_filtro;
        }
        if ($search !== '') {
            $where[] = '(u.nombre LIKE :s OR u.email LIKE :s)';
            $params[':s'] = "%$search%";
        }
        if ($rol !== '' && $rol === 'empleado') {
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

        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => $total_pages]);
        break;

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT id, empresa_id, sucursal_id, rol, nombre, email, telefono, activo FROM usuarios WHERE id=? LIMIT 1');
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        if ($data && ((int) $data['empresa_id'] !== $empresa_id || ($sucursal_id_filtro > 0 && (int) ($data['sucursal_id'] ?? 0) !== $sucursal_id_filtro)))
            $data = [];
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
        $password = trim($_POST['password'] ?? '');
        if ($role === 'gerente' && $id <= 0) {
            json_response(['success' => false, 'message' => 'El gerente no puede crear usuarios; solo editar empleados existentes.'], 200);
        }

        if ($nombre === '' || $email === '')
            json_response(['success' => false, 'message' => 'Nombre y email son obligatorios.'], 200);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            json_response(['success' => false, 'message' => 'Email inválido.'], 200);
        if ($rol !== 'empleado')
            json_response(['success' => false, 'message' => 'Desde este módulo solo puedes gestionar empleados.'], 200);
        if ($sucursal_id_filtro > 0) {
            $sucursal_id = $sucursal_id_filtro;
        }

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
                $stmt = $pdo->prepare('SELECT id, empresa_id FROM usuarios WHERE id=? LIMIT 1');
                $stmt->execute([$id]);
                $before = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$before || (int) $before['empresa_id'] !== $empresa_id)
                    json_response(['success' => false, 'message' => 'No autorizado.'], 403);
                if ((string) ($before['rol'] ?? '') !== 'empleado')
                    json_response(['success' => false, 'message' => 'Solo puedes editar usuarios con rol empleado.'], 200);
                if ($sucursal_id_filtro > 0 && (int) ($before['sucursal_id'] ?? 0) !== $sucursal_id_filtro)
                    json_response(['success' => false, 'message' => 'Solo puedes editar empleados de tu sucursal.'], 200);

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
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 200);
        }

        json_response(['success' => true, 'id' => $id]);
        break;

    case 'delete':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);
        if ($role === 'gerente') {
            json_response(['success' => false, 'message' => 'El gerente no puede eliminar usuarios.'], 200);
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0)
            json_response(['success' => false, 'message' => 'ID inválido.'], 200);

        $stmt = $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE id = ? AND empresa_id = ? AND rol = 'empleado'" . ($sucursal_id_filtro > 0 ? " AND sucursal_id = ?" : ""));
        $paramsDel = [$id, $empresa_id];
        if ($sucursal_id_filtro > 0) $paramsDel[] = $sucursal_id_filtro;
        $stmt->execute($paramsDel);
        json_response(['success' => true]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
