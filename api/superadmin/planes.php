<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
if (!$user || (($user['rol'] ?? null) !== 'superadmin')) json_response(['error' => 'unauthorized'], 403);

switch ($action) {
    case 'list':
        $page = max(1, intval($_GET['page'] ?? 1));
        $per = max(1, intval($_GET['per'] ?? 10));
        $search = trim($_GET['search'] ?? '');
        $activo = $_GET['activo'] ?? '';
        $sort = trim($_GET['sort'] ?? 'id');
        $dir = strtolower(trim($_GET['dir'] ?? 'desc'));
        $dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc';

        $sortMap = [
            'id' => 'id',
            'nombre' => 'nombre',
            'activo' => 'activo',
            'max_clientes' => 'max_clientes',
            'max_servicios' => 'max_servicios',
            'max_empleados' => 'max_empleados',
            'max_sucursales' => 'max_sucursales',
        ];
        $orderBy = $sortMap[$sort] ?? 'id';

        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(nombre LIKE :s OR descripcion LIKE :s)';
            $params[':s'] = "%$search%";
        }
        if ($activo !== '' && ($activo === '0' || $activo === '1')) {
            $where[] = 'activo = :a';
            $params[':a'] = intval($activo);
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM planes $whereSql");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();
        $total_pages = (int)ceil($total / $per);

        $offset = ($page - 1) * $per;
        $sql = "SELECT * FROM planes $whereSql ORDER BY $orderBy $dir LIMIT :o,:p";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->bindValue(':o', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':p', $per, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => $total_pages]);
        break;

    case 'get':
        $id = intval($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM planes WHERE id=?');
        $stmt->execute([$id]);
        json_response(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC) ?: []]);
        break;

    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_response(['error' => 'invalid_method'], 405);

        $id = intval($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $max_sucursales = intval($_POST['max_sucursales'] ?? 1);
        $max_empleados = intval($_POST['max_empleados'] ?? 1);
        $max_servicios = intval($_POST['max_servicios'] ?? 50);
        $max_clientes = intval($_POST['max_clientes'] ?? 10000);
        $activo = isset($_POST['activo']) ? intval($_POST['activo']) : 1;

        if ($nombre === '') json_response(['success' => false, 'message' => 'El nombre es obligatorio.'], 200);

        try {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE planes SET nombre=?, descripcion=?, max_sucursales=?, max_empleados=?, max_servicios=?, max_clientes=?, activo=? WHERE id=?');
                $stmt->execute([$nombre, $descripcion ?: null, $max_sucursales, $max_empleados, $max_servicios, $max_clientes, $activo ? 1 : 0, $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO planes (nombre, descripcion, max_sucursales, max_empleados, max_servicios, max_clientes, activo) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([$nombre, $descripcion ?: null, $max_sucursales, $max_empleados, $max_servicios, $max_clientes, $activo ? 1 : 0]);
                $id = (int)$pdo->lastInsertId();
            }
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 200);
        }

        json_response(['success' => true, 'id' => $id]);
        break;

    case 'delete':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_response(['error' => 'invalid_method'], 405);
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) json_response(['success' => false], 200);
        $stmt = $pdo->prepare('DELETE FROM planes WHERE id=?');
        $stmt->execute([$id]);
        json_response(['success' => true]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
