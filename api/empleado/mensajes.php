<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
$role = $user['rol'] ?? null;
$empresa_id = resolve_private_empresa_id($user);
$uid = (int) ($user['id'] ?? 0);

if (!$user || $empresa_id <= 0 || !in_array($role, ['empleado', 'gerente', 'admin', 'superadmin'], true)) {
    json_response(['error' => 'unauthorized'], 403);
}

switch ($action) {
    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = max(1, min(50, (int) ($_GET['per'] ?? 10)));
        $search = trim((string) ($_GET['search'] ?? ''));
        $estado = trim((string) ($_GET['estado'] ?? ''));
        $stmt = $pdo->prepare("SELECT mi.id, mi.titulo, mi.cuerpo, mi.estado, mi.created_at,
                                      COALESCE(u.nombre,'Sistema') AS remitente
                               FROM mensajes_internos mi
                               LEFT JOIN usuarios u ON u.id = mi.de_usuario_id
                               WHERE mi.empresa_id = ? AND (mi.para_usuario_id = ? OR (mi.para_usuario_id IS NULL AND mi.para_rol='empleado'))
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
        $stmt = $pdo->prepare("SELECT mi.id, mi.titulo, mi.cuerpo, mi.estado, mi.created_at,
                                      COALESCE(u.nombre,'Sistema') AS remitente
                               FROM mensajes_internos mi
                               LEFT JOIN usuarios u ON u.id = mi.de_usuario_id
                               WHERE mi.id = ? AND mi.empresa_id = ? AND (mi.para_usuario_id = ? OR (mi.para_usuario_id IS NULL AND mi.para_rol='empleado'))
                               LIMIT 1");
        $stmt->execute([$id, $empresa_id, $uid]);
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
        if (!in_array($estado, ['nuevo', 'leido', 'archivado', 'eliminado'], true))
            json_response(['success' => false, 'message' => 'Estado inválido.'], 400);
        $raw = $estado === 'nuevo' ? 'enviado' : $estado;
        $stmt = $pdo->prepare("UPDATE mensajes_internos
                               SET estado = ?
                               WHERE id = ? AND empresa_id = ? AND (para_usuario_id = ? OR (para_usuario_id IS NULL AND para_rol='empleado'))");
        $stmt->execute([$raw, $id, $empresa_id, $uid]);
        json_response(['success' => true]);
        break;

    case 'unread':
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mensajes_internos WHERE empresa_id = ? AND (para_usuario_id = ? OR (para_usuario_id IS NULL AND para_rol='empleado')) AND estado = 'enviado'");
        $stmt->execute([$empresa_id, $uid]);
        json_response(['success' => true, 'count' => (int) $stmt->fetchColumn()]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}

