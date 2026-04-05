<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
$role = $user['rol'] ?? null;
$empresa_id = resolve_private_empresa_id($user);
$uid = (int) ($user['id'] ?? 0);

if (!$user || $empresa_id <= 0 || !in_array($role, ['admin', 'superadmin'], true)) {
    json_response(['error' => 'unauthorized'], 403);
}

function admin_msg_fetch_inbox($empresa_id, $uid)
{
    global $pdo;
    $items = [];

    $stmt = $pdo->prepare("SELECT id, asunto AS titulo, mensaje AS cuerpo, nombre AS remitente, email AS remitente_extra, estado, created_at
                           FROM mensajes_contacto
                           WHERE empresa_id = ?");
    $stmt->execute([(int) $empresa_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $items[] = [
            'tipo' => 'externo',
            'id' => (int) ($r['id'] ?? 0),
            'titulo' => (string) ($r['titulo'] ?? ''),
            'cuerpo' => (string) ($r['cuerpo'] ?? ''),
            'remitente' => (string) ($r['remitente'] ?? 'Contacto'),
            'remitente_extra' => (string) ($r['remitente_extra'] ?? ''),
            'estado' => (string) ($r['estado'] ?? 'nuevo'),
            'created_at' => (string) ($r['created_at'] ?? ''),
        ];
    }

    $stmt = $pdo->prepare("SELECT id, titulo, cuerpo, estado, created_at, de_usuario_id,
                                  (SELECT nombre FROM usuarios u2 WHERE u2.id = mi.de_usuario_id LIMIT 1) AS de_nombre
                           FROM mensajes_internos mi
                           WHERE empresa_id = ?
                             AND (para_usuario_id = ? OR (para_usuario_id IS NULL AND para_rol = 'admin'))");
    $stmt->execute([(int) $empresa_id, (int) $uid]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $estado = (string) ($r['estado'] ?? 'enviado');
        $items[] = [
            'tipo' => 'interno',
            'id' => (int) ($r['id'] ?? 0),
            'titulo' => (string) ($r['titulo'] ?? ''),
            'cuerpo' => (string) ($r['cuerpo'] ?? ''),
            'remitente' => (string) ($r['de_nombre'] ?? 'Sistema'),
            'remitente_extra' => 'Interno',
            'estado' => $estado === 'enviado' ? 'nuevo' : $estado,
            'estado_raw' => $estado,
            'created_at' => (string) ($r['created_at'] ?? ''),
        ];
    }

    usort($items, static fn($a, $b) => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
    return $items;
}

switch ($action) {
    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = max(1, min(50, (int) ($_GET['per'] ?? 10)));
        $search = trim((string) ($_GET['search'] ?? ''));
        $estado = trim((string) ($_GET['estado'] ?? ''));
        $tipo = trim((string) ($_GET['tipo'] ?? ''));
        $folder = trim((string) ($_GET['folder'] ?? 'inbox')); // inbox | sent

        if ($folder === 'sent') {
            $stmt = $pdo->prepare("SELECT mi.id, mi.titulo, mi.cuerpo, mi.estado, mi.created_at, mi.para_rol,
                                          COALESCE(u.nombre, CONCAT('Rol ', mi.para_rol), 'General') AS destinatario
                                   FROM mensajes_internos mi
                                   LEFT JOIN usuarios u ON u.id = mi.para_usuario_id
                                   WHERE mi.empresa_id = ? AND mi.de_usuario_id = ?
                                   ORDER BY mi.created_at DESC");
            $stmt->execute([$empresa_id, $uid]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $items = array_map(static function ($r) {
                $raw = (string) ($r['estado'] ?? 'enviado');
                return [
                    'tipo' => 'enviado',
                    'id' => (int) ($r['id'] ?? 0),
                    'titulo' => (string) ($r['titulo'] ?? ''),
                    'cuerpo' => (string) ($r['cuerpo'] ?? ''),
                    'remitente' => 'Yo',
                    'remitente_extra' => '',
                    'destinatario' => (string) ($r['destinatario'] ?? 'General'),
                    'estado' => $raw === 'enviado' ? 'nuevo' : $raw,
                    'created_at' => (string) ($r['created_at'] ?? ''),
                ];
            }, $rows);
        } else {
            $items = admin_msg_fetch_inbox($empresa_id, $uid);
        }
        if ($tipo !== '' && in_array($tipo, ['interno', 'externo'], true)) {
            $items = array_values(array_filter($items, static fn($i) => (string) ($i['tipo'] ?? '') === $tipo));
        }
        if ($estado !== '' && in_array($estado, ['nuevo', 'leido', 'archivado'], true)) {
            $items = array_values(array_filter($items, static fn($i) => (string) ($i['estado'] ?? '') === $estado));
        }
        if ($search !== '') {
            $s = mb_strtolower($search);
            $items = array_values(array_filter($items, static function ($i) use ($s) {
                $txt = mb_strtolower(trim(((string) ($i['titulo'] ?? '')) . ' ' . ((string) ($i['cuerpo'] ?? '')) . ' ' . ((string) ($i['remitente'] ?? ''))));
                return str_contains($txt, $s);
            }));
        }

        $total = count($items);
        $off = ($page - 1) * $per;
        $data = array_slice($items, $off, $per);
        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => (int) ceil($total / $per)]);
        break;

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $tipo = trim((string) ($_GET['tipo'] ?? ''));
        $folder = trim((string) ($_GET['folder'] ?? 'inbox'));
        if ($folder === 'sent') {
            $stmt = $pdo->prepare("SELECT mi.id, mi.titulo, mi.cuerpo, mi.estado, mi.created_at,
                                          COALESCE(u.nombre, CONCAT('Rol ', mi.para_rol), 'General') AS remitente
                                   FROM mensajes_internos mi
                                   LEFT JOIN usuarios u ON u.id = mi.para_usuario_id
                                   WHERE mi.id = ? AND mi.empresa_id = ? AND mi.de_usuario_id = ?
                                   LIMIT 1");
            $stmt->execute([$id, $empresa_id, $uid]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$row)
                json_response(['success' => false, 'message' => 'Mensaje no encontrado.'], 404);
            $row['tipo'] = 'enviado';
            json_response(['success' => true, 'data' => $row]);
        }
        if ($id <= 0 || !in_array($tipo, ['interno', 'externo'], true)) {
            json_response(['success' => false, 'message' => 'Parámetros inválidos.'], 400);
        }
        if ($tipo === 'externo') {
            $stmt = $pdo->prepare("SELECT id, asunto AS titulo, mensaje AS cuerpo, nombre AS remitente, email AS remitente_extra, estado, created_at
                                   FROM mensajes_contacto WHERE id = ? AND empresa_id = ? LIMIT 1");
            $stmt->execute([$id, $empresa_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$row)
                json_response(['success' => false, 'message' => 'Mensaje no encontrado.'], 404);
            $row['tipo'] = 'externo';
            json_response(['success' => true, 'data' => $row]);
        }
        $stmt = $pdo->prepare("SELECT mi.id, mi.titulo, mi.cuerpo, mi.estado, mi.created_at,
                                      COALESCE(u.nombre,'Sistema') AS remitente
                               FROM mensajes_internos mi
                               LEFT JOIN usuarios u ON u.id = mi.de_usuario_id
                               WHERE mi.id = ? AND mi.empresa_id = ? AND (mi.para_usuario_id = ? OR (mi.para_usuario_id IS NULL AND mi.para_rol='admin'))
                               LIMIT 1");
        $stmt->execute([$id, $empresa_id, $uid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$row)
            json_response(['success' => false, 'message' => 'Mensaje no encontrado.'], 404);
        $row['tipo'] = 'interno';
        $row['estado'] = ((string) ($row['estado'] ?? '') === 'enviado') ? 'nuevo' : (string) ($row['estado'] ?? '');
        json_response(['success' => true, 'data' => $row]);
        break;

    case 'set_estado':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);
        $id = (int) ($_POST['id'] ?? 0);
        $tipo = trim((string) ($_POST['tipo'] ?? ''));
        $estado = trim((string) ($_POST['estado'] ?? ''));
        if ($id <= 0 || !in_array($tipo, ['interno', 'externo'], true) || !in_array($estado, ['nuevo', 'leido', 'archivado'], true)) {
            json_response(['success' => false, 'message' => 'Parámetros inválidos.'], 400);
        }
        if ($tipo === 'externo') {
            $stmt = $pdo->prepare("UPDATE mensajes_contacto SET estado = ? WHERE id = ? AND empresa_id = ?");
            $stmt->execute([$estado, $id, $empresa_id]);
            json_response(['success' => true]);
        }
        $map = $estado === 'nuevo' ? 'enviado' : $estado;
        $stmt = $pdo->prepare("UPDATE mensajes_internos
                               SET estado = ?
                               WHERE id = ? AND empresa_id = ? AND (para_usuario_id = ? OR (para_usuario_id IS NULL AND para_rol='admin'))");
        $stmt->execute([$map, $id, $empresa_id, $uid]);
        json_response(['success' => true]);
        break;

    case 'list_targets':
        $target_rol = trim((string) ($_GET['target_rol'] ?? ''));
        if (!in_array($target_rol, ['gerente', 'empleado'], true)) {
            json_response(['success' => false, 'message' => 'Rol destino inválido.'], 400);
        }
        $stmt = $pdo->prepare("SELECT id, nombre, email, rol FROM usuarios
                               WHERE empresa_id = ? AND activo = 1 AND rol = ?
                               ORDER BY nombre ASC");
        $stmt->execute([$empresa_id, $target_rol]);
        json_response(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'send':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);
        $modo = trim((string) ($_POST['modo'] ?? 'all')); // all | one
        $target_rol = trim((string) ($_POST['target_rol'] ?? 'empleado')); // gerente|empleado
        $target_user_id = (int) ($_POST['target_user_id'] ?? 0);
        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        $cuerpo = trim((string) ($_POST['cuerpo'] ?? ''));
        if (!in_array($modo, ['all', 'one'], true) || !in_array($target_rol, ['gerente', 'empleado'], true) || $titulo === '' || $cuerpo === '') {
            json_response(['success' => false, 'message' => 'Datos inválidos.'], 400);
        }
        $targets = [];
        if ($modo === 'one') {
            if ($target_user_id <= 0)
                json_response(['success' => false, 'message' => 'Selecciona un usuario destino.'], 400);
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND empresa_id = ? AND rol = ? AND activo = 1 LIMIT 1");
            $stmt->execute([$target_user_id, $empresa_id, $target_rol]);
            $idOk = (int) ($stmt->fetchColumn() ?: 0);
            if ($idOk <= 0)
                json_response(['success' => false, 'message' => 'Usuario destino inválido.'], 400);
            $targets[] = $idOk;
        } else {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE empresa_id = ? AND rol = ? AND activo = 1");
            $stmt->execute([$empresa_id, $target_rol]);
            $targets = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        }
        if (empty($targets))
            json_response(['success' => false, 'message' => 'No hay destinatarios.'], 400);
        $stmt = $pdo->prepare("INSERT INTO mensajes_internos (empresa_id, para_rol, para_usuario_id, de_usuario_id, titulo, cuerpo, estado)
                               VALUES (?, ?, ?, ?, ?, ?, 'enviado')");
        foreach ($targets as $tid) {
            $stmt->execute([$empresa_id, $target_rol, $tid, $uid, $titulo, $cuerpo]);
            create_notification([
                'empresa_id' => $empresa_id,
                'usuario_id' => (int) $tid,
                'rol_destino' => $target_rol,
                'tipo' => 'mensaje_interno',
                'titulo' => 'Nuevo mensaje interno',
                'descripcion' => $titulo,
                'url' => view_url('vistas/' . ($target_rol === 'gerente' ? 'sucursal' : 'empleado') . '/mensajes.php', $empresa_id),
                'referencia_tipo' => 'mensaje',
            ]);
        }
        json_response(['success' => true, 'sent' => count($targets)]);
        break;

    case 'unread':
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mensajes_contacto WHERE empresa_id = ? AND estado = 'nuevo'");
        $stmt->execute([$empresa_id]);
        $c1 = (int) $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mensajes_internos WHERE empresa_id = ? AND (para_usuario_id = ? OR (para_usuario_id IS NULL AND para_rol='admin')) AND estado = 'enviado'");
        $stmt->execute([$empresa_id, $uid]);
        $c2 = (int) $stmt->fetchColumn();
        json_response(['success' => true, 'count' => $c1 + $c2]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
