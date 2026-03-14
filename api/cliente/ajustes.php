<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
$id_e = request_id_e();

if (!$user || ($user['rol'] ?? null) !== 'cliente') {
    json_response(['error' => 'unauthorized'], 403);
}

$empresa_id = (int) ($user['empresa_id'] ?? 0);
$cliente_id = (int) ($user['id'] ?? 0);

switch ($action) {
    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $nombre = trim($_POST['nombre'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($nombre === '') {
            json_response(['success' => false, 'message' => 'Faltan campos obligatorios.']);
        }

        $params = [$nombre, $telefono];
        $sql = "UPDATE usuarios SET nombre=?, telefono=?";

        if ($password !== '') {
            $sql .= ", password=?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id=? AND empresa_id=? AND rol='cliente'";
        $params[] = $cliente_id;
        $params[] = $empresa_id;

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            json_response(['success' => true]);
        } else {
            json_response(['success' => false, 'message' => 'Error al guardar.']);
        }
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
