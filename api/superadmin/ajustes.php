<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
$id_e = request_id_e();
$is_superadmin = ($user && ($user['rol'] ?? null) === 'superadmin' && !$id_e);
$is_tenant_admin = ($user && $id_e && in_array($user['rol'] ?? null, ['admin', 'gerente'], true));

if (!$is_superadmin && !$is_tenant_admin)
    json_response(['error' => 'unauthorized'], 403);

switch ($action) {
    case 'list':
        if (!$is_superadmin)
            json_response(['error' => 'unauthorized'], 403);
        $page = max(1, intval($_GET['page'] ?? 1));
        $per = max(1, intval($_GET['per'] ?? 10));
        $search = trim($_GET['search'] ?? '');

        $whereSql = '';
        $params = [];
        if ($search !== '') {
            $whereSql = 'WHERE clave LIKE :s';
            $params[':s'] = "%$search%";
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ajustes_globales $whereSql");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();
        $total_pages = (int) ceil($total / $per);

        $offset = ($page - 1) * $per;
        $sql = "SELECT id, clave, valor_json, updated_at FROM ajustes_globales $whereSql ORDER BY id DESC LIMIT :o,:p";
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

    case 'get_panel':
        if (!$is_superadmin)
            json_response(['error' => 'unauthorized'], 403);
        $keys = [
            'system_name',
            'maintenance_mode',
            'allow_login',
            'allow_register',
            'system_logo_path',
            'system_favicon_path',
            'ui_primary_color',
            'ui_accent_color',
            'support_email',
            'support_phone',
            'public_footer_text',
            'analytics_ga4_id',
            'smtp_host',
            'smtp_port',
            'smtp_user',
            'smtp_pass',
            'smtp_from_email',
            'smtp_from_name',
        ];

        $in = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $pdo->prepare("SELECT clave, valor_json FROM ajustes_globales WHERE clave IN ($in)");
        $stmt->execute($keys);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        foreach ($rows as $r) {
            $val = json_decode($r['valor_json'] ?? '', true);
            $data[$r['clave']] = $val;
        }
        json_response(['success' => true, 'data' => $data]);
        break;

    case 'save_panel':
        if (!$is_superadmin)
            json_response(['error' => 'unauthorized'], 403);
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $payload = [
            'system_name' => trim($_POST['system_name'] ?? ''),
            'maintenance_mode' => ($_POST['maintenance_mode'] ?? '0') === '1' ? 1 : 0,
            'allow_login' => ($_POST['allow_login'] ?? '1') === '1' ? 1 : 0,
            'allow_register' => ($_POST['allow_register'] ?? '1') === '1' ? 1 : 0,
            'system_logo_path' => trim($_POST['system_logo_path'] ?? ''),
            'system_favicon_path' => trim($_POST['system_favicon_path'] ?? ''),
            'ui_primary_color' => trim($_POST['ui_primary_color'] ?? ''),
            'ui_accent_color' => trim($_POST['ui_accent_color'] ?? ''),
            'support_email' => trim($_POST['support_email'] ?? ''),
            'support_phone' => trim($_POST['support_phone'] ?? ''),
            'public_footer_text' => trim($_POST['public_footer_text'] ?? ''),
            'analytics_ga4_id' => trim($_POST['analytics_ga4_id'] ?? ''),
            'smtp_host' => trim($_POST['smtp_host'] ?? ''),
            'smtp_port' => intval($_POST['smtp_port'] ?? 0),
            'smtp_user' => trim($_POST['smtp_user'] ?? ''),
            'smtp_pass' => trim($_POST['smtp_pass'] ?? ''),
            'smtp_from_email' => trim($_POST['smtp_from_email'] ?? ''),
            'smtp_from_name' => trim($_POST['smtp_from_name'] ?? ''),
        ];

        if ($payload['system_name'] === '') {
            $payload['system_name'] = 'Sistema';
        }
        if ($payload['smtp_port'] <= 0) {
            $payload['smtp_port'] = 587;
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO ajustes_globales (clave, valor_json) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor_json=VALUES(valor_json)');
            foreach ($payload as $k => $v) {
                $json = json_encode($v, JSON_UNESCAPED_UNICODE);
                $stmt->execute([$k, $json]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            json_response(['success' => false, 'message' => $e->getMessage()], 200);
        }

        json_response(['success' => true]);
        break;

    case 'get':
        if (!$is_superadmin)
            json_response(['error' => 'unauthorized'], 403);
        $id = intval($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM ajustes_globales WHERE id=?');
        $stmt->execute([$id]);
        json_response(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC) ?: []]);
        break;

    case 'save':
        if (!$is_superadmin)
            json_response(['error' => 'unauthorized'], 403);
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $id = intval($_POST['id'] ?? 0);
        $clave = trim($_POST['clave'] ?? '');
        $valor_json = trim($_POST['valor_json'] ?? '');

        if ($clave === '' || $valor_json === '')
            json_response(['success' => false, 'message' => 'Clave y valor_json son obligatorios.'], 200);

        json_decode($valor_json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            json_response(['success' => false, 'message' => 'valor_json no es JSON válido.'], 200);
        }

        try {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE ajustes_globales SET clave=?, valor_json=? WHERE id=?');
                $stmt->execute([$clave, $valor_json, $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO ajustes_globales (clave, valor_json) VALUES (?, ?)');
                $stmt->execute([$clave, $valor_json]);
                $id = (int) $pdo->lastInsertId();
            }
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 200);
        }

        json_response(['success' => true, 'id' => $id]);
        break;

    case 'delete':
        if (!$is_superadmin)
            json_response(['error' => 'unauthorized'], 403);
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0)
            json_response(['success' => false], 200);
        $stmt = $pdo->prepare('DELETE FROM ajustes_globales WHERE id=?');
        $stmt->execute([$id]);
        json_response(['success' => true]);
        break;


    default:
        json_response(['error' => 'invalid_action'], 400);
}
