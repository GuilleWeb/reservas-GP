<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
$id_e = request_id_e();
$role = $user['rol'] ?? null;
$empresa_id = (int) ($user['empresa_id'] ?? 0);



$is_authorized = ($user && $empresa_id > 0 && in_array($role, ['admin', 'gerente', 'superadmin'], true));
if (!$is_authorized)
    json_response(['error' => 'unauthorized'], 403);

switch ($action) {
    case 'get_settings':
        $stmt = $pdo->prepare("SELECT config_json FROM empresas WHERE id = ?");
        $stmt->execute([$empresa_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $cfg = json_decode($row['config_json'] ?? '{}', true) ?: [];

        // Ensure defaults for section visibility
        $defaults = [
            'hero_visible' => 1,
            'about_visible' => 1,
            'blog_visible' => 1,
            'equipo_visible' => 1,
            'servicios_visible' => 1,
            'contacto_visible' => 1,
            'featured_blog' => [],
            'featured_equipo' => [],
            'featured_servicios' => []
        ];
        $cfg = array_merge($defaults, $cfg);

        json_response(['success' => true, 'data' => $cfg]);
        break;

    case 'save_settings':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $stmt = $pdo->prepare("SELECT config_json FROM empresas WHERE id = ?");
        $stmt->execute([$empresa_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $cfg = json_decode($row['config_json'] ?? '{}', true) ?: [];

        // Update with new posted values
        $cfg['hero_visible'] = (int) ($_POST['hero_visible'] ?? 0);
        $cfg['about_visible'] = (int) ($_POST['about_visible'] ?? 0);
        $cfg['blog_visible'] = (int) ($_POST['blog_visible'] ?? 0);
        $cfg['equipo_visible'] = (int) ($_POST['equipo_visible'] ?? 0);
        $cfg['servicios_visible'] = (int) ($_POST['servicios_visible'] ?? 0);
        $cfg['contacto_visible'] = (int) ($_POST['contacto_visible'] ?? 0);

        // Featured lists (expected as arrays of IDs)
        $cfg['featured_blog'] = isset($_POST['featured_blog']) ? array_map('intval', $_POST['featured_blog']) : [];
        $cfg['featured_equipo'] = isset($_POST['featured_equipo']) ? array_map('intval', $_POST['featured_equipo']) : [];
        $cfg['featured_servicios'] = isset($_POST['featured_servicios']) ? array_map('intval', $_POST['featured_servicios']) : [];

        $config_json = json_encode($cfg, JSON_UNESCAPED_UNICODE);

        $stmt = $pdo->prepare("UPDATE empresas SET config_json = ? WHERE id = ?");
        $stmt->execute([$config_json, $empresa_id]);

        json_response(['success' => true]);
        break;

    case 'get_catalog':
        // Load available items to choose from
        $blog = $pdo->prepare("SELECT id, titulo FROM blog WHERE empresa_id = ? ORDER BY created_at DESC");
        $blog->execute([$empresa_id]);

        $equipo = $pdo->prepare("SELECT id, nombre FROM equipo WHERE empresa_id = ? ORDER BY nombre ASC");
        $equipo_res = $equipo->execute([$empresa_id]);

        $servicios = $pdo->prepare("SELECT id, nombre FROM servicios WHERE empresa_id = ? AND activo = 1 ORDER BY nombre ASC");
        $servicios->execute([$empresa_id]);

        json_response([
            'success' => true,
            'blog' => $blog->fetchAll(PDO::FETCH_ASSOC),
            'equipo' => $equipo->fetchAll(PDO::FETCH_ASSOC),
            'servicios' => $servicios->fetchAll(PDO::FETCH_ASSOC)
        ]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
