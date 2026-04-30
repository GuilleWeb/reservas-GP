<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
$role = $user['rol'] ?? null;
if (!$user || !in_array($role, ['superadmin', 'admin', 'gerente'], true) || !can_act_as_role($role, 'gerente')) {
    json_response(['error' => 'unauthorized'], 403);
}

$empresa_id = resolve_private_empresa_id($user);
$sucursal_id = (int) ($user['sucursal_id'] ?? 0);
$usuario_id = (int) ($user['id'] ?? 0);
if ($empresa_id <= 0 || $usuario_id <= 0) {
    json_response(['success' => false, 'message' => 'No hay contexto de empresa para este usuario.'], 400);
}
if ($sucursal_id <= 0) {
    $stmt = $pdo->prepare('SELECT id FROM sucursales WHERE empresa_id = ? AND activo = 1 ORDER BY id ASC LIMIT 1');
    $stmt->execute([$empresa_id]);
    $sucursal_id = (int) ($stmt->fetchColumn() ?: 0);
}
if ($sucursal_id <= 0) {
    json_response(['success' => false, 'message' => 'No hay sucursales activas para esta empresa.'], 400);
}

function sucursal_horarios_normalize(array $in): string
{
    $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
    $out = [];
    foreach ($dias as $d) {
        $act = ((int) ($in[$d . '_activo'] ?? 0)) === 1 ? 1 : 0;
        $ini = trim((string) ($in[$d . '_inicio'] ?? '09:00'));
        $fin = trim((string) ($in[$d . '_fin'] ?? '18:00'));
        if (!preg_match('/^\d{2}:\d{2}$/', $ini))
            $ini = '09:00';
        if (!preg_match('/^\d{2}:\d{2}$/', $fin))
            $fin = '18:00';
        $out[$d] = ['activo' => $act, 'inicio' => $ini, 'fin' => $fin];
    }
    return json_encode($out, JSON_UNESCAPED_UNICODE);
}

switch ($action) {
    case 'get':
        $stmt = $pdo->prepare('SELECT id, nombre, direccion, telefono, email, foto_path, horarios_json
                               FROM sucursales
                               WHERE id = ? AND empresa_id = ? LIMIT 1');
        $stmt->execute([$sucursal_id, $empresa_id]);
        $sucursal = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$sucursal) {
            json_response(['success' => false, 'message' => 'Sucursal no encontrada.'], 404);
        }

        $stmt = $pdo->prepare('SELECT id, nombre, email, telefono, foto_path FROM usuarios WHERE id = ? AND empresa_id = ? LIMIT 1');
        $stmt->execute([$usuario_id, $empresa_id]);
        $perfil = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $horarios = json_decode((string) ($sucursal['horarios_json'] ?? '{}'), true);
        if (!is_array($horarios)) {
            $horarios = [];
        }

        json_response([
            'success' => true,
            'data' => [
                'sucursal' => $sucursal,
                'perfil' => $perfil,
                'horarios' => $horarios,
            ]
        ]);
        break;

    case 'save_branch':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $direccion = trim((string) ($_POST['direccion'] ?? ''));
        $telefono = trim((string) ($_POST['telefono'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $foto_path = trim((string) ($_POST['foto_path'] ?? ''));
        if ($nombre === '') {
            json_response(['success' => false, 'message' => 'El nombre de la sucursal es obligatorio.'], 200);
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['success' => false, 'message' => 'El email de la sucursal no es válido.'], 200);
        }

        if (!empty($_FILES['foto_file']['name'])) {
            $ext = strtolower((string) pathinfo((string) $_FILES['foto_file']['name'], PATHINFO_EXTENSION));
            if (!preg_match('/^(png|jpe?g|webp|gif)$/', $ext)) {
                json_response(['success' => false, 'message' => 'Formato de imagen no válido.'], 200);
            }
            $dir = __DIR__ . '/../../assets/sucursales/' . (int) $empresa_id . '/' . (int) $sucursal_id . '/';
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            $filename = 'sucursal_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file((string) $_FILES['foto_file']['tmp_name'], $dir . $filename)) {
                $foto_path = 'assets/sucursales/' . (int) $empresa_id . '/' . (int) $sucursal_id . '/' . $filename;
            }
        }
        $horarios_json = sucursal_horarios_normalize($_POST);

        $stmt = $pdo->prepare('UPDATE sucursales
                               SET nombre = ?, direccion = ?, telefono = ?, email = ?, foto_path = ?, horarios_json = ?
                               WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$nombre, $direccion, $telefono ?: null, $email ?: null, $foto_path ?: null, $horarios_json, $sucursal_id, $empresa_id]);
        json_response(['success' => true]);
        break;

    case 'save_profile':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $telefono = trim((string) ($_POST['telefono'] ?? ''));
        $foto_path = trim((string) ($_POST['foto_path'] ?? ''));
        $password = trim((string) ($_POST['password'] ?? ''));
        if ($nombre === '' || $email === '') {
            json_response(['success' => false, 'message' => 'Nombre y email son obligatorios.'], 200);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['success' => false, 'message' => 'Email inválido.'], 200);
        }

        if (!empty($_FILES['foto_file']['name'])) {
            $ext = strtolower((string) pathinfo((string) $_FILES['foto_file']['name'], PATHINFO_EXTENSION));
            if (!preg_match('/^(png|jpe?g|webp|gif)$/', $ext)) {
                json_response(['success' => false, 'message' => 'Formato de imagen no válido.'], 200);
            }
            $dir = __DIR__ . '/../../assets/usuarios/' . (int) $empresa_id . '/' . (int) $usuario_id . '/';
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            $filename = 'perfil_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file((string) $_FILES['foto_file']['tmp_name'], $dir . $filename)) {
                $foto_path = 'assets/usuarios/' . (int) $empresa_id . '/' . (int) $usuario_id . '/' . $filename;
            }
        } elseif ($foto_path === '') {
            $stmt = $pdo->prepare('SELECT foto_path FROM usuarios WHERE id = ? AND empresa_id = ? LIMIT 1');
            $stmt->execute([$usuario_id, $empresa_id]);
            $foto_path = (string) ($stmt->fetchColumn() ?: '');
        }

        $stmt = $pdo->prepare('UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, foto_path = ? WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$nombre, $email, $telefono ?: null, $foto_path ?: null, $usuario_id, $empresa_id]);

        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ? AND empresa_id = ?');
            $stmt->execute([$hash, $usuario_id, $empresa_id]);
        }
        json_response(['success' => true]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
