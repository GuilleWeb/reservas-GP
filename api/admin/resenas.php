<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';

// Alias CRUD (para estandarizar)
if ($action === 'create')
    $action = 'save';
if ($action === 'read')
    $action = 'get';
if ($action === 'update')
    $action = 'save';

$user = current_user();
$id_e = request_id_e();

// Public
if ($action === 'list_public') {
    if (!$id_e)
        json_response(['success' => false, 'message' => 'Empresa no especificada.'], 400);

    $stmt_emp = $pdo->prepare('SELECT id FROM empresas WHERE slug = ? AND activo = 1 LIMIT 1');
    $stmt_emp->execute([$id_e]);
    $empresa_id = (int) ($stmt_emp->fetchColumn() ?: 0);
    if ($empresa_id <= 0)
        json_response(['success' => true, 'data' => []]);

    $limit = max(1, min(20, (int) ($_GET['limit'] ?? 3)));
    $stmt = $pdo->prepare('SELECT autor_nombre as autor, comentario, rating FROM resenas WHERE empresa_id = ? AND visible_en_home = 1 AND activo = 1 ORDER BY created_at DESC LIMIT ?');
    $stmt->bindValue(1, $empresa_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    json_response(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// Auth tenant
$role = $user['rol'] ?? null;
$empresa_id = resolve_private_empresa_id($user);



$is_authorized = ($user && $empresa_id > 0 && in_array($role, ['admin', 'superadmin'], true));
if (!$is_authorized)
    json_response(['error' => 'unauthorized'], 403);

switch ($action) {
    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = max(1, (int) ($_GET['per'] ?? 10));
        $search = trim((string) ($_GET['search'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $order = trim((string) ($_GET['order'] ?? 'new'));

        $where = ['empresa_id = ?'];
        $params = [$empresa_id];
        if ($search !== '') {
            $where[] = '(autor_nombre LIKE ? OR comentario LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($status !== '') {
            $where[] = 'activo = ?';
            $params[] = (int) $status;
        }
        $whereSql = implode(' AND ', $where);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM resenas WHERE $whereSql");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();
        $total_pages = (int) ceil($total / $per);

        $offset = ($page - 1) * $per;
        $orderSql = $order === 'old' ? 'id ASC' : ($order === 'rating_desc' ? 'rating DESC, id DESC' : ($order === 'rating_asc' ? 'rating ASC, id DESC' : 'id DESC'));
        $sql = "SELECT * FROM resenas WHERE $whereSql ORDER BY $orderSql LIMIT $per OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $selected = array_flip(home_page_selected_ids($empresa_id, 'resenas'));
        foreach ($data as &$row) {
            $row['show_in_home'] = isset($selected[(int) ($row['id'] ?? 0)]) ? 1 : 0;
        }
        unset($row);

        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => $total_pages]);
        break;

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM resenas WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$id, $empresa_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        if ($row) {
            $row['show_in_home'] = home_page_is_item_selected($empresa_id, 'resenas', (int) $id) ? 1 : 0;
        }
        json_response(['success' => true, 'data' => $row]);
        break;

    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Las reseñas no se crean manualmente desde admin.'], 200);
        }
        $show_in_home = isset($_POST['show_in_home']) ? (int) ($_POST['show_in_home']) : (isset($_POST['visible_en_home']) ? (int) ($_POST['visible_en_home']) : 0);
        $visible_en_home = $show_in_home === 1 ? 1 : 0;
        $activo = isset($_POST['activo']) ? (int) ($_POST['activo']) : 1;
        $stmt = $pdo->prepare('SELECT activo FROM resenas WHERE id=? AND empresa_id=? LIMIT 1');
        $stmt->execute([$id, $empresa_id]);
        $cur = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$cur) {
            json_response(['success' => false, 'message' => 'Reseña no encontrada.']);
        }
        $is_superadmin = (($role ?? null) === 'superadmin');
        if (!$is_superadmin && (int) ($cur['activo'] ?? 0) === 1 && $activo !== 1) {
            json_response(['success' => false, 'message' => 'Solo superadmin puede cambiar el estado de una reseña aprobada.']);
        }

        try {
            $stmt = $pdo->prepare('UPDATE resenas SET visible_en_home=?, activo=? WHERE id=? AND empresa_id=?');
            $stmt->execute([$visible_en_home, $activo, $id, $empresa_id]);
            home_page_sync_item($empresa_id, 'resenas', (int) $id, $show_in_home === 1 && $activo === 1);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => 'Error al guardar.']);
        }

        json_response(['success' => true, 'id' => $id]);
        break;

    case 'delete':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0)
            json_response(['success' => false, 'message' => 'ID inválido.']);

        // Borrado lógico (NO eliminar fila)
        $stmt = $pdo->prepare('UPDATE resenas SET activo = 0 WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$id, $empresa_id]);
        home_page_sync_item($empresa_id, 'resenas', (int) $id, false);
        json_response(['success' => true]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
