<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
$id_e = request_id_e();
$roles_permitidos = ['superadmin', 'admin', 'gerente', 'empleado'];
if (!$user || !in_array($user['rol'] ?? null, $roles_permitidos)) {
    json_response(['error' => 'unauthorized'], 403);
}

$empresa_id = resolve_private_empresa_id($user);
$empleado_id = (int) ($user['id'] ?? 0);

switch ($action) {
    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($nombre === '' || $email === '') {
            json_response(['success' => false, 'message' => 'Faltan campos obligatorios.']);
        }

        $params = [$nombre, $email, $telefono];
        $sql = "UPDATE usuarios SET nombre=?, email=?, telefono=?";

        if ($password !== '') {
            $sql .= ", password=?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id=? AND empresa_id=?";
        $params[] = $empleado_id;
        $params[] = $empresa_id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        json_response(['success' => true]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
