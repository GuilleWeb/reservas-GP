<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
if (!$user || (($user['rol'] ?? null) !== 'superadmin'))
    json_response(['error' => 'unauthorized'], 403);

switch ($action) {
    case 'list':
        $page = max(1, intval($_GET['page'] ?? 1));
        $per = max(1, intval($_GET['per'] ?? 10));
        $search = trim($_GET['search'] ?? '');
        $estado = trim($_GET['estado'] ?? '');
        $empresa_id = intval($_GET['empresa_id'] ?? 0);
        $folder = trim((string) ($_GET['folder'] ?? 'inbox')); // inbox | sent
        $sort = trim($_GET['sort'] ?? 'id');
        $dir = strtolower(trim($_GET['dir'] ?? 'desc'));
        $dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc';

        $sortMap = [
            'id' => 'mc.id',
            'created_at' => 'mc.created_at',
            'estado' => 'mc.estado',
            'empresa' => 'e.nombre',
            'asunto' => 'mc.asunto',
        ];
        $orderBy = $sortMap[$sort] ?? 'mc.id';

        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(mc.nombre LIKE :s OR mc.email LIKE :s OR mc.asunto LIKE :s OR mc.mensaje LIKE :s)';
            $params[':s'] = "%$search%";
        }
        if ($estado !== '' && in_array($estado, ['nuevo', 'leido', 'archivado'], true)) {
            $where[] = 'mc.estado = :st';
            $params[':st'] = $estado;
        }
        if ($empresa_id > 0) {
            $where[] = 'mc.empresa_id = :eid';
            $params[':eid'] = $empresa_id;
        }

        if ($folder === 'sent') {
            $where2 = ['mi.de_usuario_id = :uid'];
            $params2 = [':uid' => (int) $user['id']];
            if ($search !== '') {
                $where2[] = '(mi.titulo LIKE :s OR mi.cuerpo LIKE :s)';
                $params2[':s'] = "%$search%";
            }
            if ($empresa_id > 0) {
                $where2[] = 'mi.empresa_id = :eid';
                $params2[':eid'] = $empresa_id;
            }
            $whereSql2 = 'WHERE ' . implode(' AND ', $where2);
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM mensajes_internos mi $whereSql2");
            $stmt->execute($params2);
            $total = (int) $stmt->fetchColumn();
            $total_pages = (int) ceil($total / $per);
            $offset = ($page - 1) * $per;
            $stmt = $pdo->prepare("SELECT mi.id, mi.empresa_id, e.slug AS id_e, e.nombre AS empresa_nombre,
                                          COALESCE(u.nombre, CONCAT('Rol ', mi.para_rol), 'General') AS nombre,
                                          '' AS email, '' AS telefono, mi.titulo AS asunto,
                                          mi.estado, mi.created_at
                                   FROM mensajes_internos mi
                                   LEFT JOIN usuarios u ON u.id = mi.para_usuario_id
                                   LEFT JOIN empresas e ON e.id = mi.empresa_id
                                   $whereSql2
                                   ORDER BY mi.id DESC
                                   LIMIT :o,:p");
            foreach ($params2 as $k => $v) {
                $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->bindValue(':o', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':p', $per, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as &$r) {
                $r['es_mio'] = 1;
            }
            unset($r);
        } else {
            $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM mensajes_contacto mc $whereSql");
            $stmt->execute($params);
            $total = (int) $stmt->fetchColumn();
            $total_pages = (int) ceil($total / $per);
            $offset = ($page - 1) * $per;
            $sql = "
                SELECT mc.id, mc.empresa_id, e.slug AS id_e, e.nombre AS empresa_nombre,
                       mc.nombre, mc.email, mc.telefono, mc.asunto, mc.estado, mc.created_at
                FROM mensajes_contacto mc
                JOIN empresas e ON e.id = mc.empresa_id
                $whereSql
                ORDER BY $orderBy $dir
                LIMIT :o,:p
            ";
            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->bindValue(':o', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':p', $per, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => $total_pages]);
        break;

    case 'get':
        $id = intval($_GET['id'] ?? 0);
        $folder = trim((string) ($_GET['folder'] ?? 'inbox'));
        if ($folder === 'sent') {
            $stmt = $pdo->prepare("SELECT mi.id, mi.empresa_id, mi.titulo as asunto, mi.cuerpo as mensaje, mi.created_at,
                                          COALESCE(u.nombre, CONCAT('Rol ', mi.para_rol), 'General') AS nombre,
                                          e.slug AS id_e, e.nombre AS empresa_nombre, mi.estado
                                   FROM mensajes_internos mi
                                   LEFT JOIN usuarios u ON u.id = mi.para_usuario_id
                                   LEFT JOIN empresas e ON e.id = mi.empresa_id
                                   WHERE mi.id = ? AND mi.de_usuario_id = ?
                                   LIMIT 1");
            $stmt->execute([$id, (int) $user['id']]);
            json_response(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC) ?: []]);
        }
        $sql = "
            SELECT mc.*, e.slug AS id_e, e.nombre AS empresa_nombre
            FROM mensajes_contacto mc
            JOIN empresas e ON e.id = mc.empresa_id
            WHERE mc.id = ?
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        json_response(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC) ?: []]);
        break;

    case 'set_estado':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);
        $id = intval($_POST['id'] ?? 0);
        $estado = trim($_POST['estado'] ?? '');
        if (!in_array($estado, ['nuevo', 'leido', 'archivado'], true))
            json_response(['success' => false, 'message' => 'Estado inválido.'], 200);
        $stmt = $pdo->prepare('UPDATE mensajes_contacto SET estado=? WHERE id=?');
        $stmt->execute([$estado, $id]);
        json_response(['success' => true]);
        break;

    case 'list_targets':
        $empresa_id = intval($_GET['empresa_id'] ?? 0);
        $where = "WHERE rol='admin' AND activo=1";
        $params = [];
        if ($empresa_id > 0) {
            $where .= " AND empresa_id = ?";
            $params[] = $empresa_id;
        }
        $stmt = $pdo->prepare("SELECT id, empresa_id, nombre, email FROM usuarios $where ORDER BY nombre ASC");
        $stmt->execute($params);
        json_response(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'send_interno':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);
        $empresa_id = intval($_POST['empresa_id'] ?? 0);
        $para_usuario_id = intval($_POST['para_usuario_id'] ?? 0);
        $modo = trim($_POST['modo'] ?? ''); // 'all' | 'one'
        $titulo = trim($_POST['titulo'] ?? '');
        $cuerpo = trim($_POST['cuerpo'] ?? '');

        if ($titulo === '' || $cuerpo === '')
            json_response(['success' => false, 'message' => 'Título y cuerpo son obligatorios.'], 200);
        if (!in_array($modo, ['all', 'one'], true))
            json_response(['success' => false, 'message' => 'Modo inválido.'], 200);
        if ($modo === 'one' && $para_usuario_id <= 0)
            json_response(['success' => false, 'message' => 'Selecciona un admin.'], 200);

        try {
            $pdo->beginTransaction();

            $targets = [];
            if ($modo === 'one') {
                $stmt = $pdo->prepare("SELECT id, empresa_id FROM usuarios WHERE id=? AND rol='admin' AND activo=1 LIMIT 1");
                $stmt->execute([$para_usuario_id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row)
                    throw new Exception('Admin inválido.');
                $targets[] = $row;
            } else {
                $where = "WHERE rol='admin' AND activo=1";
                $params = [];
                if ($empresa_id > 0) {
                    $where .= " AND empresa_id=?";
                    $params[] = $empresa_id;
                }
                $stmt = $pdo->prepare("SELECT id, empresa_id FROM usuarios $where");
                $stmt->execute($params);
                $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            if (empty($targets))
                throw new Exception('No hay admins destino.');

            $stmt = $pdo->prepare("INSERT INTO mensajes_internos (empresa_id, para_rol, para_usuario_id, de_usuario_id, titulo, cuerpo, estado) VALUES (?, NULL, ?, ?, ?, ?, 'enviado')");
            foreach ($targets as $t) {
                $stmt->execute([
                    $t['empresa_id'] ?? null,
                    (int) $t['id'],
                    (int) $user['id'],
                    $titulo,
                    $cuerpo
                ]);
                create_notification([
                    'empresa_id' => (int) ($t['empresa_id'] ?? 0),
                    'usuario_id' => (int) ($t['id'] ?? 0),
                    'rol_destino' => 'admin',
                    'tipo' => 'mensaje_interno',
                    'titulo' => 'Nuevo mensaje interno',
                    'descripcion' => $titulo,
                    'url' => view_url('vistas/admin/mensajes.php', (int) ($t['empresa_id'] ?? 0)),
                    'referencia_tipo' => 'mensaje',
                ]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            json_response(['success' => false, 'message' => $e->getMessage()], 200);
        }

        json_response(['success' => true, 'sent' => count($targets)]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
