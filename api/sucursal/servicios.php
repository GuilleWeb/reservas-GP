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

switch ($action) {
    case 'list':
        $stmt = $pdo->prepare("SELECT * FROM servicios WHERE empresa_id = ? ORDER BY nombre ASC");
        $stmt->execute([$empresa_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        json_response(['success' => true, 'data' => $data, 'total' => count($data)]);
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
        $precio = (float) ($_POST['precio_base'] ?? 0);
        $duracion = (int) ($_POST['duracion_minutos'] ?? 30);
        $desc = trim($_POST['descripcion'] ?? '');
        $activo = (int) ($_POST['activo'] ?? 0);

        if (!$nombre)
            json_response(['success' => false, 'message' => 'Nombre obligatorio.']);

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE servicios SET nombre=?, precio_base=?, duracion_minutos=?, descripcion=?, activo=? WHERE id=? AND empresa_id=?');
            $stmt->execute([$nombre, $precio, $duracion, $desc, $activo, $id, $empresa_id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO servicios (empresa_id, nombre, precio_base, duracion_minutos, descripcion, activo) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$empresa_id, $nombre, $precio, $duracion, $desc, $activo]);
            $id = (int) $pdo->lastInsertId();
        }
        json_response(['success' => true, 'id' => $id]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
