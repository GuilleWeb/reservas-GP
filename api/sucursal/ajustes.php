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

$empresa_id = (int) ($user['empresa_id'] ?? 0);

switch ($action) {
    case 'get':
        $stmt = $pdo->prepare('SELECT * FROM empresas_ajustes WHERE id_e = ?');
        $stmt->execute([$id_e]);
        json_response(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC) ?: []]);
        break;

    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $logo = trim($_POST['system_logo_path'] ?? '');
        $color = trim($_POST['ui_primary_color'] ?? '');
        $email = trim($_POST['support_email'] ?? '');
        $phone = trim($_POST['support_phone'] ?? '');
        $hero_t = trim($_POST['hero_titulo'] ?? '');
        $hero_s = trim($_POST['hero_subtitulo'] ?? '');

        $sql = "UPDATE empresas_ajustes SET 
                logo_path=?, color_principal=?, email_contacto=?, telefono_contacto=?, 
                hero_titulo=?, hero_subtitulo=? 
                WHERE id_e=?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$logo, $color, $email, $phone, $hero_t, $hero_s, $id_e])) {
            json_response(['success' => true]);
        } else {
            json_response(['success' => false, 'message' => 'Error al guardar']);
        }
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
