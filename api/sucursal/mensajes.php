<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
$role = $user['rol'] ?? null;
$empresa_id = resolve_private_empresa_id($user);
$uid = (int) ($user['id'] ?? 0);

if (!$user || $empresa_id <= 0 || !in_array($role, ['gerente', 'admin', 'superadmin'], true)) {
    json_response(['error' => 'unauthorized'], 403);
}

switch ($action) {
    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = max(1, min(50, (int) ($_GET['per'] ?? 10)));
        $search = trim((string) ($_GET['search'] ?? ''));
        $estado = trim((string) ($_GET['estado'] ?? ''));
        $folder = trim((string) ($_GET['folder'] ?? 'inbox')); // inbox | sent
        if ($folder === 'sent') {
            $stmt = $pdo->prepare("SELECT mi.id, mi.titulo, mi.cuerpo, mi.estado, mi.created_at,
                                          COALESCE(u.nombre, CONCAT('Rol ', mi.para_rol), 'General') AS remitente
                                   FROM mensajes_internos mi
                                   LEFT JOIN usuarios u ON u.id = mi.para_usuario_id
                                   WHERE mi.empresa_id = ? AND mi.de_usuario_id = ?
                                   ORDER BY mi.created_at DESC");
            $stmt->execute([$empresa_id, $uid]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $items = array_values(array_filter(array_map(static function ($r) {
                $raw = (string) ($r['estado'] ?? 'enviado');
                if ($raw === 'eliminado') {
                    return null;
                }
                $r['estado'] = ($raw === 'enviado') ? 'nuevo' : $raw;
                $r['es_mio'] = 1;
                return $r;
            }, $items)));
        } else {
            $stmt = $pdo->prepare("SELECT mi.id, mi.titulo, mi.cuerpo, mi.estado, mi.created_at,
                                          COALESCE(u.nombre,'Sistema') AS remitente
                                   FROM mensajes_internos mi
                                   LEFT JOIN usuarios u ON u.id = mi.de_usuario_id
                                   WHERE mi.empresa_id = ? AND (mi.para_usuario_id = ? OR (mi.para_usuario_id IS NULL AND mi.para_rol='gerente'))
                                   ORDER BY mi.created_at DESC");
            $stmt->execute([$empresa_id, $uid]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $items = array_values(array_filter(array_map(static function ($r) {
                $raw = (string) ($r['estado'] ?? 'enviado');
                if ($raw === 'eliminado') {
                    return null;
                }
                $r['estado'] = ($raw === 'enviado') ? 'nuevo' : $raw;
                return $r;
            }, $items)));
        }
        if ($estado !== '' && in_array($estado, ['nuevo', 'leido', 'archivado'], true)) {
            $items = array_values(array_filter($items, static fn($i) => (string) ($i['estado'] ?? '') === $estado));
        }
        if ($search !== '') {
            $s = mb_strtolower($search);
            $items = array_values(array_filter($items, static function ($i) use ($s) {
                return str_contains(mb_strtolower(((string) ($i['titulo'] ?? '')) . ' ' . ((string) ($i['cuerpo'] ?? '')) . ' ' . ((string) ($i['remitente'] ?? ''))), $s);
            }));
        }
        $total = count($items);
        $off = ($page - 1) * $per;
        json_response(['success' => true, 'data' => array_slice($items, $off, $per), 'total' => $total, 'total_pages' => (int) ceil($total / $per)]);
        break;

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $folder = trim((string) ($_GET['folder'] ?? 'inbox'));
        if ($folder === 'sent') {
            $stmt = $pdo->prepare("SELECT mi.id, mi.titulo, mi.cuerpo, mi.estado, mi.created_at,
                                          COALESCE(u.nombre, CONCAT('Rol ', mi.para_rol), 'General') AS remitente
                                   FROM mensajes_internos mi
                                   LEFT JOIN usuarios u ON u.id = mi.para_usuario_id
                                   WHERE mi.id = ? AND mi.empresa_id = ? AND mi.de_usuario_id = ?
                                   LIMIT 1");
            $stmt->execute([$id, $empresa_id, $uid]);
        } else {
            $stmt = $pdo->prepare("SELECT mi.id, mi.titulo, mi.cuerpo, mi.estado, mi.created_at,
                                          COALESCE(u.nombre,'Sistema') AS remitente
                                   FROM mensajes_internos mi
                                   LEFT JOIN usuarios u ON u.id = mi.de_usuario_id
                                   WHERE mi.id = ? AND mi.empresa_id = ? AND (mi.para_usuario_id = ? OR (mi.para_usuario_id IS NULL AND mi.para_rol='gerente'))
                                   LIMIT 1");
            $stmt->execute([$id, $empresa_id, $uid]);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$row)
            json_response(['success' => false, 'message' => 'No encontrado.'], 404);
        if ((string) ($row['estado'] ?? '') === 'eliminado') {
            json_response(['success' => false, 'message' => 'No encontrado.'], 404);
        }
        $row['estado'] = ((string) ($row['estado'] ?? '') === 'enviado') ? 'nuevo' : (string) ($row['estado'] ?? '');
        json_response(['success' => true, 'data' => $row]);
        break;

    case 'set_estado':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);
        $id = (int) ($_POST['id'] ?? 0);
        $estado = trim((string) ($_POST['estado'] ?? ''));
        $folder = trim((string) ($_POST['folder'] ?? 'inbox'));
        if (!in_array($estado, ['nuevo', 'leido', 'archivado', 'eliminado'], true))
            json_response(['success' => false, 'message' => 'Estado inválido.'], 400);
        $raw = $estado === 'nuevo' ? 'enviado' : $estado;
        if ($folder === 'sent') {
            $stmt = $pdo->prepare("UPDATE mensajes_internos
                                   SET estado = ?
                                   WHERE id = ? AND empresa_id = ? AND de_usuario_id = ?");
            $stmt->execute([$raw, $id, $empresa_id, $uid]);
        } else {
            $stmt = $pdo->prepare("UPDATE mensajes_internos
                                   SET estado = ?
                                   WHERE id = ? AND empresa_id = ? AND (para_usuario_id = ? OR (para_usuario_id IS NULL AND para_rol='gerente'))");
            $stmt->execute([$raw, $id, $empresa_id, $uid]);
        }
        json_response(['success' => true]);
        break;

    case 'list_targets':
        $stmt = $pdo->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE empresa_id = ? AND rol = 'empleado' AND activo = 1 ORDER BY nombre ASC");
        $stmt->execute([$empresa_id]);
        json_response(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'send':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);
        $modo = trim((string) ($_POST['modo'] ?? 'all'));
        $target_user_id = (int) ($_POST['target_user_id'] ?? 0);
        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        $cuerpo = trim((string) ($_POST['cuerpo'] ?? ''));
        if (!in_array($modo, ['all', 'one'], true) || $titulo === '' || $cuerpo === '') {
            json_response(['success' => false, 'message' => 'Datos inválidos.'], 400);
        }
        $targets = [];
        if ($modo === 'one') {
            if ($target_user_id <= 0)
                json_response(['success' => false, 'message' => 'Selecciona un empleado.'], 400);
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND empresa_id = ? AND rol = 'empleado' AND activo = 1 LIMIT 1");
            $stmt->execute([$target_user_id, $empresa_id]);
            $ok = (int) ($stmt->fetchColumn() ?: 0);
            if ($ok <= 0)
                json_response(['success' => false, 'message' => 'Empleado inválido.'], 400);
            $targets[] = $ok;
        } else {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE empresa_id = ? AND rol = 'empleado' AND activo = 1");
            $stmt->execute([$empresa_id]);
            $targets = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        }
        if (empty($targets))
            json_response(['success' => false, 'message' => 'No hay destinatarios.'], 400);
        $stmt = $pdo->prepare("INSERT INTO mensajes_internos (empresa_id, para_rol, para_usuario_id, de_usuario_id, titulo, cuerpo, estado)
                               VALUES (?, 'empleado', ?, ?, ?, ?, 'enviado')");
        foreach ($targets as $tid) {
            $stmt->execute([$empresa_id, $tid, $uid, $titulo, $cuerpo]);
            create_notification([
                'empresa_id' => $empresa_id,
                'usuario_id' => (int) $tid,
                'rol_destino' => 'empleado',
                'tipo' => 'mensaje_interno',
                'titulo' => 'Nuevo mensaje interno',
                'descripcion' => $titulo,
                'url' => view_url('vistas/empleado/mensajes.php', $empresa_id),
                'referencia_tipo' => 'mensaje',
            ]);
        }
        json_response(['success' => true, 'sent' => count($targets)]);
        break;

    case 'unread':
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mensajes_internos WHERE empresa_id = ? AND (para_usuario_id = ? OR (para_usuario_id IS NULL AND para_rol='gerente')) AND estado = 'enviado'");
        $stmt->execute([$empresa_id, $uid]);
        json_response(['success' => true, 'count' => (int) $stmt->fetchColumn()]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
