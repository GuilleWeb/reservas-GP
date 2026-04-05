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
$role = $user['rol'] ?? null;
$empresa_id = resolve_private_empresa_id($user);



$is_authorized = ($user && $empresa_id > 0 && in_array($role, ['admin', 'superadmin'], true));
if (!$is_authorized && $action !== 'list')
    json_response(['error' => 'unauthorized'], 403);

function normalize_slug_text($txt)
{
    $txt = strtolower(trim((string) $txt));
    $txt = preg_replace('/[^a-z0-9]+/', '-', $txt);
    return trim((string) $txt, '-');
}

function build_unique_sucursal_slug($empresa_id, $nombre, $exclude_id = 0)
{
    global $pdo;
    $base = normalize_slug_text($nombre);
    if ($base === '') {
        $base = 'sucursal';
    }
    $slug = $base;
    $n = 1;
    while (true) {
        if ($exclude_id > 0) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM sucursales WHERE empresa_id = ? AND slug = ? AND id <> ?');
            $stmt->execute([(int) $empresa_id, $slug, (int) $exclude_id]);
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM sucursales WHERE empresa_id = ? AND slug = ?');
            $stmt->execute([(int) $empresa_id, $slug]);
        }
        if ((int) $stmt->fetchColumn() === 0) {
            return $slug;
        }
        $n++;
        $slug = $base . '-' . $n;
    }
}

switch ($action) {
    case 'list':
        if ($empresa_id <= 0)
            json_response(['success' => true, 'data' => [], 'total' => 0, 'total_pages' => 1]);

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = max(1, (int) ($_GET['per'] ?? 10));
        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';
        $solo_activas = isset($_GET['activos']) ? 1 : 0;

        $where = ['empresa_id = ?'];
        $params = [$empresa_id];

        if ($search !== '') {
            $where[] = '(nombre LIKE ? OR slug LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($status !== '') {
            $where[] = 'activo = ?';
            $params[] = (int) $status;
        } elseif ($solo_activas) {
            $where[] = 'activo = 1';
        }

        $whereSql = implode(' AND ', $where);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sucursales WHERE $whereSql");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();
        $total_pages = (int) ceil($total / $per);

        $offset = ($page - 1) * $per;
        $sql = "SELECT * FROM sucursales WHERE $whereSql ORDER BY id DESC LIMIT $per OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $ids = array_values(array_filter(array_map(static fn($r) => (int) ($r['id'] ?? 0), $data), static fn($v) => $v > 0));
        $count_map = [];
        if (!empty($ids)) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $sqlc = "SELECT sucursal_id, COUNT(*) AS total
                     FROM usuarios
                     WHERE empresa_id = ?
                       AND activo = 1
                       AND rol IN ('admin','gerente','empleado')
                       AND sucursal_id IN ($ph)
                     GROUP BY sucursal_id";
            $stmt = $pdo->prepare($sqlc);
            $stmt->execute(array_merge([(int) $empresa_id], $ids));
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $rowc) {
                $count_map[(int) $rowc['sucursal_id']] = (int) $rowc['total'];
            }
        }
        $selected = array_flip(home_page_selected_ids($empresa_id, 'sucursales'));
        foreach ($data as &$row) {
            $row['show_in_home'] = isset($selected[(int) ($row['id'] ?? 0)]) ? 1 : 0;
            $row['empleados_count'] = (int) ($count_map[(int) ($row['id'] ?? 0)] ?? 0);
        }
        unset($row);

        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => $total_pages]);
        break;

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM sucursales WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$id, $empresa_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        if ($row) {
            $row['show_in_home'] = home_page_is_item_selected($empresa_id, 'sucursales', (int) $id) ? 1 : 0;
        }
        json_response(['success' => true, 'data' => $row]);
        break;

    case 'get_assign_users':
        $sid = (int) ($_GET['sucursal_id'] ?? 0);
        if ($sid <= 0) {
            json_response(['success' => false, 'message' => 'Sucursal inválida.'], 400);
        }
        $stmt = $pdo->prepare('SELECT id FROM sucursales WHERE id = ? AND empresa_id = ? LIMIT 1');
        $stmt->execute([$sid, $empresa_id]);
        if (!$stmt->fetchColumn()) {
            json_response(['success' => false, 'message' => 'Sucursal no encontrada.'], 404);
        }

        $stmt = $pdo->prepare("SELECT id, nombre, rol, sucursal_id
                               FROM usuarios
                               WHERE empresa_id = ?
                                 AND activo = 1
                                 AND rol IN ('admin','gerente','empleado')
                                 AND (sucursal_id IS NULL OR sucursal_id = 0 OR sucursal_id = ?)
                               ORDER BY rol ASC, nombre ASC");
        $stmt->execute([$empresa_id, $sid]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        json_response(['success' => true, 'data' => $users]);
        break;

    case 'save_assign_users':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);
        $sid = (int) ($_POST['sucursal_id'] ?? 0);
        $user_ids = $_POST['user_ids'] ?? [];
        if (!is_array($user_ids)) $user_ids = [];
        $user_ids = array_values(array_unique(array_filter(array_map('intval', $user_ids), static fn($v) => $v > 0)));
        if ($sid <= 0) {
            json_response(['success' => false, 'message' => 'Sucursal inválida.'], 400);
        }
        $stmt = $pdo->prepare('SELECT id FROM sucursales WHERE id = ? AND empresa_id = ? LIMIT 1');
        $stmt->execute([$sid, $empresa_id]);
        if (!$stmt->fetchColumn()) {
            json_response(['success' => false, 'message' => 'Sucursal no encontrada.'], 404);
        }
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE usuarios
                                   SET sucursal_id = NULL
                                   WHERE empresa_id = ?
                                     AND sucursal_id = ?
                                     AND rol IN ('admin','gerente','empleado')");
            $stmt->execute([$empresa_id, $sid]);
            if (!empty($user_ids)) {
                $ph = implode(',', array_fill(0, count($user_ids), '?'));
                $sql = "UPDATE usuarios
                        SET sucursal_id = ?
                        WHERE empresa_id = ?
                          AND activo = 1
                          AND rol IN ('admin','gerente','empleado')
                          AND id IN ($ph)
                          AND (sucursal_id IS NULL OR sucursal_id = 0 OR sucursal_id = ?)";
                $params = array_merge([$sid, $empresa_id], $user_ids, [$sid]);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }
            $pdo->commit();
            json_response(['success' => true]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            json_response(['success' => false, 'message' => 'No se pudo guardar la asignación.']);
        }
        break;

    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $slug = build_unique_sucursal_slug($empresa_id, $nombre, $id);
        $direccion = trim($_POST['direccion'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $horarioTexto = trim($_POST['horario'] ?? '');
        $horariosRaw = trim((string) ($_POST['horarios_json'] ?? ''));
        $show_in_home = isset($_POST['show_in_home']) ? (int) $_POST['show_in_home'] : 0;
        $horariosData = null;
        if ($horariosRaw !== '') {
            $tmp = json_decode($horariosRaw, true);
            if (is_array($tmp)) {
                $horariosData = $tmp;
            }
        }
        if (!is_array($horariosData)) {
            $horariosData = [
                'lun-vie' => ['inicio' => '08:00', 'fin' => '18:00'],
                'sab' => ['inicio' => '09:00', 'fin' => '13:00'],
            ];
        }
        if ($horarioTexto !== '') {
            $horariosData['texto'] = $horarioTexto;
        }
        $horariosJson = json_encode($horariosData, JSON_UNESCAPED_UNICODE);
        $activo = isset($_POST['activo']) ? (int) ($_POST['activo']) : 1;

        if ($nombre === '')
            json_response(['success' => false, 'message' => 'El nombre es obligatorio.']);

        try {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE sucursales SET nombre=?, slug=?, direccion=?, telefono=?, email=?, horarios_json=?, activo=? WHERE id=? AND empresa_id=?');
                $stmt->execute([$nombre, $slug, $direccion, $telefono, $email, $horariosJson, $activo, $id, $empresa_id]);
                audit_event('update', 'sucursales', $id, "Sucursal actualizada: $nombre", $empresa_id);
            } else {
                $stmt = $pdo->prepare('INSERT INTO sucursales (empresa_id, nombre, slug, direccion, telefono, email, horarios_json, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$empresa_id, $nombre, $slug, $direccion, $telefono, $email, $horariosJson, $activo]);
                $id = (int) $pdo->lastInsertId();
                audit_event('create', 'sucursales', $id, "Nueva sucursal creada: $nombre", $empresa_id);
            }
            home_page_sync_item($empresa_id, 'sucursales', (int) $id, $show_in_home === 1 && $activo === 1);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => 'Error al guardar, verificar campos obligatorios o slug repetido.']);
        }

        json_response(['success' => true, 'id' => $id]);
        break;

    case 'delete':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0)
            json_response(['success' => false, 'message' => 'ID inválido.']);

        // Borrado lógico
        $stmt = $pdo->prepare('UPDATE sucursales SET activo = 0 WHERE id=? AND empresa_id=?');
        $stmt->execute([$id, $empresa_id]);
        home_page_sync_item($empresa_id, 'sucursales', (int) $id, false);
        audit_event('delete', 'sucursales', $id, 'Sucursal desactivada', $empresa_id);
        json_response(['success' => true]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
